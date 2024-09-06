
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
