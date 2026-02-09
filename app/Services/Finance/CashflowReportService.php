<?php

namespace App\Services\Finance;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CashflowReportService
{
    public function buildReport(
        int $tenantId,
        int $companyId,
        string $dateFrom,
        string $dateTo,
        ?int $cashboxId = null,
        ?string $groupBy = null,
    ): array {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->endOfDay();

        $rows = $this->aggregateByItem($tenantId, $companyId, $from, $to, $cashboxId);
        $sections = $this->buildSections($rows);

        $inflow = $rows->where('direction', 'IN')->sum('amount');
        $outflow = $rows->where('direction', 'OUT')->sum('amount');
        $net = $inflow - $outflow;

        $opening = $this->openingBalance($tenantId, $companyId, $from, $cashboxId);
        $closing = $opening + $net;

        $timeline = null;
        if ($groupBy) {
            $timeline = $this->timeline($tenantId, $companyId, $from, $to, $cashboxId, $groupBy);
        }

        return [
            'summary' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'opening_balance' => (float) $opening,
                'inflow' => (float) $inflow,
                'outflow' => (float) $outflow,
                'net' => (float) $net,
                'closing_balance' => (float) $closing,
                'currency' => 'RUB',
            ],
            'rows' => $sections,
            'timeline' => $timeline,
        ];
    }

    private function aggregateByItem(
        int $tenantId,
        int $companyId,
        Carbon $from,
        Carbon $to,
        ?int $cashboxId,
    ): Collection {
        $query = $this->baseQuery($tenantId, $companyId, $from, $to, $cashboxId);

        return $query
            ->select([
                'cfi.section',
                'cfi.direction',
                'cfi.id',
                'cfi.code',
                'cfi.name',
            ])
            ->selectRaw('COALESCE(SUM(ABS(t.sum)), 0) as amount')
            ->groupBy('cfi.section', 'cfi.direction', 'cfi.id', 'cfi.code', 'cfi.name')
            ->orderBy('cfi.section')
            ->orderBy('cfi.name')
            ->get()
            ->map(function ($row) {
                return [
                    'section' => (string) $row->section,
                    'direction' => (string) $row->direction,
                    'id' => (int) $row->id,
                    'code' => (string) $row->code,
                    'name' => (string) $row->name,
                    'amount' => (float) $row->amount,
                ];
            });
    }

    private function buildSections(Collection $rows): array
    {
        $sections = [];

        foreach ($rows as $row) {
            $section = $row['section'];
            if (!isset($sections[$section])) {
                $sections[$section] = [
                    'section' => $section,
                    'items' => [],
                    'totals' => ['in' => 0.0, 'out' => 0.0, 'net' => 0.0],
                ];
            }

            $amountIn = $row['direction'] === 'IN' ? $row['amount'] : 0.0;
            $amountOut = $row['direction'] === 'OUT' ? $row['amount'] : 0.0;
            $net = $amountIn - $amountOut;

            $sections[$section]['items'][] = [
                'id' => $row['id'],
                'code' => $row['code'],
                'name' => $row['name'],
                'direction' => $row['direction'],
                'amount_in' => $amountIn,
                'amount_out' => $amountOut,
                'net' => $net,
            ];

            $sections[$section]['totals']['in'] += $amountIn;
            $sections[$section]['totals']['out'] += $amountOut;
            $sections[$section]['totals']['net'] += $net;
        }

        return array_values($sections);
    }

    private function timeline(
        int $tenantId,
        int $companyId,
        Carbon $from,
        Carbon $to,
        ?int $cashboxId,
        string $groupBy,
    ): array {
        $periodExpr = $this->periodExpression($groupBy);

        $rows = $this->baseQuery($tenantId, $companyId, $from, $to, $cashboxId)
            ->selectRaw("{$periodExpr} as period")
            ->select('cfi.direction')
            ->selectRaw('COALESCE(SUM(ABS(t.sum)), 0) as amount')
            ->groupByRaw("{$periodExpr}")
            ->groupBy('cfi.direction')
            ->orderByRaw("{$periodExpr}")
            ->get();

        $byPeriod = [];
        foreach ($rows as $row) {
            $period = (string) $row->period;
            if (!isset($byPeriod[$period])) {
                $byPeriod[$period] = ['in' => 0.0, 'out' => 0.0];
            }
            if ((string) $row->direction === 'IN') {
                $byPeriod[$period]['in'] += (float) $row->amount;
            } else {
                $byPeriod[$period]['out'] += (float) $row->amount;
            }
        }

        $timeline = [];
        foreach ($byPeriod as $period => $values) {
            $timeline[] = [
                'period' => $period,
                'inflow' => (float) $values['in'],
                'outflow' => (float) $values['out'],
                'net' => (float) $values['in'] - (float) $values['out'],
            ];
        }

        return $timeline;
    }

    private function openingBalance(int $tenantId, int $companyId, Carbon $from, ?int $cashboxId): float
    {
        if ($cashboxId && $this->hasCashboxSnapshots()) {
            $snapshot = DB::connection('legacy_new')->table('cashbox_balance_snapshots')
                ->where('cashbox_id', $cashboxId)
                ->where(function ($builder) use ($tenantId) {
                    $builder->whereNull('tenant_id')
                        ->orWhere('tenant_id', $tenantId);
                })
                ->where('company_id', $companyId)
                ->where('calculated_at', '<', $from)
                ->orderByDesc('calculated_at')
                ->value('balance');

            if ($snapshot !== null) {
                return (float) $snapshot;
            }
        }

        $rows = $this->aggregateBefore($tenantId, $companyId, $from, $cashboxId);
        $inflow = $rows->where('direction', 'IN')->sum('amount');
        $outflow = $rows->where('direction', 'OUT')->sum('amount');

        return (float) ($inflow - $outflow);
    }

    private function baseQuery(
        int $tenantId,
        int $companyId,
        Carbon $from,
        Carbon $to,
        ?int $cashboxId,
    ) {
        $query = DB::connection('legacy_new')
            ->table('transactions as t')
            ->join('cashflow_items as cfi', 'cfi.id', '=', 't.cashflow_item_id')
            ->where('t.is_paid', 1)
            ->whereBetween('t.date_is_paid', [$from->toDateString(), $to->toDateString()])
            ->whereNotNull('t.cashbox_id')
            ->where('t.company_id', $companyId)
            ->where(function ($builder) use ($tenantId) {
                $builder->whereNull('t.tenant_id')
                    ->orWhere('t.tenant_id', $tenantId);
            })
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw('1'))
                    ->from('cash_transfers as ct')
                    ->where(function ($q) {
                        $q->whereColumn('ct.transaction_in_id', 't.id')
                            ->orWhereColumn('ct.transaction_out_id', 't.id');
                    });
            });

        if ($cashboxId) {
            $query->where('t.cashbox_id', $cashboxId);
        }

        return $query;
    }

    private function aggregateBefore(
        int $tenantId,
        int $companyId,
        Carbon $before,
        ?int $cashboxId,
    ): Collection {
        $query = DB::connection('legacy_new')
            ->table('transactions as t')
            ->join('cashflow_items as cfi', 'cfi.id', '=', 't.cashflow_item_id')
            ->where('t.is_paid', 1)
            ->where('t.date_is_paid', '<', $before->toDateString())
            ->whereNotNull('t.cashbox_id')
            ->where('t.company_id', $companyId)
            ->where(function ($builder) use ($tenantId) {
                $builder->whereNull('t.tenant_id')
                    ->orWhere('t.tenant_id', $tenantId);
            })
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw('1'))
                    ->from('cash_transfers as ct')
                    ->where(function ($q) {
                        $q->whereColumn('ct.transaction_in_id', 't.id')
                            ->orWhereColumn('ct.transaction_out_id', 't.id');
                    });
            });

        if ($cashboxId) {
            $query->where('t.cashbox_id', $cashboxId);
        }

        return $query
            ->select(['cfi.direction'])
            ->selectRaw('COALESCE(SUM(ABS(t.sum)), 0) as amount')
            ->groupBy('cfi.direction')
            ->get()
            ->map(fn ($row) => [
                'direction' => (string) $row->direction,
                'amount' => (float) $row->amount,
            ]);
    }

    private function periodExpression(string $groupBy): string
    {
        $driver = DB::connection()->getDriverName();
        $groupBy = strtolower($groupBy);

        return match ($groupBy) {
            'day' => match ($driver) {
                'pgsql' => "to_char(t.date_is_paid, 'YYYY-MM-DD')",
                'sqlite' => "strftime('%Y-%m-%d', t.date_is_paid)",
                default => "DATE_FORMAT(t.date_is_paid, '%Y-%m-%d')",
            },
            'week' => match ($driver) {
                'pgsql' => "to_char(t.date_is_paid, 'IYYY-IW')",
                'sqlite' => "strftime('%Y-%W', t.date_is_paid)",
                default => "DATE_FORMAT(t.date_is_paid, '%x-%v')",
            },
            default => match ($driver) {
                'pgsql' => "to_char(t.date_is_paid, 'YYYY-MM')",
                'sqlite' => "strftime('%Y-%m', t.date_is_paid)",
                default => "DATE_FORMAT(t.date_is_paid, '%Y-%m')",
            },
        };
    }

    private function hasCashboxSnapshots(): bool
    {
        return Schema::connection('legacy_new')->hasTable('cashbox_balance_snapshots');
    }
}
