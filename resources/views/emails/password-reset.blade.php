<!DOCTYPE html>
<html>
<head>
    <title>Password Reset - Linkud Hub</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4A2C1D; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 30px; border-radius: 5px; }
        .button { 
            display: inline-block; 
            padding: 12px 24px; 
            background-color: #7F5539; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
            margin: 20px 0; 
        }
        .button:hover { background-color: #4A2C1D; }
        .footer { 
            margin-top: 20px; 
            padding-top: 20px; 
            border-top: 1px solid #ddd; 
            text-align: center; 
            color: #666; 
            font-size: 12px; 
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Linkud Hub</h1>
        </div>
        
        <div class="content">
            <h2>Password Reset Request</h2>
            <p>Hello {{ $userName ?? 'User' }},</p>
            
            <p>You are receiving this email because we received a password reset request for your account.</p>
            
            <div style="text-align: center;">
                <a href="{{ $resetLink }}" class="button">Reset Password</a>
            </div>
            
            <p>This password reset link will expire in {{ $expiresIn ?? 60 }} minutes.</p>
            
            <p>If you did not request a password reset, no further action is required.</p>
            
            <p style="color: #666; font-size: 14px;">
                If you're having trouble clicking the "Reset Password" button, 
                copy and paste the URL below into your web browser:
                <br>
                <a href="{{ $resetLink }}" style="color: #7F5539; word-break: break-all;">{{ $resetLink }}</a>
            </p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ $currentYear ?? date('Y') }} Linkud Hub. All rights reserved.</p>
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>