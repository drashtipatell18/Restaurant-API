<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmaci贸n de registro de usuario</title>
</head>

<body>
    <p>Hola {{ $user->name }}!</p>
<br><br>
    <p>Te hemos dado acceso a nuestro portal para que puedas gestionar tu viaje con nosotros y conocer todas las posibilidades que ofrece Cyproapp.</p>
    <p>Si quieres iniciar sesi贸n en tu cuenta, haz clic en el siguiente enlace: <a href="https://dev.cyprosolution.com">https://dev.cyprosolution.com</a></p>
    <p>Congratulations! Your registration for the Restaurant Website is confirmed.</p>
    <p>Credenciales:</p>
    <p>Correo: {{ $user->email }} </p>

    <p>Contrase帽a : {{$plainPassword }}</p>
    <p>
    隆Disfruta!
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
