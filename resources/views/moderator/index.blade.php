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
            background: rgba(30, 41, 59, 0.8);
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
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1.5rem; }
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
        .stat-card.declined .stat-num { color: var(--accent-red); }
        .stat-card.total .stat-num { color: var(--accent-blue); }

        .filter-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.75rem; }
        .tab-group { display: flex; background: var(--bg-card); padding: 0.25rem; border-radius: 8px; border: 1px solid var(--border-color); }
        .tab-btn {
            padding: 0.5rem 1rem; font-size: 0.9rem; font-weight: 500;
            color: var(--text-secondary); text-decoration: none; border-radius: 6px;
        }
        .tab-btn.active { color: var(--text-primary); background: rgba(255, 255, 255, 0.08); }
        
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
        }
        .card-image-wrapper {
            position: relative; width: 100%; height: 200px;
            background: #090d16; cursor: pointer; overflow: hidden;
        }
        .card-image-wrapper img { width: 100%; height: 100%; object-fit: cover; }
        .zoom-badge {
            position: absolute; bottom: 8px; right: 8px;
            background: rgba(0, 0, 0, 0.75); color: #fff;
            font-size: 0.75rem; padding: 3px 8px; border-radius: 4px;
        }
        .card-content { padding: 1.25rem; display: flex; flex-direction: column; flex-grow: 1; }
        .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; }
        .business-title { font-size: 1.2rem; font-weight: 700; margin-top: 0.25rem; }
        .card-date { font-size: 0.8rem; color: var(--text-secondary); }
        
        .badge { font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 4px; }
        .badge-pending { background: rgba(245, 158, 11, 0.15); color: var(--accent-orange); border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-approved { background: rgba(34, 197, 94, 0.15); color: var(--accent-green); border: 1px solid rgba(34, 197, 94, 0.3); }
        .badge-declined { background: rgba(239, 68, 68, 0.15); color: var(--accent-red); border: 1px solid rgba(239, 68, 68, 0.3); }

        .contact-info { font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.5rem; }
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
        .modal-dialog textarea {
            width: 100%; height: 90px; background: #0f172a; border: 1px solid var(--border-color);
            color: #fff; padding: 0.75rem; border-radius: 8px; margin: 1rem 0;
        }
        .modal-actions { display: flex; justify-content: flex-end; gap: 0.75rem; }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="logo">
            <span style="font-size: 1.5rem">⚡</span>
            <h1>AdModerator <span>(Laravel)</span></h1>
        </div>
        <div class="header-right">
            <div class="pulse-indicator"></div>
            <span style="font-size: 0.85rem; color: var(--text-secondary);">Laravel REST API Active</span>
            <a href="{{ url('/api/ads') }}" target="_blank" class="api-link">View Approved Ads API</a>
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
                    <span class="stat-label">Pending Approval</span>
                </div>
            </div>
            <div class="stat-card approved">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <span class="stat-num">{{ $counts['approved'] }}</span>
                    <span class="stat-label">Approved (In-Game)</span>
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
                    <span class="stat-label">Total Submissions</span>
                </div>
            </div>
        </section>

        <!-- Filters -->
        <div class="filter-bar">
            <div class="tab-group">
                <a href="{{ route('moderator.index', ['filter' => 'pending']) }}" class="tab-btn {{ $filter === 'pending' ? 'active' : '' }}">Pending ({{ $counts['pending'] }})</a>
                <a href="{{ route('moderator.index', ['filter' => 'approved']) }}" class="tab-btn {{ $filter === 'approved' ? 'active' : '' }}">Approved ({{ $counts['approved'] }})</a>
                <a href="{{ route('moderator.index', ['filter' => 'declined']) }}" class="tab-btn {{ $filter === 'declined' ? 'active' : '' }}">Declined ({{ $counts['declined'] }})</a>
                <a href="{{ route('moderator.index', ['filter' => 'all']) }}" class="tab-btn {{ $filter === 'all' ? 'active' : '' }}">All Submissions</a>
            </div>
            <button onclick="location.reload()" class="btn" style="background: var(--bg-card); color: #fff; border: 1px solid var(--border-color);">🔄 Refresh</button>
        </div>

        <!-- Submissions Grid -->
        <section class="submissions-list">
            @if($submissions->isEmpty())
                <div class="empty-state">
                    <div style="font-size: 3rem; margin-bottom: 0.5rem;">📭</div>
                    <h3>No {{ $filter }} submissions found</h3>
                    <p style="color: var(--text-secondary); margin-top: 0.5rem;">New ads submitted by players in-game will appear here automatically.</p>
                </div>
            @else
                <div class="cards-grid">
                    @foreach($submissions as $sub)
                        <div class="submission-card">
                            <div class="card-image-wrapper" onclick="openLightbox('{{ asset('storage/' . $sub->image_path) }}', '{{ $sub->business_name }}')">
                                <img src="{{ asset('storage/' . $sub->image_path) }}" alt="{{ $sub->business_name }}" loading="lazy">
                                <div class="zoom-badge">🔍 Zoom Image</div>
                            </div>
                            <div class="card-content">
                                <div class="card-header">
                                    <div>
                                        <span class="badge badge-{{ $sub->status }}">{{ strtoupper($sub->status) }}</span>
                                        <h3 class="business-title">{{ $sub->business_name }}</h3>
                                    </div>
                                    <span class="card-date">{{ $sub->created_at->format('M d, H:i') }}</span>
                                </div>

                                @if($sub->submitter_contact)
                                    <div class="contact-info">
                                        <strong>Submitter:</strong> {{ $sub->submitter_contact }}
                                    </div>
                                @endif

                                <div class="card-desc">{{ $sub->description ?: 'No description provided.' }}</div>

                                @if($sub->status === 'declined' && $sub->decline_reason)
                                    <div class="decline-box">
                                        <strong>Decline Reason:</strong> {{ $sub->decline_reason }}
                                    </div>
                                @endif

                                <div class="card-actions">
                                    @if($sub->status !== 'approved')
                                        <form action="{{ route('moderator.approve', $sub->id) }}" method="POST" style="flex: 1;">
                                            @csrf
                                            <button type="submit" class="btn btn-approve" style="width: 100%;">✓ Approve</button>
                                        </form>
                                    @endif

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
