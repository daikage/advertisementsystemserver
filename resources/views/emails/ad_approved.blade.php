<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advertisement Approved</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #0f172a; color: #f8fafc; margin: 0; padding: 24px; }
        .card { max-width: 600px; margin: 0 auto; background-color: #1e293b; border-radius: 12px; border: 1px solid #334155; padding: 32px; }
        .header { text-align: center; border-bottom: 1px solid #334155; padding-bottom: 20px; }
        .badge { display: inline-block; background-color: #10b981; color: #ffffff; padding: 6px 14px; border-radius: 9999px; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        h1 { color: #ffffff; font-size: 22px; margin: 16px 0 8px 0; }
        p { color: #94a3b8; font-size: 15px; line-height: 1.6; }
        .details-box { background-color: #0f172a; border-radius: 8px; padding: 18px; margin: 24px 0; }
        .row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
        .row:last-child { margin-bottom: 0; }
        .label { color: #64748b; font-weight: 500; }
        .value { color: #e2e8f0; font-weight: 600; text-align: right; }
        .button { display: inline-block; background: linear-gradient(135deg, #0284c7, #2563eb); color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-weight: 600; font-size: 15px; text-align: center; margin-top: 10px; }
        .footer { text-align: center; margin-top: 24px; color: #64748b; font-size: 12px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <span class="badge">Approved & Live</span>
            <h1>Good News! Your Ad is Live</h1>
            <p>Your advertisement for <strong>{{ $ad->business_name }}</strong> has been approved by our moderation team and is now actively rotating on in-game billboards.</p>
        </div>

        <div class="details-box">
            <div class="row">
                <span class="label">Business / Brand:</span>
                <span class="value">{{ $ad->business_name }}</span>
            </div>
            <div class="row">
                <span class="label">Category:</span>
                <span class="value">{{ ucfirst($ad->category ?? 'General') }}</span>
            </div>
            <div class="row">
                <span class="label">Media Format:</span>
                <span class="value">{{ strtoupper($ad->media_type ?? 'Image') }}</span>
            </div>
            <div class="row">
                <span class="label">Starts At:</span>
                <span class="value">{{ $ad->starts_at ? $ad->starts_at->format('M d, Y H:i') : 'Immediate' }}</span>
            </div>
            <div class="row">
                <span class="label">Expires At:</span>
                <span class="value">{{ $ad->expires_at ? $ad->expires_at->format('M d, Y H:i') : 'Open Ended' }}</span>
            </div>
        </div>

        @if($ad->payment_reference)
        <div style="text-align: center; margin-top: 20px;">
            <a href="{{ url('/advertiser/status/' . $ad->payment_reference) }}" class="button">
                View Live Advertiser Analytics
            </a>
        </div>
        @endif

        <div class="footer">
            <p>Advertisement System &bull; Automatically generated notification</p>
        </div>
    </div>
</body>
</html>
