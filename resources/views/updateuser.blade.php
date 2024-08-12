<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de registro de usuario</title>
</head>

<body>
    <p>Hola {{ $user->name }}!</p>
<br><br>
    <p>We wanted to inform you that your login credentials for Cyproapp have been updated by our team.</p>
    <p>Congratulations! Your registration for the Restaurant Website is confirmed.</p>
    <p>Updated Credentials:</p>
    <p>Credenciales:</p>
    <p>Correo: {{ $user->email }} </p>

    <p>Contraseña : {{$plainPassword }}</p>
    <p>
    ¡Disfruta!
    </p>
    <br><br><br>
<p>
Un saludo,
</p>
<p>

    El equipo de Cyproapp
</p>
</body>

</html>