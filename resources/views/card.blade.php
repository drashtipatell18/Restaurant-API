<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Box</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        .box {
            width: 100px;
            height: 100px;
            display: inline-block;
            margin: 10px;
            background-color: lightgray;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="box" data-id="1"></div>
        <div class="box" data-id="2"></div>
        <div class="box" data-id="3"></div>
        <div class="box" data-id="4"></div>
        <div class="box" data-id="5"></div>
        <div class="box" data-id="6"></div>
        <div class="box" data-id="7"></div>
        <div class="box" data-id="8"></div>
        <div class="box" data-id="9"></div>
        <div class="box" data-id="10"></div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script src="https://cdn.jsdelivr.net/npm/pusher-js@7.0.0/dist/web/pusher.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.11.3/dist/echo.iife.js"></script>

    <script>
        const defaultColor = 'lightgray';
        const activeColor = 'green';
        const socket = new window.Echo({
            broadcaster: "pusher",
            key: "GoofNBCH",
            cluster: "mt1",
            wsHost: window.location.hostname,
            wsPort: 6001,
            forceTLS: false,
            disableStats: true,
        });

        const boxes = document.querySelectorAll('.box');
        fetch('/api/initialState')
            .then(response => response.json())
            .then(data => {
                data.forEach(box => {
                    const element = document.querySelector(`.box[data-id="${box.card_id}"]`);
                    element.style.backgroundColor = box.selected ? activeColor : defaultColor;
                });
            });


        socket.channel('box-channel')
            .listen('.CardClick', (e) => {
                const box = document.querySelector(`.box[data-id="${e.card_id}"]`);
                box.style.backgroundColor = e.selected ? activeColor : defaultColor;
            });

        boxes.forEach(box => {
            box.addEventListener('click', () => {
                const cardId = box.getAttribute('data-id');
                const isSelected = box.style.backgroundColor === activeColor;
                const newSelected = !isSelected;

                box.style.backgroundColor = newSelected ? activeColor : defaultColor;

                fetch('/api/brodcastCardClicked', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ card_id: cardId, selected: newSelected })
                });
            });
        });
    </script>
</body>
</html>
