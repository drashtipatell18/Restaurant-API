<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>User Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body, html {
            height: 100%;
            margin: 0;
        }
        .bg-custom {
            background-color: #f8f9fa; /* Change this to your preferred background color */
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .card-custom {
            max-width: 400px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="bg-custom">
        <div class="card card-custom border border-light-subtle rounded-3 shadow-sm">
            <div class="card-body p-3 p-md-4 p-xl-5">
                <div class="text-center mb-3">
                    <a href="#!">
                        <img src="./images/SocketTVLogo.jpg" alt="BootstrapBrain Logo" width="175" height="57">
                    </a>
                </div>
                <h2 class="fs-6 fw-normal text-center text-secondary mb-4">Sign in to your account</h2>
                @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                <form action="#" id="loginFrm" method="POST">
                    @csrf
                    <div class="row gy-2 overflow-hidden">
                        <div class="col-12">
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" name="email" id="email" required>
                                <label for="email" class="form-label">Email</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-grid my-3">
                                <button class="btn btn-primary btn-lg" name="submit" type="submit">Log in</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/pusher-js@7.0.0/dist/web/pusher.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.11.3/dist/echo.iife.js"></script>

    <script>
        $("#loginFrm").submit(function(e){
            e.preventDefault();
            $.ajax({
                type: "POST",
                method: "POST",
                dataType: "JSON",
                data: {"email": $("#email").val()},
                url: "/api/chat/login",
                success: function(response){
                    window.localStorage.setItem('token', response.token)
                    window.localStorage.setItem('total_groups', JSON.stringify(response.groups));
                    window.localStorage.setItem('current_user', JSON.stringify(response.user));
                    window.localStorage.setItem('total_users', JSON.stringify(response.users));

                    window.location.replace('/chatmsg')
                },
                error: function(err){
                    alert(err.error)
                }
            })
        })
        // Ensure Echo is correctly configured with your Laravel WebSocket settings
        const echo = new Echo({
            broadcaster: 'pusher',
            key: '{{ env('PUSHER_APP_KEY') }}',
            wsHost: '{{ env('PUSHER_HOST') }}',
            wsPort: '{{ env('PUSHER_PORT') }}',
            forceTLS: false,
            disableStats: true,
            enabledTransports: ['ws', 'wss'],
        });

        // function fireEvent() {
        //     $.ajax({
        //         headers: {
        //             'X-CSRF-TOKEN': '{{ csrf_token() }}'
        //         },
        //         url: '{{ route('broadcast.chat') }}',
        //         type: 'POST',
        //         success: function(data) {
        //             console.log(data);
        //             alert('Event has been fired.');
        //         }
        //     });
        // }

        // Listening to the chatApplication channel and the Chat event
        // setTimeout(() => {
        //     echo.channel('chatApplication')
        //         .listen('Chat', (data) => {
        //             console.log(data);
        //         });
        // }, 100);
    </script>
</body>
</html>
