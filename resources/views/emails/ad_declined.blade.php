<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advertisement Submission Status</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #0f172a; color: #f8fafc; margin: 0; padding: 24px; }
        .card { max-width: 600px; margin: 0 auto; background-color: #1e293b; border-radius: 12px; border: 1px solid #334155; padding: 32px; }
        .header { text-align: center; border-bottom: 1px solid #334155; padding-bottom: 20px; }
        .badge { display: inline-block; background-color: #ef4444; color: #ffffff; padding: 6px 14px; border-radius: 9999px; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        h1 { color: #ffffff; font-size: 22px; margin: 16px 0 8px 0; }
        p { color: #94a3b8; font-size: 15px; line-height: 1.6; }
        .reason-box { background-color: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 18px; margin: 24px 0; color: #fca5a5; font-size: 14px; }
        .details-box { background-color: #0f172a; border-radius: 8px; padding: 18px; margin: 24px 0; }
        .row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
        .row:last-child { margin-bottom: 0; }
        .label { color: #64748b; font-weight: 500; }
        .value { color: #e2e8f0; font-weight: 600; text-align: right; }
        .footer { text-align: center; margin-top: 24px; color: #64748b; font-size: 12px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <span class="badge">Submission Declined</span>
            <h1>Update on Your Ad Submission</h1>
            <p>Thank you for submitting an ad for <strong>{{ $ad->business_name }}</strong>. After review by our team, we were unable to approve this advertisement for in-game display.</p>
        </div>

        @if(!empty($reason))
        <div class="reason-box">
            <strong>Reason for decline:</strong><br>
            {{ $reason }}
        </div>
        @endif

        <div class="details-box">
            <div class="row">
                <span class="label">Business Name:</span>
                <span class="value">{{ $ad->business_name }}</span>
            </div>
            <div class="row">
                <span class="label">Category:</span>
                <span class="value">{{ ucfirst($ad->category ?? 'General') }}</span>
            </div>
            <div class="row">
                <span class="label">Format:</span>
                <span class="value">{{ strtoupper($ad->media_type ?? 'Image') }}</span>
            </div>
        </div>

        <p style="text-align: center; font-size: 14px; color: #94a3b8;">
            Please ensure your creative complies with guidelines and resubmit with updated assets if desired.
        </p>

        <div class="footer">
            <p>Advertisement System &bull; Automatically generated notification</p>
        </div>
    </div>
</body>
</html>
