<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $appName }} - Your Staff Account</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            padding: 20px 0;
            background: linear-gradient(135deg, #8d6e63, #6d4c41);
            color: white;
            border-radius: 10px 10px 0 0;
            margin: -20px -20px 20px -20px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .content {
            padding: 20px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #5d4037;
        }
        h3.section-title {
            color: #5d4037;
            border-bottom: 2px solid #8d6e63;
            padding-bottom: 5px;
            margin-top: 30px;
            margin-bottom: 15px;
            text-transform: uppercase;
            font-size: 16px;
        }
        .note {
            background: #fff8e1;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
            margin: 20px 0;
            font-size: 14px;
            color: #7a5a00;
        }
        .password-section {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #4caf50;
        }
        .password-item {
            margin-bottom: 8px;
        }
        .password-label {
            font-weight: bold;
            color: #2e7d32;
            margin-bottom: 5px;
        }
        .password-value {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            color: #1b5e20;
            background: white;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #4caf50;
            text-align: center;
            letter-spacing: 2px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e8d6c9;
            color: #8d6e63;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{ $appName }}</div>
            <h1>Staff Account Created</h1>
        </div>

        <div class="content">
            <div class="greeting">
                Hello <strong>{{ $staffAccount->first_name }} {{ $staffAccount->last_name }}</strong>,
            </div>

            <p>Welcome to the team! An owner has created a staff account for you for {{ $appName }}.</p>
            <p>Please use the following credentials to log in to the system. You will be prompted to change your password upon your first login for security.</p>

            <!-- Account Information -->
            <h3 class="section-title">Your Account Information</h3>
            <div class="password-section">
                <div class="note">
                    <p><strong>Login Email:</strong> {{ $staffAccount->email }}</p>
                </div>
                <div class="password-item">
                    <div class="password-label">Your Auto-Generated Password:</div>
                    <div class="password-value">{{ $generatedPassword }}</div>
                </div>
            </div>

            <p>If you have any questions, please contact your administrator.</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html> 