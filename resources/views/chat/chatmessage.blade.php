<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Chat Message</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ url('/css/style.css') }}">
    <style>
        .selected {
            background-color: blue !important;
            color: white !important;
        }
        .user-selected {
            background-color: lightblue !important;
            color: white !important;
        }
        .group-selected {
            background-color: lightgreen !important;
            color: white !important;
        }
    </style>
</head>
<body>
    <div class="container content">
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    @foreach ($users as $u)
                        <a href="javascript:void(0)" class="list-group-item list-group-item-action user-item"
                            data-username="{{ $u->username }}" data-id="{{ $u->id }}"
                            data-email="{{ $u->email }}" onclick="selectUser(this)">
                            {{ $u->email }}
                        </a>
                    @endforeach
                </div>
                <div class="list-group" style="margin-top:50px !important">
                    @foreach ($groups as $g)
                        <a href="javascript:void(0)" class="list-group-item list-group-item-action group-item"
                            data-group-id="{{ $g->id }}" data-group-name="{{ $g->name }}"
                            onclick="selectGroup(this)">
                            {{ $g->name }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="col-xl-9 col-lg-12 col-md-6 col-sm-12 col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <p class="mb-0">Receiver: <span id="chat-title"></span></p>
                        <p class="mb-0">Sender: {{ $username }}</p>
                    </div>
                    <div class="card-body height3">
                        <ul class="chat-list" id="chat-section">
                            <!-- Messages will be loaded here -->
                        </ul>
                    </div>
                </div>
                <div class="row mt-3 justify-content-between">
                    <div class="col-lg-11">
                        <input type="text" id="username" value="{{ $username }}" hidden class="form-control">
                        <input type="text" id="receiver_id" value="" hidden class="form-control">
                        <input type="text" id="group_id" value="" hidden class="form-control">
                        <input type="text" id="chat_message" class="form-control" placeholder="Write a text message...">
                    </div>
                    <div class="col-lg-1 justify-content-center">
                        <button class="btn btn-primary rounded w-100" onclick="broadcastMethod()">Send</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Pusher JS -->
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@7.0.0/dist/web/pusher.min.js"></script>
    <!-- Laravel Echo -->
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.11.3/dist/echo.iife.js"></script>

    <script>
        let receiverId = localStorage.getItem('receiverId') || null;
        let groupId = localStorage.getItem('groupId') || null;
        const userId = "{{Auth::user()->id}}";

        document.addEventListener('DOMContentLoaded', function() {
            let receiverId = localStorage.getItem('receiverId');
            let receiverName = localStorage.getItem('receiverName');
            let storedGroupId = localStorage.getItem('groupId');
            let groupName = localStorage.getItem('groupName');

            if (receiverId && receiverName) {
                $("#receiver_id").val(receiverId);
                $("#chat-title").text(receiverName);
                loadMessages(receiverId, null);
                $(`.list-group-item[data-id="${receiverId}"]`).addClass('user-selected');
            }

            if (storedGroupId && groupName) {
                $("#group_id").val(storedGroupId);
                $("#chat-title").text(groupName);
                loadMessages(null, storedGroupId);
                $(`.list-group-item[data-group-id="${storedGroupId}"]`).addClass('group-selected');
            }
        });

        function selectUser(element) {
            const email = $(element).data('email');
            const id = $(element).data('id');
            const username = $(element).data('username');

            receiverId = id;
            localStorage.setItem('receiverId', id);
            localStorage.setItem('receiverName', username);

            $("#receiver_id").val(id);
            $("#group_id").val(null);

            $('.user-item').removeClass('user-selected');
            $(element).addClass('user-selected');

            $("#chat-title").text(username);
            $("#chat-section").empty();
            window.localStorage.removeItem('groupId')
            window.localStorage.removeItem('groupName')
            window.location.reload();
            loadMessages(id);
        }

        function selectGroup(element) {
            const groupId = $(element).data('group-id');
            const groupName = $(element).data('group-name');

            localStorage.setItem('groupId', groupId);
            localStorage.setItem('groupName', groupName);

            $("#receiver_id").val(null);
            $("#group_id").val(groupId);

            $('.group-item').removeClass('group-selected');
            $(element).addClass('group-selected');

            $("#chat-title").text(groupName);
            $("#chat-section").empty();

            window.localStorage.removeItem('receiverId')
            window.localStorage.removeItem('receiverName')
            window.location.reload();
            loadMessages(null, groupId);
        }

        function loadMessages(id = null, groupId = null) {
            $.ajax({
                url: '{{ route('chat.messages') }}',
                type: 'GET',
                data: {
                    receiver_id: id,
                    group_id: groupId,
                },
                success: function(data) {
                    data.forEach(message => {
                        let newMessage;
                        if (message.sender_id === {{ auth()->user()->id }}) {
                            newMessage = `<li class="out">
                                            <div class="chat-img">
                                                <img alt="Avatar" src="https://bootdey.com/img/Content/avatar/avatar1.png">
                                            </div>
                                            <div class="chat-body">
                                                <div class="chat-message">
                                                    <h5>${message.sender_name}</h5>
                                                    <p>${message.message}</p>
                                                </div>
                                            </div>
                                        </li>`;
                        } else {
                            newMessage = `<li class="in">
                                            <div class="chat-img">
                                                <img alt="Avatar" src="https://bootdey.com/img/Content/avatar/avatar1.png">
                                            </div>
                                            <div class="chat-body">
                                                <div class="chat-message">
                                                    <h5>${message.sender_name}</h5>
                                                    <p>${message.message}</p>
                                                </div>
                                            </div>
                                        </li>`;
                        }
                        $("#chat-section").append(newMessage);
                    });
                },
                error: function(error) {
                    console.log(error);
                }
            });
        }

        function broadcastMethod() {
            let receiver_id = $("#receiver_id").val();
            let group_id = $("#group_id").val();

            if (!receiver_id && !group_id) {
                alert("Please select a user or a group to send a message.");
                return;
            }

            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                url: '{{ route('broadcast.chat') }}',
                type: 'POST',
                data: {
                    username: $("#username").val(),
                    receiver_id: receiver_id || null,
                    group_id: group_id || null,
                    msg: $('#chat_message').val()
                },
                success: function(data) {
                    let u = "{{ $username }}";
                    let newMessage;
                    if (group_id) {
                    //     newMessage = `<li class="out">
                    //                     <div class="chat-img">
                    //                         <img alt="Avatar" src="https://bootdey.com/img/Content/avatar/avatar1.png">
                    //                     </div>
                    //                     <div class="chat-body">
                    //                         <div class="chat-message">
                    //                             <h5>${u}</h5>
                    //                             <p>${$('#chat_message').val()}</p>
                    //                         </div>
                    //                     </div>
                    //                 </li>`;
                    // $("#chat-section").append(newMessage);
                    $('#chat_message').val('');
                    } else {
                        newMessage = `<li class="out">
                                        <div class="chat-img">
                                            <img alt="Avatar" src="https://bootdey.com/img/Content/avatar/avatar1.png">
                                        </div>
                                        <div class="chat-body">
                                            <div class="chat-message">
                                                <h5>${u}</h5>
                                                <p>${$('#chat_message').val()}</p>
                                            </div>
                                        </div>
                                    </li>`;
                    $("#chat-section").append(newMessage);
                    $('#chat_message').val('');
                    }
                },
                error: function(error) {
                    console.log(error);
                }
            });
        }

        window.Echo = new Echo({
            broadcaster: "pusher",
            key: "GoofNBCH",
            cluster: "mt1",
            wsHost: window.location.hostname,
            wsPort: 6001,
            forceTLS: false,
            disableStats: false,
            enabledTransports: ['ws', 'wss']
        });

        function subscribeToChat() {
            if (groupId) {
                // window.Echo.leaveChannel(`group.${groupId}`);
                window.Echo.join(`group.${groupId}`)
                    .listen('Chat', (data) => {
                        console.log(data);
                        if(data.username != $("#username").val())
                        {
                            let newMessage = `<li class="in">
                                                <div class="chat-img">
                                                    <img alt="Avatar" src="https://bootdey.com/img/Content/avatar/avatar1.png">
                                                </div>
                                                <div class="chat-body">
                                                    <div class="chat-message">
                                                        <h5>${data.username}</h5>
                                                        <p>${data.message}</p>
                                                    </div>
                                                </div>
                                            </li>`;
                            $("#chat-section").append(newMessage);
                        }
                        else
                        {
                            let newMessage = `<li class="out">
                                                <div class="chat-img">
                                                    <img alt="Avatar" src="https://bootdey.com/img/Content/avatar/avatar1.png">
                                                </div>
                                                <div class="chat-body">
                                                    <div class="chat-message">
                                                        <h5>${data.username}</h5>
                                                        <p>${data.message}</p>
                                                    </div>
                                                </div>
                                            </li>`;
                            $("#chat-section").append(newMessage);
                        }
                    })
                    .error((error) => {
                        console.error("Subscription error:", error);
                    });
            } else if (receiverId) {
                // window.Echo.leaveChannel(`chat.${receiverId}.${userId}`);
                window.Echo.private(`chat.${receiverId}.${userId}`)
                    .listen('Chat', (data) => {
                        let newMessage = `<li class="in">
                                            <div class="chat-img">
                                                <img alt="Avatar" src="https://bootdey.com/img/Content/avatar/avatar1.png">
                                            </div>
                                            <div class="chat-body">
                                                <div class="chat-message">
                                                    <h5>${data.username}</h5>
                                                    <p>${data.message}</p>
                                                </div>
                                            </div>
                                        </li>`;
                        $("#chat-section").append(newMessage);
                    });
            }
        }

        subscribeToChat();
    </script>
</body>
</html>
