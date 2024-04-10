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
            color: #007bff;
            margin-bottom: 20px;
            font-weight: bold;
            text-align: center;
        }
        p {
            color: #555555;
            line-height: 1.6;
            margin-bottom: 20px;
            text-align: justify;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
            text-align: center;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .footer {
            margin-top: 30px;
            color: #999999;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to {{$name}}</h1>
        <p>Dear <b>{{$first_name}} {{$last_name}}</b>,</p>
        <p>We are delighted to inform you that you have been selected as an employee at {{$name}}. Your employee number is: <b>{{$employee_number}}</b>.</p>
        <p>We have chosen you because we believe your expertise and dedication will contribute significantly to our team's success.</p>
        <p>As an employee, you'll play a crucial role in helping {{$name}} achieve its goals.</p>
        <p>Here is the company's website for you to visit: <a href="{{$website}}">{{website}}</a></p>
        <p>Use the following credentials to log in and explore our platform:</p>
        <ul>
            <li><strong>Email:</strong> {{$email}}</li>
            <li><strong>Password:</strong> password</li>
        </ul>
        <p>If you have any questions or need assistance, feel free to reach out to our support team at <a href="mailto:support@example.com">support@example.com</a>.</p>
        <p class="footer">We look forward to having you on our team!<br>Best regards,<br>Team Track</p>
    </div>
</body>
</html>
