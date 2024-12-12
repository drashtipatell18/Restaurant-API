<!--<!DOCTYPE html>-->
<!--<html lang="en">-->

<!--<head>-->
<!--    <meta charset="UTF-8">-->
<!--    <meta http-equiv="X-UA-Compatible" content="IE=edge">-->
<!--    <meta name="viewport" content="width=device-width, initial-scale=1.0">-->
<!--    <title>User Registration Confirmation</title>-->
<!--</head>-->

<!--<body>-->
<!--    <p>Hello {{ $user->name }},</p>-->

<!--    <p>Congratulations! Your registration for the Restaurant Website is confirmed.</p>-->
    
<!--    <p>To set your password, please click on the link below:</p>-->
    
<!--    <p>-->
<!--        <a href="{{ url('api/set-password/' . $user->id ) }}">Set Your Password</a>-->
<!--    </p>-->

<!--    <p>If the above link doesn't work, copy and paste the following URL into your browser:</p>-->
    
<!--    <p>{{ url('api/set-password/' . $user->id ) }}</p>-->

<!--    <p>This link will expire in 24 hours for security reasons.</p>-->

<!--    <p>If you didn't request this registration, please ignore this email.</p>-->

<!--    <p>Thank you!</p>-->
<!--</body>-->

<!--</html>-->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Registro de Usuario</title>
</head>

<body>
    <p>Hola {{ $user->name }},</p>

    <p>¡Felicidades! Su registro para el sitio web del restaurante está confirmado.</p>
    
    <p>Para establecer su contraseña, haga clic en el siguiente enlace:</p>
    
    <p>
        <a href="https://dev.cyproapp.com/enlaceAdmin/pass/{{$user->id}}">Establecer su Contraseña</a>
    </p>

    <p>Si el enlace anterior no funciona, copie y pegue la siguiente URL en su navegador:</p>
    
    <p>https://dev.cyproapp.com/enlaceAdmin/pass/{{$user->id}}</p>

    <p>Este enlace caducará en 24 horas por razones de seguridad.</p>

    <p>Si no solicitó este registro, ignore este correo electrónico.</p>

    <p>¡Gracias!</p>
</body>

</html>
