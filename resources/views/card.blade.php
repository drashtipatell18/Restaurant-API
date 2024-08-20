<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Chat App</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<style>
    .card {
        width: 100px;
        height: 100px;
        background: gray;
        margin: 5px;
        display: inline-block;
        cursor: pointer;
    }

    .selected {
        background: green;
    }
</style>

<body>
    <div class="container">
        <h1>Welcome to My Box App</h1>
        <div class="cards">
            @for ($i = 1; $i <= 9; $i++)
                <div class="card" onclick="fireClick(this)" id="card-{{ $i }}"></div>
            @endfor
        </div>
    </div>
    @vite('resources/js/app.js')

<script>
    function fireClick(cardElement) {
        const cardId = $(cardElement).attr('id');

        if ($(cardElement).hasClass('selected')) {
            $(cardElement).removeClass('selected');

            $.ajax({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            url: '{{ route('broadcast.cardclicked') }}',
            type: 'POST',
            data: {
                card_id: cardId
            },
            success: function(data) {
                console.log(data);
            }
        });
        }
        else{
            $(cardElement).addClass('selected');
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                url: '{{ route('broadcast.cardclicked') }}',
                type: 'POST',
                data: {
                    card_id: cardId,
                    selected: true
                },
                success: function(data) {
                    console.log(data);
                }
            });
        }
    }
    setTimeout(() => {
        window.Echo.channel('chatMessage').listen('CardClick', (event)=> {
    const cardElement = document.getElementById(event.card_id);
        if (cardElement) {
            if (event.selected) {
                $(cardElement).addClass('selected');
            } else {
                $(cardElement).removeClass('selected');
            }
        }
    });
    }, 1000);

</script>
</body>
</html>
