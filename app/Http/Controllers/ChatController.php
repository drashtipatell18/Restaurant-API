<?php

namespace App\Http\Controllers;

use App\Models\Chats;
use App\Models\GroupForChat;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGroupJoin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    public function addGroupChat(Request $request)
    {
        $validateRequest = Validator::make($request->all(), [
            'group_id' => 'required|exists:group_for_chats,id',
            'message' => 'required'
        ]);

        if($validateRequest->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        $user = Auth()->user();
        $group = GroupForChat::find($request->group_id);

        if($group == null)
        {
            return response()->json([
                'success' => false,
                'message' => 'Group id invalid'
            ],403);
        }

        if(Role::find($user->role_id)->name != "admin")
        {
            $check = UserGroupJoin::where('user_id', $user->id)->where('group_id', $group->id)->get()->count();
    
            if($check == 0)
            {
                return response()->json([
                    'success'=>false,
                    'message'=>'You are not a member of this group, so you cannot access the chats'
                ], 401);
            }
        }

        Chats::create([
            'sender_id' => $user->id,
            'group_id' => $group->id,
            'message' => $request->message
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully'
        ], 200);
    }

    public function makeNewChat(Request $request)
    {
        $validateRequest = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required'
        ]);

        if($validateRequest->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        Chats::create([
            'sender_id' => Auth()->user()->id,
            'receiver_id' => $request->receiver_id,
            'message' => $request->message
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully.'
        ], 200);
    }

    public function getAllChats()
    {
        $users = User::all()->whereNotIn('id', [Auth()->user()->id]);

        $responseUsers = [];
        $temp = [];
        foreach ($users as $user) 
        {
            $check = Chats::where('sender_id', $user->id)->where('receiver_id', Auth()->user()->id)->where('read_by', 'no')->get()->count();

            $user["unread"] = $check;
            if($check != 0)
            {
                array_push($responseUsers, $user);
            }
            else
            {
                array_push($temp, $user);
            }
        }

        if(count($temp) != 0)
        {
            foreach ($temp as $t) {
                array_push($responseUsers, $t);
            }
        }

        return response()->json([
            'users' => $responseUsers
        ], 200);
    }

    public function getSpecificUserChat($id)
    {
        $sender = User::find($id);

        if($sender == null)
        {
            return response()->json([
                'success' => false,
                'message' => "ID not valid"
            ], 403);
        }

        $chats = Chats::whereIn('sender_id', [$sender->id, Auth()->user()->id])->whereIn('receiver_id', [$sender->id, Auth()->user()->id])->orderBy('created_at', 'desc')->get();

        foreach ($chats as $chat) {
            if($chat->sender_id == $sender->id && $chat->receiver_id == Auth()->user()->id)
            {
                $chat->read_by = "yes";
                $chat->save();
            }
        }

        return response()->json([
            'chats' => $chats
        ], 200);
    }
}
