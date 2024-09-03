
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatAppController;
use App\Http\Controllers\GroupChatController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/box', function(){
    return view('card');
});

Route::post('brodcastCardClicked',[ChatAppController::class,'cardClicked'])->name('broadcast.cardclicked');



// Chat Application
Route::get('login',[ChatAppController::class,'chatLoggin'])->name('chat.loggin');
Route::post('/brodcast',[ChatAppController::class,'broadcastChat'])->name('broadcast.chat');
Route::post('/chat',[ChatAppController::class,'chat'])->name('chat');

Route::get('logout',[ChatAppController::class,'logout']);

Route::get('/chat/messages', [ChatAppController::class, 'getMessages'])->name('chat.messages');




Route::get('group/create', [GroupChatController::class, 'createGroup']);


Route::post('/group/store', [GroupChatController::class, 'storeGroup']);


Route::get('/group/{id}', [GroupChatController::class, 'getGroup'])->name('group.get');

// Route to get all groups
Route::get('/groups', [GroupChatController::class, 'getAllGroups'])->name('group.all');