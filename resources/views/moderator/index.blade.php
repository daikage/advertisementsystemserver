<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Ad System Moderator Dashboard (Laravel)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #0f172a;
            --bg-card: #1e293b;
            --bg-card-hover: #24344d;
            --border-color: #334155;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --accent-blue: #38bdf8;
            --accent-green: #22c55e;
            --accent-red: #ef4444;
            --accent-orange: #f59e0b;
            --accent-purple: #a855f7;
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
            background: rgba(30, 41, 59, 0.85);
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
        .header-right { display: flex; align-items: center; gap: 1rem; }
        .pulse-indicator {
            width: 10px; height: 10px;
            background-color: var(--accent-green);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--accent-green);
        }
        .api-link {
            font-size: 0.85rem; color: var(--accent-blue);
            text-decoration: none; padding: 0.35rem 0.75rem;
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid rgba(56, 189, 248, 0.2);
            border-radius: 6px;
        }
        .container { max-width: 1240px; margin: 2rem auto; padding: 0 1.5rem; }
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem; margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--bg-card); border: 1px solid var(--border-color);
            border-radius: 12px; padding: 1.25rem; display: flex; align-items: center; gap: 1rem;
        }
        .stat-icon { font-size: 2rem; }
        .stat-num { display: block; font-size: 1.75rem; font-weight: 700; }
        .stat-label { font-size: 0.85rem; color: var(--text-secondary); }
        .stat-card.pending .stat-num { color: var(--accent-orange); }
        .stat-card.approved .stat-num { color: var(--accent-green); }
        .stat-card.expired .stat-num { color: #f43f5e; }
        .stat-card.declined .stat-num { color: var(--accent-red); }
        .stat-card.total .stat-num { color: var(--accent-blue); }

        .filter-bar {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1.75rem; flex-wrap: wrap; gap: 1rem;
        }
        .tab-group { display: flex; background: var(--bg-card); padding: 0.25rem; border-radius: 8px; border: 1px solid var(--border-color); }
        .tab-btn {
            padding: 0.5rem 1rem; font-size: 0.9rem; font-weight: 500;
            color: var(--text-secondary); text-decoration: none; border-radius: 6px;
        }
        .tab-btn.active { color: var(--text-primary); background: rgba(255, 255, 255, 0.08); }
        
        .filter-controls { display: flex; align-items: center; gap: 0.75rem; }
        .category-select {
            background: var(--bg-card); color: var(--text-primary);
            border: 1px solid var(--border-color); border-radius: 8px;
            padding: 0.5rem 0.75rem; font-size: 0.9rem; outline: none;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac; padding: 0.75rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem;
        }

        .cards-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 1.5rem;
        }
        .submission-card {
            background: var(--bg-card); border: 1px solid var(--border-color);
            border-radius: 14px; overflow: hidden; display: flex; flex-direction: column;
            transition: transform 0.15s ease, border-color 0.15s ease;
        }
        .submission-card:hover {
            border-color: rgba(56, 189, 248, 0.4);
            transform: translateY(-2px);
        }
        .card-media-wrapper {
            position: relative; width: 100%; height: 210px;
            background: #090d16; cursor: pointer; overflow: hidden;
            display: flex; align-items: center; justify-content: center;
        }
        .card-media-wrapper img, .card-media-wrapper video {
            width: 100%; height: 100%; object-fit: cover;
        }
        .zoom-badge {
            position: absolute; bottom: 8px; right: 8px;
            background: rgba(0, 0, 0, 0.75); color: #fff;
            font-size: 0.75rem; padding: 3px 8px; border-radius: 4px;
        }
        .media-type-tag {
            position: absolute; top: 8px; left: 8px;
            font-size: 0.7rem; font-weight: 700; padding: 2px 7px;
            border-radius: 4px; text-transform: uppercase;
        }
        .tag-image { background: rgba(56, 189, 248, 0.25); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.4); }
        .tag-video { background: rgba(168, 85, 247, 0.25); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.4); }
        .tag-animated { background: rgba(245, 158, 11, 0.25); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); }

        .card-content { padding: 1.25rem; display: flex; flex-direction: column; flex-grow: 1; }
        .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; }
        .business-title { font-size: 1.2rem; font-weight: 700; margin-top: 0.25rem; }
        .card-date { font-size: 0.8rem; color: var(--text-secondary); }
        
        .badges-row { display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; margin-bottom: 0.35rem; }
        .badge { font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 4px; }
        .badge-pending { background: rgba(245, 158, 11, 0.15); color: var(--accent-orange); border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-approved { background: rgba(34, 197, 94, 0.15); color: var(--accent-green); border: 1px solid rgba(34, 197, 94, 0.3); }
        .badge-expired { background: rgba(244, 63, 94, 0.15); color: #fb7185; border: 1px solid rgba(244, 63, 94, 0.3); }
        .badge-declined { background: rgba(239, 68, 68, 0.15); color: var(--accent-red); border: 1px solid rgba(239, 68, 68, 0.3); }
        .badge-category { background: rgba(56, 189, 248, 0.15); color: #7dd3fc; border: 1px solid rgba(56, 189, 248, 0.3); }
        .badge-schedule { background: rgba(255, 255, 255, 0.08); color: #cbd5e1; }
        .badge-paid { background: rgba(34, 197, 94, 0.15); color: var(--accent-green); border: 1px solid rgba(34, 197, 94, 0.3); }
        .badge-pay-pending { background: rgba(245, 158, 11, 0.15); color: var(--accent-orange); border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-unpaid { background: rgba(239, 68, 68, 0.15); color: var(--accent-red); border: 1px solid rgba(239, 68, 68, 0.3); }
        .stat-card.revenue .stat-num { color: var(--accent-green); }
        .stat-card.active-paid .stat-num { color: var(--accent-blue); }
        .force-approve-row { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: #fca5a5; margin: 0.4rem 0; }
        .force-approve-row input { width: auto !important; margin: 0 !important; }
        .btn-approve[disabled] { opacity: 0.5; cursor: not-allowed; }
        .paystack-link { color: var(--accent-green); text-decoration: none; font-size: 0.8rem; }
        .paystack-link:hover { text-decoration: underline; }

        .contact-info { font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.4rem; }
        .url-info { font-size: 0.85rem; margin-bottom: 0.5rem; word-break: break-all; }
        .url-info a { color: var(--accent-blue); text-decoration: none; }
        .url-info a:hover { text-decoration: underline; }
        
        .schedule-box {
            font-size: 0.8rem; padding: 0.4rem 0.65rem; border-radius: 6px;
            background: rgba(0, 0, 0, 0.25); border: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 0.75rem; color: #94a3b8;
        }
        .schedule-box strong { color: #f8fafc; }

        .card-desc { font-size: 0.9rem; color: #cbd5e1; margin-bottom: 1rem; flex-grow: 1; white-space: pre-wrap; }
        .decline-box {
            background: rgba(239, 68, 68, 0.1); border-left: 3px solid var(--accent-red);
            padding: 0.5rem 0.75rem; font-size: 0.85rem; color: #fca5a5; margin-bottom: 1rem; border-radius: 0 4px 4px 0;
        }
        .card-actions { display: flex; gap: 0.5rem; border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 1rem; }
        .btn {
            border: none; border-radius: 8px; padding: 0.6rem 1rem;
            font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: 0.15s ease;
        }
        .btn-approve { background: #15803d; color: #fff; flex: 1; }
        .btn-approve:hover { background: #16a34a; }
        .btn-decline {
            background: rgba(239, 68, 68, 0.2); color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3); flex: 1;
        }
        .btn-delete { background: transparent; color: var(--text-secondary); padding: 0.6rem 0.8rem; border: 1px solid var(--border-color); }
        .btn-delete:hover { background: rgba(239, 68, 68, 0.2); color: #fff; }

        .empty-state { text-align: center; padding: 4rem 2rem; background: var(--bg-card); border: 1px dashed var(--border-color); border-radius: 12px; }
        .modal {
            display: none; position: fixed; z-index: 1000; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(8px);
            justify-content: center; align-items: center; flex-direction: column;
        }
        .modal-content { max-width: 85%; max-height: 80vh; border-radius: 8px; }
        .modal-dialog {
            background: var(--bg-card); border: 1px solid var(--border-color);
            padding: 2rem; border-radius: 12px; width: 90%; max-width: 480px;
        }
        .modal-dialog textarea, .modal-dialog select, .modal-dialog input {
            width: 100%; background: #0f172a; border: 1px solid var(--border-color);
            color: #fff; padding: 0.75rem; border-radius: 8px; margin: 0.6rem 0; outline: none;
        }
        .modal-actions { display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem; }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="logo">
            <span style="font-size: 1.5rem">⚡</span>
            <h1>AdModerator <span>(Laravel)</span></h1>
        </div>
        <div class="header-right">
            <a href="{{ route('analytics.index') }}" class="api-link" style="background: rgba(168, 85, 247, 0.15); border-color: rgba(168, 85, 247, 0.3); color: #c084fc;">📊 Analytics & Telemetry</a>
            <div class="pulse-indicator"></div>
            <span style="font-size: 0.85rem; color: var(--text-secondary);">👤 {{ Auth::user()->name ?? 'Moderator' }}</span>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5; padding: 0.35rem 0.75rem; font-size: 0.8rem;">Log Out</button>
            </form>
            <a href="{{ url('/api/ads') }}" target="_blank" class="api-link">View Ads API</a>
        </div>
    </header>

    <main class="container">
        @if(session('success'))
            <div class="alert-success">✓ {{ session('success') }}</div>
        @endif

        <!-- Stats -->
        <section class="stats-grid">
            <div class="stat-card pending">
                <div class="stat-icon">⏳</div>
                <div class="stat-info">
                    <span class="stat-num">{{ $counts['pending'] }}</span>
                    <span class="stat-label">Pending Review</span>
                </div>
            </div>
            <div class="stat-card approved">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <span class="stat-num">{{ $counts['approved'] }}</span>
                    <span class="stat-label">Active (Live in Game)</span>
                </div>
            </div>
            <div class="stat-card expired">
                <div class="stat-icon">⏱️</div>
                <div class="stat-info">
                    <span class="stat-num">{{ $counts['expired'] }}</span>
                    <span class="stat-label">Expired Campaigns</span>
                </div>
            </div>
            <div class="stat-card declined">
                <div class="stat-icon">❌</div>
                <div class="stat-info">
                    <span class="stat-num">{{ $counts['declined'] }}</span>
                    <span class="stat-label">Declined</span>
                </div>
            </div>
            <div class="stat-card total">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <span class="stat-num">{{ $counts['total'] }}</span>
                    <span class="stat-label">Total All-Time</span>
                </div>
            </div>
        </section>

        <!-- Financial Metrics -->
        <section class="stats-grid" style="margin-top: -1rem;">
            <div class="stat-card revenue">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <span class="stat-num">&#8358;{{ number_format($financials['total_revenue'], 0) }}</span>
                    <span class="stat-label">Total Revenue Collected</span>
                </div>
            </div>
            <div class="stat-card active-paid">
                <div class="stat-icon">🚀</div>
                <div class="stat-info">
                    <span class="stat-num">{{ $financials['active_paid_campaigns'] }}</span>
                    <span class="stat-label">Active Paid Campaigns</span>
                </div>
            </div>
            <div class="stat-card pending">
                <div class="stat-icon">⏳</div>
                <div class="stat-info">
                    <span class="stat-num">{{ $financials['awaiting_payment'] }}</span>
                    <span class="stat-label">Awaiting Payment</span>
                </div>
            </div>
        </section>

        <!-- Filters -->
        <div class="filter-bar">
            <div class="tab-group">
                <a href="{{ route('moderator.index', ['filter' => 'pending', 'category' => $category]) }}" class="tab-btn {{ $filter === 'pending' ? 'active' : '' }}">Pending ({{ $counts['pending'] }})</a>
                <a href="{{ route('moderator.index', ['filter' => 'approved', 'category' => $category]) }}" class="tab-btn {{ $filter === 'approved' ? 'active' : '' }}">Active ({{ $counts['approved'] }})</a>
                <a href="{{ route('moderator.index', ['filter' => 'expired', 'category' => $category]) }}" class="tab-btn {{ $filter === 'expired' ? 'active' : '' }}">Expired ({{ $counts['expired'] }})</a>
                <a href="{{ route('moderator.index', ['filter' => 'declined', 'category' => $category]) }}" class="tab-btn {{ $filter === 'declined' ? 'active' : '' }}">Declined ({{ $counts['declined'] }})</a>
                <a href="{{ route('moderator.index', ['filter' => 'paid', 'category' => $category]) }}" class="tab-btn {{ $filter === 'paid' ? 'active' : '' }}">💳 Paid ({{ $counts['paid'] }})</a>
                <a href="{{ route('moderator.index', ['filter' => 'unpaid', 'category' => $category]) }}" class="tab-btn {{ $filter === 'unpaid' ? 'active' : '' }}">🚫 Unpaid ({{ $counts['unpaid'] }})</a>
                <a href="{{ route('moderator.index', ['filter' => 'all', 'category' => $category]) }}" class="tab-btn {{ $filter === 'all' ? 'active' : '' }}">All Submissions</a>
            </div>

            <div class="filter-controls">
                <form method="GET" action="{{ route('moderator.index') }}" id="categoryFilterForm" style="display: flex; gap: 0.5rem; align-items: center;">
                    <input type="hidden" name="filter" value="{{ $filter }}">
                    <label style="font-size: 0.85rem; color: var(--text-secondary);">Category:</label>
                    <select name="category" class="category-select" onchange="document.getElementById('categoryFilterForm').submit()">
                        <option value="all" {{ (!$category || $category === 'all') ? 'selected' : '' }}>All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ $category === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                        @endforeach
                    </select>
                </form>

                <button onclick="location.reload()" class="btn" style="background: var(--bg-card); color: #fff; border: 1px solid var(--border-color);">🔄 Refresh</button>
            </div>
        </div>

        <!-- Submissions Grid -->
        <section class="submissions-list">
            @if($submissions->isEmpty())
                <div class="empty-state">
                    <div style="font-size: 3rem; margin-bottom: 0.5rem;">📭</div>
                    <h3>No {{ $filter }} submissions found</h3>
                    <p style="color: var(--text-secondary); margin-top: 0.5rem;">Advertisements submitted by players in-game will appear here automatically.</p>
                </div>
            @else
                <div class="cards-grid">
                    @foreach($submissions as $sub)
                        @php
                            $mediaType = strtolower($sub->media_type ?? 'image');
                            $isExpired = $sub->isExpired();
                        @endphp
                        <div class="submission-card">
                            <div class="card-media-wrapper">
                                @if($mediaType === 'video' && $sub->video_path)
                                    <video src="{{ asset('storage/' . $sub->video_path) }}" controls preload="metadata" style="max-height: 210px;"></video>
                                    <div class="media-type-tag tag-video">🎬 Video</div>
                                @elseif($sub->image_path)
                                    <img src="{{ asset('storage/' . $sub->image_path) }}" alt="{{ $sub->business_name }}" loading="lazy" onclick="openLightbox('{{ asset('storage/' . $sub->image_path) }}', '{{ $sub->business_name }}')">
                                    <div class="media-type-tag {{ $mediaType === 'animated' ? 'tag-animated' : 'tag-image' }}">
                                        {{ $mediaType === 'animated' ? '✨ Animated' : '📷 Image' }}
                                    </div>
                                    <div class="zoom-badge" onclick="openLightbox('{{ asset('storage/' . $sub->image_path) }}', '{{ $sub->business_name }}')">🔍 Zoom</div>
                                @else
                                    <div style="color: var(--text-secondary); font-size: 0.9rem;">No media attached</div>
                                    <div class="media-type-tag tag-image">📷 None</div>
                                @endif
                            </div>

                            <div class="card-content">
                                <div class="card-header">
                                    <div>
                                        <div class="badges-row">
                                            @if($sub->status === 'approved' && $isExpired)
                                                <span class="badge badge-expired">EXPIRED</span>
                                            @else
                                                <span class="badge badge-{{ $sub->status }}">{{ strtoupper($sub->status) }}</span>
                                            @endif

                                            @if($sub->category)
                                                <span class="badge badge-category">🏷️ {{ ucfirst($sub->category) }}</span>
                                            @endif
                                            @if($sub->display_duration)
                                                <span class="badge badge-schedule">⏱️ {{ $sub->display_duration }}s</span>
                                            @endif

                                            {{-- Payment status badge --}}
                                            @if($sub->isPaid())
                                                <span class="badge badge-paid">💳 Paid &#8358;{{ number_format($sub->payment_amount, 0) }}</span>
                                            @elseif($sub->payment_status === 'pending')
                                                <span class="badge badge-pay-pending">⏳ Payment Pending</span>
                                            @elseif($sub->payment_status === 'failed')
                                                <span class="badge badge-unpaid">❌ Payment Failed</span>
                                            @else
                                                <span class="badge badge-unpaid">🚫 Unpaid</span>
                                            @endif
                                        </div>
                                        <h3 class="business-title">{{ $sub->business_name }}</h3>
                                    </div>
                                    <span class="card-date">{{ $sub->created_at->format('M d, H:i') }}</span>
                                </div>

                                <!-- Schedule Status -->
                                <div class="schedule-box">
                                    @if($sub->expires_at)
                                        @if($isExpired)
                                            <span style="color: #f87171;">⚠️ Expired on <strong>{{ $sub->expires_at->format('M d, Y') }}</strong></span>
                                        @else
                                            <span>📅 Active until <strong>{{ $sub->expires_at->format('M d, Y') }}</strong> ({{ $sub->expires_at->diffForHumans() }})</span>
                                        @endif
                                    @else
                                        <span>♾️ Schedule: <strong>Continuous (No expiration date)</strong></span>
                                    @endif
                                </div>

                                @if($sub->payment_reference)
                                    <div class="url-info" style="font-size: 0.8rem; color: var(--text-secondary);">
                                        <strong>Paystack Ref:</strong>
                                        <a href="https://dashboard.paystack.com/#/transactions/{{ $sub->payment_reference }}" target="_blank" rel="noopener noreferrer" class="paystack-link">🔎 {{ $sub->payment_reference }}</a>
                                        @if($sub->paid_at)
                                            <span style="color: var(--accent-green);"> · paid {{ $sub->paid_at->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                @endif

                                @if($sub->submitter_contact)
                                    <div class="contact-info">
                                        <strong>Submitter:</strong> {{ $sub->submitter_contact }}
                                    </div>
                                @endif

                                @if($sub->click_through_url)
                                    <div class="url-info">
                                        <strong>Website / Link:</strong> <a href="{{ $sub->click_through_url }}" target="_blank" rel="noopener noreferrer">🔗 {{ $sub->click_through_url }}</a>
                                    </div>
                                @endif

                                <div class="card-desc">{{ $sub->description ?: 'No description provided.' }}</div>

                                @if($sub->status === 'declined' && $sub->decline_reason)
                                    <div class="decline-box">
                                        <strong>Decline Reason:</strong> {{ $sub->decline_reason }}
                                    </div>
                                @endif

                                <div class="card-actions">
                                    <button type="button" class="btn btn-approve" onclick="openApproveModal({{ $sub->id }}, '{{ addslashes($sub->business_name) }}', '{{ $sub->expires_at ? $sub->expires_at->format('Y-m-d') : '' }}', {{ $sub->isPaid() ? 'true' : 'false' }})">
                                        {{ ($sub->status === 'approved' && !$isExpired) ? '⚙️ Reschedule' : '✓ Approve & Schedule' }}
                                    </button>

                                    @if($sub->status !== 'declined')
                                        <button class="btn btn-decline" onclick="openDeclineModal({{ $sub->id }})">✗ Decline</button>
                                    @endif

                                    <form action="{{ route('moderator.destroy', $sub->id) }}" method="POST" onsubmit="return confirm('Delete submission for {{ $sub->business_name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-delete" title="Delete record">🗑️</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </main>

    <!-- Lightbox Modal -->
    <div id="lightboxModal" class="modal" onclick="closeLightbox()">
        <img class="modal-content" id="modalImage">
        <div id="modalCaption" style="color: #fff; margin-top: 1rem; font-weight: 600;"></div>
    </div>

    <!-- Approve & Schedule Modal -->
    <div id="approveModal" class="modal">
        <div class="modal-dialog">
            <h3 id="approveModalTitle">Approve & Schedule Ad Campaign</h3>
            <p style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.25rem;">Set campaign runtime duration and expiration date:</p>
            <form id="approveForm" method="POST">
                @csrf
                <label style="font-size: 0.85rem; color: #cbd5e1; font-weight: 600;">Campaign Duration:</label>
                <select name="duration_days" id="durationSelect" onchange="toggleCustomExpiry(this.value)">
                    <option value="7">Run for 7 Days (Standard)</option>
                    <option value="14">Run for 14 Days (2 Weeks)</option>
                    <option value="30">Run for 30 Days (1 Month)</option>
                    <option value="unlimited">Continuous (No Expiry Date)</option>
                    <option value="custom">Custom End Date...</option>
                </select>

                <div id="customDateContainer" style="display: none;">
                    <label style="font-size: 0.85rem; color: #cbd5e1; font-weight: 600;">Custom Expiration Date:</label>
                    <input type="date" name="expires_at" id="customExpiryInput">
                </div>

                <div id="unpaidWarning" class="force-approve-row" style="display: none; background: rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); padding: 0.5rem 0.75rem; border-radius: 8px; margin-top: 0.6rem;">
                    <input type="checkbox" name="force_approve" id="forceApproveCheck" value="1">
                    <label for="forceApproveCheck" style="color: #fca5a5; font-weight: 600; cursor: pointer;">Force approve (unpaid / house ad)</label>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn" style="background: var(--border-color); color: #fff;" onclick="closeApproveModal()">Cancel</button>
                    <button type="submit" class="btn btn-approve" style="flex: initial; padding: 0.6rem 1.5rem;">Confirm & Make Live</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Decline Modal -->
    <div id="declineModal" class="modal">
        <div class="modal-dialog">
            <h3>Decline Advertisement</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 0.5rem;">Enter reason for rejection:</p>
            <form id="declineForm" method="POST">
                @csrf
                <textarea name="decline_reason" placeholder="e.g. Inappropriate content, invalid business data..." required></textarea>
                <div class="modal-actions">
                    <button type="button" class="btn" style="background: var(--border-color); color: #fff;" onclick="closeDeclineModal()">Cancel</button>
                    <button type="submit" class="btn" style="background: var(--accent-red); color: #fff;">Confirm Decline</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openLightbox(src, caption) {
            document.getElementById('modalImage').src = src;
            document.getElementById('modalCaption').textContent = caption;
            document.getElementById('lightboxModal').style.display = 'flex';
        }
        function closeLightbox() {
            document.getElementById('lightboxModal').style.display = 'none';
        }
        function openApproveModal(id, title, existingExpiry, isPaid) {
            document.getElementById('approveForm').action = '/submissions/' + id + '/approve';
            document.getElementById('approveModalTitle').textContent = "Approve & Schedule: " + title;
            if (existingExpiry) {
                document.getElementById('durationSelect').value = 'custom';
                document.getElementById('customExpiryInput').value = existingExpiry;
                toggleCustomExpiry('custom');
            } else {
                document.getElementById('durationSelect').value = '7';
                toggleCustomExpiry('7');
            }
            // Show the force-approve bypass only for unpaid submissions.
            document.getElementById('unpaidWarning').style.display = isPaid ? 'none' : 'block';
            document.getElementById('forceApproveCheck').checked = false;
            document.getElementById('approveModal').style.display = 'flex';
        }
        function closeApproveModal() {
            document.getElementById('approveModal').style.display = 'none';
        }
        function toggleCustomExpiry(val) {
            document.getElementById('customDateContainer').style.display = val === 'custom' ? 'block' : 'none';
        }
        function openDeclineModal(id) {
            document.getElementById('declineForm').action = '/submissions/' + id + '/decline';
            document.getElementById('declineModal').style.display = 'flex';
        }
        function closeDeclineModal() {
            document.getElementById('declineModal').style.display = 'none';
        }
    </script>
</body>
</html>
