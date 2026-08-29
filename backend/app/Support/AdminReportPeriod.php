<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminReportPeriod
{
    /**
     * @return array{period: string, periodLabel: string, dateFrom: Carbon, dateTo: Carbon}
     */
    public static function resolve(Request $request, string $default = 'this_month'): array
    {
        $period = $request->get('period', $default);
        $customStart = $request->get('start_date');
        $customEnd = $request->get('end_date');

        switch ($period) {
            case 'today':
                $dateFrom = now()->startOfDay();
                $dateTo = now()->endOfDay();
                $periodLabel = 'Bugün';
                break;
            case 'this_week':
                $dateFrom = now()->startOfWeek();
                $dateTo = now()->endOfWeek();
                $periodLabel = 'Bu Hafta';
                break;
            case 'this_month':
                $dateFrom = now()->startOfMonth();
                $dateTo = now()->endOfMonth();
                $periodLabel = 'Bu Ay';
                break;
            case 'this_year':
                $dateFrom = now()->startOfYear();
                $dateTo = now()->endOfYear();
                $periodLabel = 'Bu Yıl';
                break;
            case 'all_time':
                $dateFrom = Carbon::parse('2000-01-01')->startOfDay();
                $dateTo = now()->endOfDay();
                $periodLabel = 'Tüm Zamanlar';
                break;
            case 'custom':
                $dateFrom = $customStart ? Carbon::parse($customStart)->startOfDay() : now()->startOfMonth();
                $dateTo = $customEnd ? Carbon::parse($customEnd)->endOfDay() : now()->endOfDay();
                $periodLabel = $dateFrom->format('d.m.Y') . ' - ' . $dateTo->format('d.m.Y');
                break;
            default:
                $period = 'this_month';
                $dateFrom = now()->startOfMonth();
                $dateTo = now()->endOfMonth();
                $periodLabel = 'Bu Ay';
        }

        return compact('period', 'periodLabel', 'dateFrom', 'dateTo');
    }
}
