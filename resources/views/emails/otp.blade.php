<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login OTP - DMC</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .email-container {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        .otp-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 30px 0;
        }
        .otp-code {
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 8px;
            margin: 10px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #718096;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">DMC</div>
            <h2>Login Verification Code</h2>
        </div>
        
        <p>Hello {{ $name }},</p>
        
        <p>You have requested to login to your DMC account. Please use the following One-Time Password (OTP) to complete your login:</p>
        
        <div class="otp-box">
            <div style="font-size: 14px; margin-bottom: 10px;">Your OTP Code</div>
            <div class="otp-code">{{ $otp }}</div>
            <div style="font-size: 12px; margin-top: 10px;">This code will expire in 10 minutes</div>
        </div>
        
        <p>If you did not request this OTP, please ignore this email or contact support if you have concerns.</p>
        
        <p>For security reasons, never share this code with anyone.</p>
        
        <div class="footer">
            <p>© {{ date('Y') }} DMC. All rights reserved.</p>
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>

