<?php

namespace App\Http\Controllers;

use App\Models\AdEvent;
use App\Models\AdSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Display the analytics & performance dashboard.
     */
    public function index(Request $request)
    {
        $days = (int) $request->query('days', 7);
        if ($days < 1 || $days > 90) $days = 7;

        $startDate = now()->subDays($days)->startOfDay();

        // 1. High-level KPI totals
        $totalImpressions = AdEvent::where('event_type', 'impression')->count();
        $totalClicks = AdEvent::where('event_type', 'click')->count();
        $totalDismissals = AdEvent::where('event_type', 'dismiss')->count();

        $overallCtr = $totalImpressions > 0 
            ? round(($totalClicks / $totalImpressions) * 100, 2) 
            : 0.0;

        // 2. Performance breakdown per Ad
        $adStats = AdSubmission::select('ad_submissions.*')
            ->withCount([
                'events as impressions_count' => function ($q) {
                    $q->where('event_type', 'impression');
                },
                'events as clicks_count' => function ($q) {
                    $q->where('event_type', 'click');
                },
                'events as dismissals_count' => function ($q) {
                    $q->where('event_type', 'dismiss');
                },
            ])
            ->orderByDesc('impressions_count')
            ->get()
            ->map(function ($ad) {
                $ctr = $ad->impressions_count > 0 
                    ? round(($ad->clicks_count / $ad->impressions_count) * 100, 2) 
                    : 0.0;
                $ad->ctr = $ctr;
                return $ad;
            });

        // 3. Daily timeline for chart (last $days days)
        $dailyData = DB::table('ad_events')
            ->select(
                DB::raw("DATE(created_at) as date"),
                DB::raw("SUM(CASE WHEN event_type = 'impression' THEN 1 ELSE 0 END) as impressions"),
                DB::raw("SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END) as clicks")
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy(DB::raw("DATE(created_at)"))
            ->orderBy('date', 'asc')
            ->get();

        $chartLabels = [];
        $chartImpressions = [];
        $chartClicks = [];

        // Build continuous date list for chart
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = now()->subDays($i)->format('M d');
            
            $match = $dailyData->firstWhere('date', $d);
            $chartImpressions[] = $match ? (int)$match->impressions : 0;
            $chartClicks[] = $match ? (int)$match->clicks : 0;
        }

        return view('analytics.index', compact(
            'totalImpressions',
            'totalClicks',
            'totalDismissals',
            'overallCtr',
            'adStats',
            'chartLabels',
            'chartImpressions',
            'chartClicks',
            'days'
        ));
    }
}
