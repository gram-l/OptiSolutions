<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: 'Segoe UI', sans-serif; background: #f4f6fb; margin: 0; padding: 0; }
  .wrapper { max-width: 480px; margin: 40px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
  .header { background: linear-gradient(135deg, #0D3B72, #0A2A52); padding: 32px 24px; text-align: center; }
  .header h1 { color: #fff; margin: 0; font-size: 22px; letter-spacing: .5px; }
  .header p  { color: rgba(255,255,255,.7); margin: 6px 0 0; font-size: 13px; }
  .body { padding: 32px 28px; }
  .body p { color: #4a5568; font-size: 14px; line-height: 1.6; margin: 0 0 20px; }
  .otp-box { background: #f4f6fb; border: 2px dashed #0D3B72; border-radius: 10px; text-align: center; padding: 20px; margin: 24px 0; }
  .otp-box .code { font-size: 38px; font-weight: 700; letter-spacing: 10px; color: #0D3B72; }
  .otp-box small { display: block; color: #8a8fa3; font-size: 12px; margin-top: 6px; }
  .footer { background: #f4f6fb; padding: 16px 28px; text-align: center; }
  .footer p { color: #8a8fa3; font-size: 11px; margin: 0; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>🏥 PolyClinic</h1>
    <p>Inquiry System · Smart Care</p>
  </div>
  <div class="body">
    <p>Hello,</p>
    <p>We received a request to reset your PolyClinic staff account password. Use the code below to continue. It expires in <strong>10 minutes</strong>.</p>

    <div class="otp-box">
      <div class="code">{{ $otp }}</div>
      <small>One-time password — do not share this with anyone</small>
    </div>

    <p>If you did not request a password reset, you can safely ignore this email. Your password will not change.</p>
  </div>
  <div class="footer">
    <p>© PolyClinic Health — secure portal &nbsp;|&nbsp; This is an automated message</p>
  </div>
</div>
</body>
</html>
