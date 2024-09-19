<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Notifications</h1>
        <div id="notifications" class="mt-3"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@7.0.0/dist/web/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.11.3/dist/echo.iife.js"></script>

    <script>
        window.Echo = new Echo({
            broadcaster: "pusher",
            key: "GoofNBCH",
            cluster: "mt1",
            wsHost: window.location.hostname,
            wsPort: 6001,
            forceTLS: false,
            disableStats: false,
            enabledTransports: ['ws', 'wss'],
        });

        window.Echo.channel('notifications')
            .listen('NotificationMessage', (event) => {
                console.log('New notification received:', event.notification);

                // Create a new notification element
                const notificationElement = document.createElement('div');
                notificationElement.className = 'alert alert-info';
                notificationElement.textContent = event.notification;

                // Append the notification to the container
                document.getElementById('notifications').appendChild(notificationElement);
            });
    </script>
</body>
</html>
