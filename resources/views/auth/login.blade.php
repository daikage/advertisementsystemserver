<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderator Login — Unity Ad System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #0a0f1d;
            --bg-card: rgba(22, 33, 54, 0.75);
            --border-color: rgba(56, 189, 248, 0.2);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --accent-blue: #38bdf8;
            --accent-green: #22c55e;
            --accent-red: #ef4444;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
            background: radial-gradient(circle at 50% 20%, #172554 0%, #0a0f1d 75%);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-card {
            background: var(--bg-card);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            width: 100%;
            max-width: 440px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 0 40px rgba(56, 189, 248, 0.1);
        }
        .header { text-align: center; margin-bottom: 2rem; }
        .logo-icon {
            font-size: 2.2rem;
            display: inline-block;
            margin-bottom: 0.5rem;
            filter: drop-shadow(0 0 12px rgba(56, 189, 248, 0.4));
        }
        .header h1 { font-size: 1.5rem; font-weight: 700; }
        .header h1 span { color: var(--accent-blue); }
        .header p { color: var(--text-secondary); font-size: 0.9rem; margin-top: 0.35rem; }

        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem; color: #cbd5e1; }
        .form-input {
            width: 100%;
            background: rgba(10, 15, 29, 0.8);
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            color: #fff;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.15s ease;
        }
        .form-input:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.88rem;
            margin-bottom: 1.25rem;
        }
        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.88rem;
            margin-bottom: 1.25rem;
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            border: none;
            border-radius: 8px;
            padding: 0.85rem;
            color: #fff;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: transform 0.1s ease, filter 0.15s ease;
            margin-top: 0.5rem;
        }
        .btn-submit:hover { filter: brightness(1.15); transform: translateY(-1px); }

        .default-creds {
            margin-top: 1.75rem;
            padding: 0.85rem 1rem;
            background: rgba(56, 189, 248, 0.06);
            border: 1px dashed rgba(56, 189, 248, 0.3);
            border-radius: 8px;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
        .default-creds strong { color: var(--accent-blue); }
        .creds-row { margin-top: 0.25rem; font-family: monospace; color: #e2e8f0; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="header">
            <div class="logo-icon">🛡️</div>
            <h1>AdModerator <span>Login</span></h1>
            <p>Sign in to manage in-game ads and review submissions</p>
        </div>

        @if(session('success'))
            <div class="alert-success">✓ {{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert-error">
                ✗ {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf

            <div class="form-group">
                <label class="form-label" for="email">Moderator Email</label>
                <input type="email" id="email" name="email" class="form-input" value="{{ old('email', 'admin@adsystem.local') }}" required autofocus autocomplete="email">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-input" value="admin123" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn-submit">Sign In to Dashboard →</button>
        </form>

        <div class="default-creds">
            <strong>Default Moderator Credentials:</strong>
            <div class="creds-row">• Email: admin@adsystem.local</div>
            <div class="creds-row">• Password: admin123</div>
        </div>
    </div>
</body>
</html>
