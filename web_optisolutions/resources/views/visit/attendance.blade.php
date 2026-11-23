<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Confirm your visit - PolyClinic Lipa</title>
    <style>
        body { margin:0; padding:24px 12px; background:#f1f5f9; color:#334155; font-family:Arial,Helvetica,sans-serif; }
        .card { max-width:480px; margin:0 auto; background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:24px; border-top:4px solid #0E62AA; }
        h1 { margin:0 0 12px; color:#062744; font-size:22px; line-height:1.3; }
        .details { background:#eef6fc; border-radius:8px; padding:14px 16px; margin:16px 0; font-size:15px; line-height:1.6; }
        form { margin:0; }
        button { display:block; width:100%; padding:14px; margin:0 0 10px; border-radius:6px; font-size:16px; font-weight:bold; cursor:pointer; }
        .yes { background:#0E62AA; border:1px solid #0E62AA; color:#fff; }
        .no { background:#fff; border:1px solid #b42318; color:#b42318; }
        .msg { padding:12px 14px; border-radius:8px; margin:0 0 16px; font-size:15px; }
        .ok { background:#dcf5e4; color:#15803d; }
        .warn { background:#fde2e2; color:#b42318; }
        .note { color:#64748b; font-size:13px; margin:12px 0 0; }
    </style>
</head>
<body>
<div class="card">
    <h1>Confirm your visit</h1>

    @if ($closed)
        <p class="msg warn">{{ $closed }}</p>
    @else
        <p style="margin:0;">Hi {{ $firstName }}, will you be coming to your schedule visit?</p>
        <div class="details">
            <strong>Date and time:</strong> {{ $schedule }} (Philippine time)<br>
            <strong>Service:</strong> {{ $service }}<br>
            @if ($doctor)<strong>Doctor:</strong> {{ $doctor }}@endif
        </div>

        @if ($response === 'confirmed')
            <p class="msg ok">Thank you! You are confirmed. We look forward to seeing you.</p>
        @elseif ($response === 'declined')
            <p class="msg warn">We noted that you can't come. To book another time, please use our chatbot.</p>
        @endif

        <form method="POST" action="{{ request()->fullUrl() }}">
            @csrf
            @if ($response !== 'confirmed')
                <button type="submit" name="response" value="confirmed" class="yes">Yes, I'm coming</button>
            @endif
            @if ($response !== 'declined')
                <button type="submit" name="response" value="declined" class="no">I can't come</button>
            @endif
        </form>
        <p class="note">You can change your answer on this page until your visit is over.</p>
    @endif
</div>
</body>
</html>