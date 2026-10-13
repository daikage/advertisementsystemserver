<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $ad->business_name }} — Advertiser Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0b0f19; color: #f1f5f9; }
        .glass-panel { background: rgba(18, 24, 38, 0.85); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.08); }
        .glow-accent { box-shadow: 0 0 25px -5px rgba(56, 189, 248, 0.25); }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between antialiased">
    <!-- Navbar -->
    <header class="glass-panel border-b border-slate-800/80 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <span class="text-2xl">🎮</span>
                <span class="font-bold tracking-tight text-white text-lg">In-Game Billboard Network</span>
                <span class="hidden sm:inline-block text-xs uppercase px-2 py-0.5 rounded bg-sky-500/10 text-sky-400 font-semibold border border-sky-500/20">Advertiser Portal</span>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-xs text-slate-400 hidden sm:inline">Ref: <code class="text-slate-200 bg-slate-800 px-1.5 py-0.5 rounded">{{ $ad->payment_reference ?: 'ID #' . $ad->id }}</code></span>
                <button onclick="navigator.clipboard.writeText(window.location.href); alert('Dashboard link copied to clipboard!');" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-200 px-3 py-1.5 rounded-lg border border-slate-700 transition flex items-center space-x-1.5">
                    <span>📋 Share Link</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-grow">
        @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 flex items-center space-x-3">
            <span class="text-xl">✅</span>
            <div class="text-sm font-medium">{{ session('success') }}</div>
        </div>
        @endif

        @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Campaign Hero Header -->
        <div class="glass-panel rounded-2xl p-6 sm:p-8 mb-8 relative overflow-hidden glow-accent">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center gap-3 mb-2 flex-wrap">
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">{{ $ad->business_name }}</h1>
                        
                        <!-- Status Badges -->
                        @if($isLive)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Currently Live On Billboard
                            </span>
                        @elseif($isExpired)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/30">
                                ⚠️ Campaign Expired
                            </span>
                        @elseif($ad->status === 'declined')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/30">
                                ❌ Declined
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-sky-500/10 text-sky-400 border border-sky-500/30">
                                ⏳ Pending Moderator Review
                            </span>
                        @endif

                        <span class="text-xs uppercase px-2.5 py-1 rounded-md bg-slate-800 text-slate-300 font-medium">
                            Category: {{ ucfirst($ad->category ?? 'General') }}
                        </span>
                    </div>

                    @if(!empty($ad->description))
                    <p class="text-slate-400 text-sm max-w-2xl mt-1 leading-relaxed">{{ $ad->description }}</p>
                    @endif
                </div>

                <!-- Expiry Countdown Box -->
                <div class="bg-slate-900/90 rounded-xl p-4 border border-slate-800 text-right min-w-[200px]">
                    <div class="text-xs text-slate-400 uppercase font-medium">Schedule Validity</div>
                    @if($ad->expires_at)
                        <div class="text-lg font-bold text-slate-200 mt-1">
                            {{ $ad->expires_at->format('M d, Y') }}
                        </div>
                        <div class="text-xs mt-0.5 {{ $isExpired ? 'text-rose-400 font-semibold' : 'text-emerald-400' }}">
                            {{ $isExpired ? 'Ended ' . $ad->expires_at->diffForHumans() : $timeRemaining . ' remaining' }}
                        </div>
                    @else
                        <div class="text-lg font-bold text-slate-200 mt-1">Open-Ended</div>
                        <div class="text-xs text-slate-400 mt-0.5">No expiry date scheduled</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 4 Analytics Metric Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="glass-panel p-5 rounded-xl border border-slate-800">
                <div class="flex items-center justify-between text-slate-400 text-xs font-medium uppercase tracking-wider mb-2">
                    <span>Billboard Impressions</span>
                    <span class="text-sky-400 text-base">👁️</span>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-white">{{ number_format($impressions) }}</div>
                <div class="text-xs text-slate-500 mt-1">Player views in-game</div>
            </div>

            <div class="glass-panel p-5 rounded-xl border border-slate-800">
                <div class="flex items-center justify-between text-slate-400 text-xs font-medium uppercase tracking-wider mb-2">
                    <span>Interactions / Clicks</span>
                    <span class="text-emerald-400 text-base">👆</span>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-white">{{ number_format($clicks) }}</div>
                <div class="text-xs text-slate-500 mt-1">Direct player engagements</div>
            </div>

            <div class="glass-panel p-5 rounded-xl border border-slate-800">
                <div class="flex items-center justify-between text-slate-400 text-xs font-medium uppercase tracking-wider mb-2">
                    <span>Click-Through Rate (CTR)</span>
                    <span class="text-indigo-400 text-base">🎯</span>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-white">{{ $ctr }}%</div>
                <div class="text-xs text-slate-500 mt-1">Click to impression ratio</div>
            </div>

            <div class="glass-panel p-5 rounded-xl border border-slate-800">
                <div class="flex items-center justify-between text-slate-400 text-xs font-medium uppercase tracking-wider mb-2">
                    <span>Avg View Duration</span>
                    <span class="text-amber-400 text-base">⏱️</span>
                </div>
                <div class="text-2xl sm:text-3xl font-extrabold text-white">{{ number_format($avgViewDuration, 1) }}s</div>
                <div class="text-xs text-slate-500 mt-1">Seconds spent on billboard</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Left 2 Cols: Creative Asset Preview -->
            <div class="lg:col-span-2 glass-panel p-6 rounded-2xl border border-slate-800">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <span>🎨 Creative Preview</span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-800 text-slate-300">
                            {{ strtoupper($ad->media_type ?? 'Image') }}
                        </span>
                    </h2>
                    @if($ad->image_width && $ad->image_height)
                    <span class="text-xs text-slate-400 bg-slate-800 px-2 py-0.5 rounded border border-slate-700">
                        {{ $ad->image_width }} × {{ $ad->image_height }} px
                    </span>
                    @endif
                </div>

                <div class="rounded-xl overflow-hidden bg-slate-950 border border-slate-800 flex items-center justify-center min-h-[300px]">
                    @if($ad->media_type === 'video' && $ad->video_url)
                        <video controls autoplay muted loop playsinline class="w-full max-h-[460px] object-contain">
                            <source src="{{ $ad->video_url }}" type="video/mp4">
                            Your browser does not support video playback.
                        </video>
                    @elseif($ad->image_url)
                        <img src="{{ $ad->image_url }}" alt="{{ $ad->business_name }}" class="w-full max-h-[460px] object-contain rounded-lg">
                    @else
                        <div class="p-8 text-center text-slate-500">
                            <span class="text-3xl block mb-2">🖼️</span>
                            <span>No creative asset uploaded.</span>
                        </div>
                    @endif
                </div>

                @if($ad->click_through_url)
                <div class="mt-4 pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                    <span>Destination Link:</span>
                    <a href="{{ $ad->click_through_url }}" target="_blank" rel="noopener noreferrer" class="text-sky-400 hover:underline flex items-center gap-1 font-mono">
                        {{ $ad->click_through_url }} ↗
                    </a>
                </div>
                @endif
            </div>

            <!-- Right Col: Self-Service Campaign Renewal -->
            <div class="glass-panel p-6 rounded-2xl border border-slate-800 flex flex-col justify-between">
                <div>
                    <h2 class="text-base font-bold text-white mb-2 flex items-center gap-2">
                        <span>⚡ Extend / Renew Campaign</span>
                    </h2>
                    <p class="text-xs text-slate-400 mb-6 leading-relaxed">
                        Keep your ad in active rotation on game billboards. Select a duration tier below to renew instantly via Paystack.
                    </p>

                    <form action="{{ route('advertiser.renew', ['reference' => $ad->payment_reference ?: $ad->id]) }}" method="POST">
                        @csrf
                        <div class="space-y-3 mb-6">
                            @foreach($tiers as $tier)
                            <label class="block cursor-pointer">
                                <input type="radio" name="duration_days" value="{{ $tier['durationDays'] }}" {{ $loop->first ? 'checked' : '' }} class="peer sr-only">
                                <div class="p-3.5 rounded-xl border border-slate-800 bg-slate-900/60 peer-checked:border-sky-500 peer-checked:bg-sky-500/10 peer-checked:ring-1 peer-checked:ring-sky-500 transition flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-semibold text-white">{{ $tier['tierLabel'] }}</div>
                                        <div class="text-xs text-slate-400">{{ $tier['durationDays'] }} days continuous display</div>
                                    </div>
                                    <div class="text-sm font-bold text-emerald-400">
                                        {{ $tier['formattedPrice'] }}
                                    </div>
                                </div>
                            </label>
                            @endforeach
                        </div>

                        @if(empty($ad->submitter_email))
                        <div class="mb-4">
                            <label class="block text-xs text-slate-400 mb-1">Receipt Email:</label>
                            <input type="email" name="email" required placeholder="your.email@example.com" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-sky-500">
                        </div>
                        @endif

                        <button type="submit" class="w-full bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-400 hover:to-blue-500 text-white font-semibold py-3 px-4 rounded-xl shadow-lg shadow-sky-500/20 transition flex items-center justify-center gap-2">
                            <span>💳 Pay With Paystack</span>
                        </button>
                    </form>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-800/80 text-center">
                    <p class="text-[11px] text-slate-500">
                        Secure checkout powered by Paystack. Validity is extended immediately upon payment confirmation.
                    </p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="glass-panel border-t border-slate-800 py-6 text-center text-xs text-slate-500">
        <p>In-Game Advertisement System &bull; Real-Time Billboard Performance Analytics</p>
    </footer>
</body>
</html>
