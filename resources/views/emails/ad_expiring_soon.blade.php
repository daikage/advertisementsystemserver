<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ad Campaign Expiring Soon</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #0f172a; color: #f8fafc; margin: 0; padding: 24px; }
        .card { max-width: 600px; margin: 0 auto; background-color: #1e293b; border-radius: 12px; border: 1px solid #334155; padding: 32px; }
        .header { text-align: center; border-bottom: 1px solid #334155; padding-bottom: 20px; }
        .badge { display: inline-block; background-color: #f59e0b; color: #ffffff; padding: 6px 14px; border-radius: 9999px; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        h1 { color: #ffffff; font-size: 22px; margin: 16px 0 8px 0; }
        p { color: #94a3b8; font-size: 15px; line-height: 1.6; }
        .details-box { background-color: #0f172a; border-radius: 8px; padding: 18px; margin: 24px 0; }
        .row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
        .row:last-child { margin-bottom: 0; }
        .label { color: #64748b; font-weight: 500; }
        .value { color: #e2e8f0; font-weight: 600; text-align: right; }
        .button { display: inline-block; background: linear-gradient(135deg, #f59e0b, #d97706); color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-weight: 600; font-size: 15px; text-align: center; margin-top: 10px; }
        .footer { text-align: center; margin-top: 24px; color: #64748b; font-size: 12px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <span class="badge">Expiring Soon</span>
            <h1>Keep Your Billboard Campaign Running</h1>
            <p>Your advertisement campaign for <strong>{{ $ad->business_name }}</strong> is scheduled to expire in approximately 24 hours.</p>
        </div>

        <div class="details-box">
            <div class="row">
                <span class="label">Campaign:</span>
                <span class="value">{{ $ad->business_name }}</span>
            </div>
            <div class="row">
                <span class="label">Expiration Date:</span>
                <span class="value" style="color: #fca5a5;">{{ $ad->expires_at ? $ad->expires_at->format('M d, Y H:i') : 'Within 24h' }}</span>
            </div>
            <div class="row">
                <span class="label">Total Impressions:</span>
                <span class="value">{{ $ad->events()->where('event_type', 'impression')->count() }}</span>
            </div>
        </div>

        <p style="text-align: center;">Renew your campaign now to maintain continuous placement on high-traffic billboards without interruption.</p>

        <div style="text-align: center; margin-top: 20px;">
            <a href="{{ $portalUrl }}" class="button">
                Renew Campaign Online
            </a>
        </div>

        <div class="footer">
            <p>Advertisement System &bull; Automatically generated notification</p>
        </div>
    </div>
</body>
</html>
