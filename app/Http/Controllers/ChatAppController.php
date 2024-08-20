<?php

namespace App\Http\Controllers;

use App\Events\Chat;
use App\Events\CardClick;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class ChatAppController extends Controller
{
// Selected Box
    public function index()
    {
        return view('card');
    }
    public function cardClicked(Request $request)
    {
        $card_id = $request->input('card_id');
        $selected = $request->input('selected');
        broadcast(new CardClick($card_id, $selected));
        // return response()->json(['status' => 'Card clicked!']);
        return response()->json(['status' => 'Card updated!', 'card_id' => $card_id, 'selected' => $selected]);
    }


// Chat Application

    public function chatLoggin()
    {
        return view('chat/login');
    }


    public function chat(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);
        $email = $request->email;

        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->back()->withErrors(['email' => 'Email not found in our records.']);
        }
        Auth::login($user);

        return view('chat/chatmessage')->with(['email'=> $email,'username'=>$user->username]);
        // return response()->json(['email'=> $email, 'username'=>$user->username]);
    }
    public function notFound()
    {
        return abort(404,'Not found');
    }

    public function broadcastChat(Request $request)
    {
        $request->validate([
            'username'=>'required',
            'msg' => 'required'
        ]);
        event(new Chat($request->username,$request->msg));
        return response()->json($request->all());
    }
}
