<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Two-Factor Authentication Code</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
        }
        .header {
            background-color: #4A2C1D;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            padding: 30px;
            background-color: #f9f9f9;
        }
        .code-container {
            background-color: #ffffff;
            border: 2px solid #4A2C1D;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .verification-code {
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 5px;
            color: #4A2C1D;
            margin: 10px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #eee;
        }
        .expiry-note {
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 10px;
            margin: 20px 0;
            font-size: 14px;
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo img {
            max-width: 150px;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Two-Factor Authentication</h1>
        </div>
        
        <div class="content">
            <div class="logo">
                <h2 style="color: #4A2C1D; margin: 0;">Linkud Hub</h2>
            </div>
            
            <p>Hello {{ $name }},</p>
            
            <p>You have requested to log in to your Linkud Hub account. To complete your login, please use the verification code below:</p>
            
            <div class="code-container">
                <p style="margin: 0 0 10px 0; color: #666;">Your verification code is:</p>
                <div class="verification-code">{{ $code }}</div>
                <p style="margin: 10px 0 0 0; color: #666; font-size: 14px;">This code is valid for {{ $expires }} minutes</p>
            </div>
            
            <div class="expiry-note">
                <strong>Note:</strong> For security reasons, this code will expire in {{ $expires }} minutes. If you did not request this code, please ignore this email or contact support.
            </div>
            
            <p>If you're having trouble with the code, you can:</p>
            <ul>
                <li>Wait for the code to expire and request a new one</li>
                <li>Check that you entered the correct email address</li>
                <li>Contact our support team if problems persist</li>
            </ul>
            
            <p>Thank you,<br>The Linkud Hub Team</p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Linkud Hub. All rights reserved.</p>
            <p>This is an automated message, please do not reply to this email.</p>
            <p>If you have any questions, contact us at: support@linkudhub.com</p>
        </div>
    </div>
</body>
</html>