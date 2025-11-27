<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background-color: #4a90e2;
            color: #fff;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4a90e2;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #357abd;
        }
        .footer {
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            background-color: #f9f9f9;
            border-top: 1px solid #eee;
        }
        .link-text {
            word-break: break-all;
            color: #4a90e2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome, <?php echo htmlspecialchars($name); ?>!</h1>
        </div>
        <div class="content">
            <p>Thank you for registering with us. To complete your account setup and access the customer portal, please verify your email address.</p>
            
            <div style="text-align: center;">
                <a href="<?php echo $verification_link; ?>" class="button">Verify Email Address</a>
            </div>
            
            <p>If the button above doesn't work, please copy and paste the following link into your browser:</p>
            <p><a href="<?php echo $verification_link; ?>" class="link-text"><?php echo $verification_link; ?></a></p>
            
            <p>This link will expire in 24 hours.</p>
            
            <p>If you didn't create an account, you can safely ignore this email.</p>
        </div>
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo isset($company_name) ? htmlspecialchars($company_name) : 'ERP System'; ?>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
