<?php

namespace App\Http\Controllers;

use App\Events\Chat;
use App\Models\Chats;
use App\Events\CardClick;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class ChatAppController extends Controller
{
// Selected Box
    public function index()
    {
        return view('card');
    }

    public function initialState()
    {
        $boxStates = [];
        foreach (range(1, 10) as $cardId) {
            $boxStates[] = Cache::get('box-' . $cardId, ['card_id' => $cardId, 'selected' => false]);
        }
        return response()->json($boxStates);
    }
    public function cardClicked(Request $request)
    {
        $cardId = $request->input('card_id');
        $selected = $request->input('selected');
        broadcast(new CardClick($cardId, $selected))->toOthers();
        return response()->json(['status' => 'Color changed!']);
    }

    // Chat Application

    // public function chatLoggin()
    // {
    //     return view('chat/login');
    // }

    // public function chat(Request $request)
    // {
    //     $request->validate([
    //         'email' => 'required|email',
    //     ]);
    //     $email = $request->email;

    //     $user = User::where('email', $email)->first();


    //     if (!$user) {
    //         return redirect()->back()->withErrors(['email' => 'Email not found in our records.']);
    //     }
    //     $users = User::where('email', '!=', $email)->get();
    //     Auth::login($user);

    //     return view('chat/chatmessage')->with(['email'=> $email,'username'=>$user->username,'users' => $users]);
    //     // return response()->json(['email'=> $email, 'username'=>$user->username]);
    // }

//     public function broadcastChat(Request $request)
// {
//     $request->validate([
//         'username' => 'required',
//         'receiver_id' => 'required|exists:users,id',
//         'msg' => 'required'
//     ]);

//     $sender = auth()->user();
//     $senderName = $sender->username;

//     $chat = new Chats;
//     $chat->sender_id = $sender->id;
//     $chat->receiver_id = $request->receiver_id;
//     $chat->message = $request->msg;
//     $chat->save();

//     broadcast(new Chat($sender->id, $request->receiver_id, $senderName, $request->msg));

//     return response()->json([
//         'status' => 'success',
//         'message' => 'Message sent successfully',
//         'data' => $chat
//     ]);
// }


    // public function notFound()
    // {
    //     return abort(404,'Not found');
    // }

    // public function getMessages(Request $request)
    // {
    //     $senderId = auth()->id();  // Authenticated user's ID
    //     $receiverId = $request->receiver_id;  // ID of the selected user to chat with

    //     // Fetch messages between the authenticated user and the selected user only
    //     $messages = Chats::where(function ($query) use ($senderId, $receiverId) {
    //         $query->where('sender_id', $senderId)->where('receiver_id', $receiverId);
    //     })->orWhere(function ($query) use ($senderId, $receiverId) {
    //         $query->where('sender_id', $receiverId)->where('receiver_id', $senderId);
    //     })->with('sender')->orderBy('created_at', 'asc')->get();

    //     // Add sender's username to the message object
    //     $messages->transform(function ($message) {
    //         $message->sender_name = $message->sender->username;
    //         return $message;
    //     });

    //     return response()->json($messages);
    // }
    // public function logout()
    // {
    //     Auth::logout();
    //     return redirect()->route('chat.loggin');
    // }


}
