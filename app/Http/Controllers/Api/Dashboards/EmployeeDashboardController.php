<?php

namespace App\Http\Controllers\Api\Dashboards;

use App\Domain\CRM\Models\Contract;
use App\Domain\Estimates\Models\Estimate;
use App\Domain\Finance\Models\PayrollAccrual;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class EmployeeDashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;
        $userId = $user?->id;

        if (!$tenantId || !$companyId || !$userId) {
            return response()->json(['message' => 'Missing tenant/company/user context.'], 403);
        }

        $now = now();
        $from = $now->copy()->startOfMonth();
        $to = $now->copy()->endOfMonth();
        $prevFrom = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $prevTo = $now->copy()->subMonthNoOverflow()->endOfMonth();

        $contractsAll = Contract::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('manager_id', $userId);

        $contractsMonth = (clone $contractsAll)
            ->whereNotNull('contract_date')
            ->whereBetween('contract_date', [$from->toDateString(), $to->toDateString()]);

        $contractsPrevMonth = (clone $contractsAll)
            ->whereNotNull('contract_date')
            ->whereBetween('contract_date', [$prevFrom->toDateString(), $prevTo->toDateString()]);

        $contractsAllCount = (int) (clone $contractsAll)->count();
        $contractsAllSum = (float) (clone $contractsAll)->sum('total_amount');

        $contractsMonthCount = (int) (clone $contractsMonth)->count();
        $contractsMonthSum = (float) (clone $contractsMonth)->sum('total_amount');

        $contractsPrevMonthCount = (int) (clone $contractsPrevMonth)->count();
        $contractsPrevMonthSum = (float) (clone $contractsPrevMonth)->sum('total_amount');

        $accrualsMonth = PayrollAccrual::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->whereNull('cancelled_at')
            ->where('status', 'active')
            ->whereBetween('created_at', [$from, $to]);

        $salaryMonthCount = (int) (clone $accrualsMonth)->count();
        $salaryMonthSum = (float) (clone $accrualsMonth)->sum('amount');

        $estimatesMonthCount = (int) Estimate::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('created_by', $userId)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        // "Time in system" is an estimate.
        // Default Laravel sessions table doesn't store created_at, so we gracefully fallback.
        $sessionConnection = config('session.connection'); // null => default connection
        $sessionsSeconds = $this->estimateSessionsSeconds($sessionConnection, (int) $userId, $from, $to);

        $weeks = $this->buildWeekBuckets($from, $to);
        $weekLabels = array_map(fn ($w) => $w['label'], $weeks);

        $contractsWeek = $this->bucketizeContracts($tenantId, $companyId, $userId, $from, $to, $weeks);

        $prevWeeks = $this->buildWeekBuckets($prevFrom, $prevTo);
        $prevWeekLabels = array_map(fn ($w) => $w['label'], $prevWeeks);
        $contractsPrevWeek = $this->bucketizeContracts($tenantId, $companyId, $userId, $prevFrom, $prevTo, $prevWeeks);

        $salaryWeek = $this->bucketizeAccruals($tenantId, $companyId, $userId, $from, $to, $weeks);
        $estimatesWeek = $this->bucketizeEstimates($tenantId, $companyId, $userId, $from, $to, $weeks);

        return response()->json([
            'data' => [
                'period' => [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ],
                'contracts' => [
                    'all_time' => [
                        'count' => $contractsAllCount,
                        'sum' => $contractsAllSum,
                        'currency' => 'RUB',
                    ],
                    'month' => [
                        'count' => $contractsMonthCount,
                        'sum' => $contractsMonthSum,
                        'currency' => 'RUB',
                    ],
                    'prev_month' => [
                        'count' => $contractsPrevMonthCount,
                        'sum' => $contractsPrevMonthSum,
                        'currency' => 'RUB',
                    ],
                    'series' => [
                        'labels' => $weekLabels,
                        'counts' => $contractsWeek['counts'],
                        'sums' => $contractsWeek['sums'],
                    ],
                    'prev_series' => [
                        'labels' => $prevWeekLabels,
                        'counts' => $contractsPrevWeek['counts'],
                        'sums' => $contractsPrevWeek['sums'],
                    ],
                ],
                'payroll' => [
                    'month' => [
                        'count' => $salaryMonthCount,
                        'accrued_sum' => $salaryMonthSum,
                        'currency' => 'RUB',
                    ],
                    'series' => [
                        'labels' => $weekLabels,
                        'sums' => $salaryWeek['sums'],
                    ],
                ],
                'estimates' => [
                    'month' => [
                        'count' => $estimatesMonthCount,
                    ],
                    'series' => [
                        'labels' => $weekLabels,
                        'counts' => $estimatesWeek['counts'],
                    ],
                ],
                'activity' => [
                    'month' => [
                        'seconds' => $sessionsSeconds,
                    ],
                ],
            ],
        ]);
    }

    /**
     * @return array<int, array{from: \Carbon\CarbonImmutable, to: \Carbon\CarbonImmutable, label: string}>
     */
    private function buildWeekBuckets($from, $to): array
    {
        $weeks = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();

        while ($cursor->lessThanOrEqualTo($end)) {
            $bucketFrom = $cursor->copy();
            $bucketTo = $cursor->copy()->addDays(6)->endOfDay();
            if ($bucketTo->greaterThan($end)) {
                $bucketTo = $end->copy();
            }

            $weeks[] = [
                'from' => $bucketFrom->toImmutable(),
                'to' => $bucketTo->toImmutable(),
                'label' => $bucketFrom->format('d.m') . '-' . $bucketTo->format('d.m'),
            ];

            $cursor = $cursor->addDays(7);
        }

        return $weeks;
    }

    /**
     * @param array<int, array{from: \Carbon\CarbonImmutable, to: \Carbon\CarbonImmutable, label: string}> $weeks
     * @return array{counts: array<int, int>, sums: array<int, float>}
     */
    private function bucketizeContracts(int $tenantId, int $companyId, int $userId, $from, $to, array $weeks): array
    {
        $counts = array_fill(0, count($weeks), 0);
        $sums = array_fill(0, count($weeks), 0.0);

        $rows = Contract::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('manager_id', $userId)
            ->whereNotNull('contract_date')
            ->whereBetween('contract_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('FLOOR(DATEDIFF(contract_date, ?) / 7) as bucket', [$from->toDateString()])
            ->selectRaw('COUNT(*) as cnt')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_sum')
            ->groupBy('bucket')
            ->get();

        foreach ($rows as $row) {
            $i = (int) $row->bucket;
            if ($i < 0 || $i >= count($weeks)) {
                continue;
            }
            $counts[$i] = (int) $row->cnt;
            $sums[$i] = (float) $row->total_sum;
        }

        return ['counts' => $counts, 'sums' => $sums];
    }

    /**
     * @param array<int, array{from: \Carbon\CarbonImmutable, to: \Carbon\CarbonImmutable, label: string}> $weeks
     * @return array{sums: array<int, float>}
     */
    private function bucketizeAccruals(int $tenantId, int $companyId, int $userId, $from, $to, array $weeks): array
    {
        $sums = array_fill(0, count($weeks), 0.0);

        $rows = PayrollAccrual::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->whereNull('cancelled_at')
            ->where('status', 'active')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('FLOOR(DATEDIFF(DATE(created_at), ?) / 7) as bucket', [$from->toDateString()])
            ->selectRaw('COALESCE(SUM(amount), 0) as total_sum')
            ->groupBy('bucket')
            ->get();

        foreach ($rows as $row) {
            $i = (int) $row->bucket;
            if ($i < 0 || $i >= count($weeks)) {
                continue;
            }
            $sums[$i] = (float) $row->total_sum;
        }

        return ['sums' => $sums];
    }

    /**
     * @param array<int, array{from: \Carbon\CarbonImmutable, to: \Carbon\CarbonImmutable, label: string}> $weeks
     * @return array{counts: array<int, int>}
     */
    private function bucketizeEstimates(int $tenantId, int $companyId, int $userId, $from, $to, array $weeks): array
    {
        $counts = array_fill(0, count($weeks), 0);

        $rows = Estimate::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('created_by', $userId)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('FLOOR(DATEDIFF(DATE(created_at), ?) / 7) as bucket', [$from->toDateString()])
            ->selectRaw('COUNT(*) as cnt')
            ->groupBy('bucket')
            ->get();

        foreach ($rows as $row) {
            $i = (int) $row->bucket;
            if ($i < 0 || $i >= count($weeks)) {
                continue;
            }
            $counts[$i] = (int) $row->cnt;
        }

        return ['counts' => $counts];
    }

    private function estimateSessionsSeconds(?string $connection, int $userId, $from, $to): int
    {
        $db = DB::connection($connection);
        $schema = Schema::connection($connection);

        if (!$schema->hasTable('sessions')) {
            return 0;
        }

        $fromUnix = $from->getTimestamp();
        $toUnix = $to->getTimestamp();

        // If sessions has created_at, we can compute overlap more accurately.
        if ($schema->hasColumn('sessions', 'created_at')) {
            return (int) $db
                ->table('sessions')
                ->where('user_id', $userId)
                ->selectRaw(
                    "COALESCE(SUM(GREATEST(0, TIMESTAMPDIFF(SECOND, GREATEST(created_at, ?), LEAST(FROM_UNIXTIME(last_activity), ?)))), 0) as seconds",
                    [$from, $to]
                )
                ->value('seconds');
        }

        // Fallback: count sessions that were active in the period and multiply by configured lifetime.
        // This is intentionally a conservative estimate (no historical request timeline is stored in DB sessions).
        $lifetimeSeconds = (int) config('session.lifetime', 120) * 60;
        $sessionsCount = (int) $db
            ->table('sessions')
            ->where('user_id', $userId)
            ->whereBetween('last_activity', [$fromUnix, $toUnix])
            ->count();

        return $sessionsCount * $lifetimeSeconds;
    }
}
