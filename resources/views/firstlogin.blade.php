<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration Confirmation</title>
</head>

<body>
    <p>Hello {{ $user->name }},</p>

    <p>Congratulations! Your registration for the Restaurant Website is confirmed.</p>
    
    <p>To set your password, please click on the link below:</p>
    
    <p>
        <a href="{{ url('api/set-password/' . $user->id ) }}">Set Your Password</a>
    </p>

    <p>If the above link doesn't work, copy and paste the following URL into your browser:</p>
    
    <p>{{ url('api/set-password/' . $user->id ) }}</p>

    <p>This link will expire in 24 hours for security reasons.</p>

    <p>If you didn't request this registration, please ignore this email.</p>

    <p>Thank you!</p>
</body>

</html>