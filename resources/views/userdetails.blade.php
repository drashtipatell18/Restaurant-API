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
    
    <table border="1">
        <tr>
            <td style="padding: 10px;">Username</td>
            <td style="padding: 10px;">{{ $user->email }}</td>
        </tr>
        <tr>
            <td style="padding: 10px;">Password</td>
            <td style="padding: 10px;">{{ $plainPassword }}</td>
        </tr>
    </table>

    <p>Thank you!</p>
</body>

</html>
