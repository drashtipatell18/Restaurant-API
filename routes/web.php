
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatAppController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotificationController;

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


Route::get('create', [UserController::class, 'create']);

Route::get('notification', [NotificationController::class, 'notification']);