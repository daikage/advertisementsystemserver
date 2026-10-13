<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Team & Role Management (RBAC) — Ad Moderator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #0f172a;
            --bg-card: #1e293b;
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
        .api-link {
            font-size: 0.85rem; color: var(--accent-blue);
            text-decoration: none; padding: 0.35rem 0.75rem;
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid rgba(56, 189, 248, 0.2);
            border-radius: 6px;
        }
        .container { max-width: 1100px; margin: 2rem auto; padding: 0 1.5rem; }
        .alert-success {
            background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac; padding: 0.75rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem;
        }
        .alert-danger {
            background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5; padding: 0.75rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem;
        }
        .grid-layout { display: grid; grid-template-columns: 1fr 340px; gap: 2rem; }
        @media (max-width: 800px) { .grid-layout { grid-template-columns: 1fr; } }
        .card {
            background: var(--bg-card); border: 1px solid var(--border-color);
            border-radius: 12px; padding: 1.5rem;
        }
        .card-title { font-size: 1.15rem; font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 0.75rem 1rem; border-bottom: 1px solid rgba(255, 255, 255, 0.06); font-size: 0.9rem; }
        th { color: var(--text-secondary); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; }
        .role-badge {
            display: inline-block; font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 4px; text-transform: uppercase;
        }
        .role-admin { background: rgba(168, 85, 247, 0.2); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
        .role-moderator { background: rgba(56, 189, 248, 0.2); color: #7dd3fc; border: 1px solid rgba(56, 189, 248, 0.3); }
        .role-viewer { background: rgba(148, 163, 184, 0.2); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.3); }
        .form-group { margin-bottom: 1rem; }
        label { display: block; font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.35rem; font-weight: 500; }
        input, select {
            width: 100%; background: #0f172a; border: 1px solid var(--border-color);
            color: #fff; padding: 0.65rem 0.85rem; border-radius: 8px; font-size: 0.9rem; outline: none;
        }
        input:focus, select:focus { border-color: var(--accent-blue); }
        .btn-primary {
            width: 100%; background: var(--accent-blue); color: #0b0f19; font-weight: 700;
            padding: 0.75rem; border: none; border-radius: 8px; cursor: pointer; transition: opacity 0.2s;
        }
        .btn-primary:hover { opacity: 0.9; }
        .btn-sm-danger {
            background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5; padding: 0.25rem 0.6rem; border-radius: 6px; font-size: 0.75rem; cursor: pointer;
        }
        .btn-sm-danger:hover { background: rgba(239, 68, 68, 0.4); }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="logo">
            <span style="font-size: 1.5rem">👥</span>
            <h1>AdModerator <span>Team & RBAC</span></h1>
        </div>
        <div class="header-right">
            <a href="{{ route('moderator.index') }}" class="api-link">← Back to Dashboard</a>
            <span style="font-size: 0.85rem; color: var(--text-secondary);">👤 {{ Auth::user()->name }} (Admin)</span>
        </div>
    </header>

    <main class="container">
        @if(session('success'))
            <div class="alert-success">✓ {{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert-danger">
                @foreach($errors->all() as $err)
                    <div>⚠️ {{ $err }}</div>
                @endforeach
            </div>
        @endif

        <div class="grid-layout">
            <!-- Left: Users List -->
            <div class="card">
                <div class="card-title">
                    <span>Team Members</span>
                    <span style="font-size: 0.85rem; font-weight: normal; color: var(--text-secondary);">({{ $users->total() }} users)</span>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Change Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $u)
                        <tr>
                            <td>
                                <div style="font-weight: 600; color: #fff;">{{ $u->name }}</div>
                                <div style="font-size: 0.8rem; color: var(--text-secondary);">{{ $u->email }}</div>
                            </td>
                            <td>
                                <span class="role-badge role-{{ $u->role }}">
                                    {{ $u->role }}
                                </span>
                            </td>
                            <td>
                                <form action="{{ route('users.updateRole', $u->id) }}" method="POST" style="display: flex; gap: 0.4rem;">
                                    @csrf
                                    <select name="role" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; width: auto;" onchange="this.form.submit()">
                                        <option value="admin" {{ $u->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                        <option value="moderator" {{ $u->role === 'moderator' ? 'selected' : '' }}>Moderator</option>
                                        <option value="viewer" {{ $u->role === 'viewer' ? 'selected' : '' }}>Viewer</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                @if($u->id !== Auth::id())
                                <form action="{{ route('users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Remove user {{ $u->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-sm-danger">Remove</button>
                                </form>
                                @else
                                <span style="font-size: 0.75rem; color: var(--text-secondary);">(Current User)</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Right: Add User Form -->
            <div class="card" style="height: fit-content;">
                <div class="card-title">➕ Add Team Member</div>
                <form action="{{ route('users.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" required placeholder="John Doe">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required placeholder="john@company.com">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required placeholder="••••••••">
                    </div>
                    <div class="form-group">
                        <label>Access Role</label>
                        <select name="role" required>
                            <option value="moderator">Moderator (Approve/Decline/Renew)</option>
                            <option value="viewer">Viewer (Read-Only Analytics)</option>
                            <option value="admin">Admin (Full Access + User Management)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary">Create User</button>
                </form>

                <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border-color); font-size: 0.8rem; color: var(--text-secondary);">
                    <strong style="color: #fff;">Role Privileges:</strong>
                    <ul style="margin-left: 1rem; margin-top: 0.5rem; line-height: 1.6;">
                        <li><strong>Admin:</strong> Approve/decline ads, renew, delete, manage users.</li>
                        <li><strong>Moderator:</strong> Approve/decline ads, renew campaigns, view analytics.</li>
                        <li><strong>Viewer:</strong> Read-only view of submissions & telemetry.</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
