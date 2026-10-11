<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ad System Analytics & Telemetry</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg-main: #0b1120;
            --bg-card: #151f32;
            --bg-card-hover: #1b2842;
            --border-color: #24344d;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --accent-blue: #38bdf8;
            --accent-green: #22c55e;
            --accent-purple: #a855f7;
            --accent-orange: #f59e0b;
            --accent-red: #ef4444;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            background-color: var(--bg-main);
            color: var(--text-primary);
            line-height: 1.5;
            min-height: 100vh;
        }
        .navbar {
            background: rgba(21, 31, 50, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .navbar .logo { display: flex; align-items: center; gap: 0.75rem; }
        .navbar .logo h1 { font-size: 1.25rem; font-weight: 700; }
        .navbar .logo h1 span { color: var(--accent-blue); }
        .nav-links { display: flex; align-items: center; gap: 1rem; }
        .nav-link {
            font-size: 0.88rem; font-weight: 500; color: var(--text-secondary);
            text-decoration: none; padding: 0.4rem 0.85rem; border-radius: 8px;
            transition: all 0.15s ease;
        }
        .nav-link:hover, .nav-link.active {
            color: var(--text-primary); background: rgba(56, 189, 248, 0.12);
        }
        .container { max-width: 1240px; margin: 2rem auto; padding: 0 1.5rem; }
        
        .header-section {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;
        }
        .header-title h2 { font-size: 1.75rem; font-weight: 700; }
        .header-title p { color: var(--text-secondary); font-size: 0.9rem; margin-top: 0.25rem; }
        
        .range-picker {
            display: flex; background: var(--bg-card); padding: 0.25rem;
            border-radius: 8px; border: 1px solid var(--border-color);
        }
        .range-btn {
            padding: 0.4rem 0.85rem; font-size: 0.85rem; font-weight: 600;
            color: var(--text-secondary); text-decoration: none; border-radius: 6px;
        }
        .range-btn.active { color: var(--text-primary); background: rgba(255, 255, 255, 0.1); }

        .kpi-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem; margin-bottom: 2rem;
        }
        .kpi-card {
            background: var(--bg-card); border: 1px solid var(--border-color);
            border-radius: 14px; padding: 1.5rem; display: flex; flex-direction: column;
            position: relative; overflow: hidden;
        }
        .kpi-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
        }
        .kpi-card.blue::before { background: var(--accent-blue); }
        .kpi-card.green::before { background: var(--accent-green); }
        .kpi-card.purple::before { background: var(--accent-purple); }
        .kpi-card.orange::before { background: var(--accent-orange); }
        
        .kpi-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
        .kpi-title { font-size: 0.85rem; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
        .kpi-icon { font-size: 1.5rem; }
        .kpi-value { font-size: 2.2rem; font-weight: 700; line-height: 1.1; }
        .kpi-sub { font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.4rem; }

        .chart-box {
            background: var(--bg-card); border: 1px solid var(--border-color);
            border-radius: 14px; padding: 1.5rem; margin-bottom: 2rem;
        }
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; }
        .chart-header h3 { font-size: 1.15rem; font-weight: 700; }

        .table-box {
            background: var(--bg-card); border: 1px solid var(--border-color);
            border-radius: 14px; padding: 1.5rem; overflow: hidden;
        }
        .table-header { margin-bottom: 1.25rem; }
        .table-header h3 { font-size: 1.15rem; font-weight: 700; }
        .table-header p { color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.2rem; }

        .stats-table {
            width: 100%; border-collapse: collapse; text-align: left;
        }
        .stats-table th {
            padding: 0.85rem 1rem; font-size: 0.75rem; text-transform: uppercase;
            letter-spacing: 0.05em; color: var(--text-secondary); border-bottom: 1px solid var(--border-color);
        }
        .stats-table td {
            padding: 1rem; font-size: 0.9rem; border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            vertical-align: middle;
        }
        .stats-table tr:hover td { background: var(--bg-card-hover); }

        .ad-meta { display: flex; align-items: center; gap: 0.75rem; }
        .ad-thumb {
            width: 48px; height: 32px; border-radius: 4px; object-fit: cover;
            background: #000; border: 1px solid var(--border-color);
        }
        .ad-name { font-weight: 600; color: var(--text-primary); }
        .ad-cat { font-size: 0.75rem; color: var(--text-secondary); }

        .badge { font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 4px; }
        .badge-approved { background: rgba(34, 197, 94, 0.15); color: var(--accent-green); }
        .badge-pending { background: rgba(245, 158, 11, 0.15); color: var(--accent-orange); }
        .badge-declined { background: rgba(239, 68, 68, 0.15); color: var(--accent-red); }

        .ctr-bar-wrapper { display: flex; align-items: center; gap: 0.5rem; min-width: 120px; }
        .ctr-bar-bg { flex: 1; height: 6px; background: rgba(255, 255, 255, 0.1); border-radius: 3px; overflow: hidden; }
        .ctr-bar-fill { height: 100%; background: var(--accent-blue); border-radius: 3px; }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="logo">
            <span style="font-size: 1.5rem">📈</span>
            <h1>AdAnalytics <span>(Laravel Telemetry)</span></h1>
        </div>
        <nav class="nav-links">
            <a href="{{ route('moderator.index') }}" class="nav-link">🛡️ Moderator Queue</a>
            <a href="{{ route('analytics.index') }}" class="nav-link active">📊 Telemetry & Insights</a>
            <span style="font-size: 0.85rem; color: var(--text-secondary); margin-left: 0.5rem;">👤 {{ Auth::user()->name ?? 'Moderator' }}</span>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="nav-link" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5; cursor: pointer;">Log Out</button>
            </form>
            <a href="{{ url('/api/ads') }}" target="_blank" class="nav-link" style="color: var(--accent-blue);">⚡ API</a>
        </nav>
    </header>

    <main class="container">
        <div class="header-section">
            <div class="header-title">
                <h2>Real-Time Ad Performance & Telemetry</h2>
                <p>Tracking billboard impressions, player clicks, expand overlays, and dismissals across the game.</p>
            </div>
            <div class="range-picker">
                <a href="{{ route('analytics.index', ['days' => 7]) }}" class="range-btn {{ $days === 7 ? 'active' : '' }}">Last 7 Days</a>
                <a href="{{ route('analytics.index', ['days' => 14]) }}" class="range-btn {{ $days === 14 ? 'active' : '' }}">Last 14 Days</a>
                <a href="{{ route('analytics.index', ['days' => 30]) }}" class="range-btn {{ $days === 30 ? 'active' : '' }}">Last 30 Days</a>
            </div>
        </div>

        <!-- KPI Summary Cards -->
        <section class="kpi-grid">
            <div class="kpi-card blue">
                <div class="kpi-top">
                    <span class="kpi-title">Total Impressions</span>
                    <span class="kpi-icon">👀</span>
                </div>
                <span class="kpi-value">{{ number_format($totalImpressions) }}</span>
                <span class="kpi-sub">Ad displays viewed by players</span>
            </div>

            <div class="kpi-card green">
                <div class="kpi-top">
                    <span class="kpi-title">Player Interactions / Clicks</span>
                    <span class="kpi-icon">👆</span>
                </div>
                <span class="kpi-value">{{ number_format($totalClicks) }}</span>
                <span class="kpi-sub">Click-to-expand & CTA engagements</span>
            </div>

            <div class="kpi-card purple">
                <div class="kpi-top">
                    <span class="kpi-title">Average CTR</span>
                    <span class="kpi-icon">🎯</span>
                </div>
                <span class="kpi-value">{{ $overallCtr }}%</span>
                <span class="kpi-sub">Click-Through Rate across all slots</span>
            </div>

            <div class="kpi-card orange">
                <div class="kpi-top">
                    <span class="kpi-title">Player Dismissals</span>
                    <span class="kpi-icon">🚫</span>
                </div>
                <span class="kpi-value">{{ number_format($totalDismissals) }}</span>
                <span class="kpi-sub">"Not interested" / dismissed ads</span>
            </div>
        </section>

        <!-- Chart Timeline -->
        <section class="chart-box">
            <div class="chart-header">
                <h3>Impressions & Interactions Timeline</h3>
                <span style="font-size: 0.85rem; color: var(--text-secondary);">Daily breakdown</span>
            </div>
            <div style="height: 300px; width: 100%;">
                <canvas id="timelineChart"></canvas>
            </div>
        </section>

        <!-- Per-Ad Breakdown Table -->
        <section class="table-box">
            <div class="table-header">
                <h3>Advertisement Performance Breakdown</h3>
                <p>Individual telemetry metrics for all submitted ads</p>
            </div>

            <div style="overflow-x: auto;">
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th>Advertisement</th>
                            <th>Media Type</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Impressions</th>
                            <th>Clicks</th>
                            <th>CTR (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($adStats as $ad)
                            <tr>
                                <td>
                                    <div class="ad-meta">
                                        @if($ad->image_path)
                                            <img src="{{ asset('storage/' . $ad->image_path) }}" class="ad-thumb" alt="{{ $ad->business_name }}">
                                        @else
                                            <div class="ad-thumb" style="display:flex;align-items:center;justify-content:center;font-size:0.75rem;">🎬</div>
                                        @endif
                                        <div>
                                            <div class="ad-name">{{ $ad->business_name }}</div>
                                            <div class="ad-cat">ID #{{ $ad->id }} • {{ $ad->created_at->format('M d, Y') }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-size: 0.8rem; text-transform: uppercase; font-weight: 600; color: #7dd3fc;">
                                        {{ $ad->media_type ?? 'image' }}
                                    </span>
                                </td>
                                <td>
                                    <span style="background: rgba(255,255,255,0.06); padding: 3px 8px; border-radius: 4px; font-size: 0.8rem;">
                                        {{ ucfirst($ad->category ?? 'general') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $ad->status }}">{{ strtoupper($ad->status) }}</span>
                                </td>
                                <td style="font-weight: 600;">{{ number_format($ad->impressions_count) }}</td>
                                <td style="font-weight: 600; color: var(--accent-green);">{{ number_format($ad->clicks_count) }}</td>
                                <td>
                                    <div class="ctr-bar-wrapper">
                                        <div class="ctr-bar-bg">
                                            <div class="ctr-bar-fill" style="width: {{ min(100, $ad->ctr * 5) }}%;"></div>
                                        </div>
                                        <span style="font-size: 0.85rem; font-weight: 600;">{{ $ad->ctr }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                                    No advertisements recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        const ctx = document.getElementById('timelineChart').getContext('2d');
        const labels = @json($chartLabels);
        const impressionsData = @json($chartImpressions);
        const clicksData = @json($chartClicks);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Impressions',
                        data: impressionsData,
                        borderColor: '#38bdf8',
                        backgroundColor: 'rgba(56, 189, 248, 0.12)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#38bdf8',
                        pointRadius: 4,
                    },
                    {
                        label: 'Clicks',
                        data: clicksData,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.12)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#22c55e',
                        pointRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { color: '#94a3b8', font: { family: 'Plus Jakarta Sans', size: 12 } }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: '#94a3b8', font: { family: 'Plus Jakarta Sans' } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: {
                            color: '#94a3b8',
                            font: { family: 'Plus Jakarta Sans' },
                            precision: 0
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
