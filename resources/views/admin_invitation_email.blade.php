<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Mail</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333333;
            margin-bottom: 20px;
            font-weight: bold;
        }
        p {
            color: #555555;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .footer {
            margin-top: 30px;
            color: #999999;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Hello, {{ $first_name }} {{ $last_name }}!</h1>
        <p>You've been personally selected to become a Company Admin at {{$company}}. We believe your expertise and leadership will greatly contribute to our success!</p>
        <p>As a Company Admin, you'll have access to powerful tools to manage {{$company}} efficiently and effectively.</p>
        <p>Use the following credentials to log in and explore our platform:</p>
        <ul>
            <li><strong>Email:</strong> {{ $email }}</li>
            <li><strong>Password:</strong> password</li>
        </ul>

        <p>Click the button below to reset the password:</p>
        <a href="{{ $resetLink }}" class="btn">Reset Password</a>

        <p>If you have any questions or need assistance, feel free to reach out to our support team at <a href="mailto:admin@company.com">admin@company.com</a>.</p>
        <p class="footer">Looking forward to seeing you on board!<br>Best regards,<br>Team EMS</p>
    </div>
</body>
</html>
