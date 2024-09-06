
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatAppController;



Route::get('/', function () {
    return view('welcome');
});

Route::get('/box', function(){
    return view('card');
});

Route::post('brodcastCardClicked',[ChatAppController::class,'cardClicked'])->name('broadcast.cardclicked');



// Chat Application
Route::get('login',[ChatAppController::class,'chatLoggin'])->name('chat.loggin');
Route::get('chatmsg',[ChatAppController::class,'chatPage']);
Route::post('/brodcast',[ChatAppController::class,'broadcastChat'])->name('broadcast.chat');
Route::post('/chat',[ChatAppController::class,'chat'])->name('chat');

Route::get('logout',[ChatAppController::class,'logout']);

// Route::get('/chat/messages', [ChatAppController::class, 'getMessages'])->name('chat.messages');
