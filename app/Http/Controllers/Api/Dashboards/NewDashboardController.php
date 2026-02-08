<?php

namespace App\Http\Controllers\Api\Dashboards;

use App\Domain\CRM\Models\Contract;
use App\Domain\Estimates\Models\Estimate;
use App\Domain\Finance\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class NewDashboardController extends Controller
{
    public function earningReports(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $now = now();
        $year = (int) $now->year;
        $prevYear = $year - 1;

        $current = $this->buildYearSeries((int) $tenantId, (int) $companyId, $year);
        $previous = $this->buildYearSeries((int) $tenantId, (int) $companyId, $prevYear);

        return response()->json([
            'data' => [
                'currency' => 'RUB',
                'year' => $year,
                'prev_year' => $prevYear,
                'labels' => $this->monthLabels(),
                'contracts' => [
                    'current' => $current['contracts'],
                    'prev' => $previous['contracts'],
                ],
                'estimates' => [
                    'current' => $current['estimates'],
                    'prev' => $previous['estimates'],
                ],
                'profit' => [
                    'current' => $current['profit'],
                    'prev' => $previous['profit'],
                ],
            ],
        ]);
    }

    /**
     * @return array{
     *   contracts: array{counts: array<int,int>, sums: array<int,float>, total_count:int, total_sum:float},
     *   estimates: array{counts: array<int,int>, total_count:int},
     *   profit: array{net_sums: array<int,float>, total_net_sum:float}
     * }
     */
    private function buildYearSeries(int $tenantId, int $companyId, int $year): array
    {
        $from = Carbon::create($year, 1, 1)->startOfDay();
        $to = Carbon::create($year, 12, 31)->endOfDay();

        $contractCounts = array_fill(0, 12, 0);
        $contractSums = array_fill(0, 12, 0.0);

        $contractRows = Contract::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->whereNotNull('contract_date')
            ->whereBetween('contract_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('MONTH(contract_date) as m')
            ->selectRaw('COUNT(*) as cnt')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_sum')
            ->groupBy('m')
            ->get();

        foreach ($contractRows as $row) {
            $m = (int) ($row->m ?? 0);
            if ($m < 1 || $m > 12) {
                continue;
            }
            $contractCounts[$m - 1] = (int) ($row->cnt ?? 0);
            $contractSums[$m - 1] = (float) ($row->total_sum ?? 0);
        }

        $estimateCounts = array_fill(0, 12, 0);
        $estimateRows = Estimate::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('MONTH(created_at) as m')
            ->selectRaw('COUNT(*) as cnt')
            ->groupBy('m')
            ->get();

        foreach ($estimateRows as $row) {
            $m = (int) ($row->m ?? 0);
            if ($m < 1 || $m > 12) {
                continue;
            }
            $estimateCounts[$m - 1] = (int) ($row->cnt ?? 0);
        }

        $profitNetSums = array_fill(0, 12, 0.0);
        $profitRows = Transaction::query()
            ->leftJoin('transaction_types as tt', 'tt.id', '=', 'transactions.transaction_type_id')
            ->where('transactions.tenant_id', $tenantId)
            ->where('transactions.company_id', $companyId)
            ->whereBetween('transactions.created_at', [$from, $to])
            ->selectRaw('MONTH(transactions.created_at) as m')
            ->selectRaw('COALESCE(SUM(CASE WHEN tt.sign > 0 THEN transactions.sum ELSE 0 END), 0) as incomes_sum')
            ->selectRaw('COALESCE(SUM(CASE WHEN tt.sign < 0 THEN transactions.sum ELSE 0 END), 0) as expenses_sum')
            ->groupBy('m')
            ->get();

        foreach ($profitRows as $row) {
            $m = (int) ($row->m ?? 0);
            if ($m < 1 || $m > 12) {
                continue;
            }
            $incomes = (float) ($row->incomes_sum ?? 0);
            $expenses = (float) ($row->expenses_sum ?? 0);
            $profitNetSums[$m - 1] = $incomes - $expenses;
        }

        return [
            'contracts' => [
                'counts' => $contractCounts,
                'sums' => $contractSums,
                'total_count' => array_sum($contractCounts),
                'total_sum' => array_sum($contractSums),
            ],
            'estimates' => [
                'counts' => $estimateCounts,
                'total_count' => array_sum($estimateCounts),
            ],
            'profit' => [
                'net_sums' => $profitNetSums,
                'total_net_sum' => array_sum($profitNetSums),
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function monthLabels(): array
    {
        return ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    }
}

