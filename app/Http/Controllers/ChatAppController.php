<?php

namespace App\Http\Controllers;

use App\Events\Chat;
use App\Models\Chats;
use App\Events\CardClick;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use App\Models\GroupForChat;
use Illuminate\Support\Facades\DB;


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

    public function chatLoggin()
    {
        return view('chat.login');
    }

    public function chatPage()
    {
        return view('chat.chatmessage');
    }

    public function chat(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['error' => 'Email not found'], 404);
        }

        // Log in the user
        Auth::login($user);

        // Create a new API token for the user
        $token = $user->createToken('auth_token')->plainTextToken;

        // Return the token and user information in the response
        return response()->json([
            'status' => 'success',
            'token' => $token,
            'user' => [
                'email' => $user->email,
                'name' => $user->name,
                'id' => $user->id,
            ],
            'groups' => $user->groups,
            'users' => User::where('email', '!=', $user->email)->get()
        ]);
    }

    public function broadcastChat(Request $request)
    {
        $request->validate([
            'msg' => 'required',
        ]);

        $sender = auth()->user();
        if (!$sender) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $chat = new Chats;
        $chat->sender_id = $sender->id;
        $chat->message = $request->msg;

        // If it's a group message
        if ($request->group_id) {
            $chat->group_id = $request->group_id;
            $chat->save();

            broadcast(new Chat($sender->id, null, $sender->name, $request->msg, $request->group_id));

            return response()->json(['status' => 'success', 'message' => 'Group message sent successfully', 'data' => $chat]);
        }

        // If it's a private message
        if ($request->receiver_id) {
            $chat->receiver_id = $request->receiver_id;
            $chat->save();

            broadcast(new Chat($sender->id, $request->receiver_id, $sender->name, $request->msg, null));

            return response()->json(['status' => 'success', 'message' => 'Message sent successfully', 'data' => $chat]);
        }

        return response()->json(['status' => 'error', 'message' => 'Failed to send message'], 400);
    }

    public function notFound()
    {
        return abort(404,'Not found');
    }

    public function getMessages(Request $request)
    {
        $groupId = $request->group_id;
        $receiverId = $request->receiver_id;

        if (!$groupId && !$receiverId) {
            return response()->json(['error' => 'No chat identifier provided'], 400);
        }

        // If group chat
        if ($groupId) {
            $messages = Chats::where('group_id', $groupId)
                ->with('sender')
                ->orderBy('created_at', 'asc')
                ->get();
        } else {
            // If one-to-one chat
            $messages = Chats::where(function ($query) use ($receiverId) {
                $query->where('sender_id', auth()->id())
                    ->where('receiver_id', $receiverId);
            })->orWhere(function ($query) use ($receiverId) {
                $query->where('sender_id', $receiverId)
                    ->where('receiver_id', auth()->id());
            })->with('sender')->orderBy('created_at', 'asc')->get();
        }

        $messages->transform(function ($message) {
            $message->sender_name = $message->sender->name;
            return $message;
        });

        return response()->json($messages);
    }

    public function chatUsers1()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Email not found'], 404);
        }

        return response()->json([
            'status' => 'success',
            'user' => [
                'email' => $user->email,
                'name' => $user->name,
                'id' => $user->id,
            ],
            'groups' => $user->groups,
            'users' => User::where('email', '!=', $user->email)->get()
        ]);
    }

    public function chatUsers()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
    
        $allUsers = User::where('id', '!=', $user->id)->get();
    
        $usersWithMessages = $allUsers->map(function ($otherUser) use ($user) {
            $messages = Chats::where(function ($query) use ($user, $otherUser) {
                $query->where('sender_id', $user->id)
                      ->where('receiver_id', $otherUser->id);
            })->orWhere(function ($query) use ($user, $otherUser) {
                $query->where('sender_id', $otherUser->id)
                      ->where('receiver_id', $user->id);
            })->orderBy('created_at', 'desc')
            
              ->get();
    
            return [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'email' => $otherUser->email,
                'messages' => $messages
            ];
        });
    
        // Get the authenticated user's messages
        $userMessages = Chats::where('sender_id', $user->id)
                               ->orWhere('receiver_id', $user->id)
                               ->orderBy('created_at', 'desc')
                              
                               ->get();
    
        return response()->json([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'messages' => $userMessages
            ],
            'groups' => $user->groups,
            'users' => $usersWithMessages
        ]);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user->status = false; // Mark user as offline
        $user->save();

        Auth::logout();

        return response()->json(['status' => 'success', 'message' => 'Logged out successfully']);
    }

    public function storeGroup(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'name' => 'required|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Create a new group
        $group = new GroupForChat();
        $group->name = $request->input('name');
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $destinationPath = public_path('group_photos');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $fileName = time() . '-' . $file->getClientOriginalName();
            $file->move($destinationPath, $fileName);

            $group->photo = $fileName;
        }

        $group->save();

        return response()->json(['success' => true, 'group' => $group]);
    }


    public function addUserToGroup(Request $request)
    {
        // Validate the request
        $request->validate([
            'group_id' => 'required|exists:group_for_chats,id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $group = GroupForChat::find($request->group_id);

        if (!$group) {
            return response()->json(['status' => 'error', 'message' => 'Group not found']);
        }

        // Attach each user to the group
        // $group->users()->syncWithoutDetaching($request->user_ids);
        // Attach each user to the group using a loop
        foreach ($request->user_ids as $user_id) {
            // Check if the user is already in the group to avoid duplicates
            $exists = DB::table('user_group_joins')
                ->where('group_id', $request->group_id)
                ->where('user_id', $user_id)
                ->exists();

            if (!$exists) {
                DB::table('user_group_joins')->insert([
                    'group_id' => $request->group_id,
                    'group_for_chat_id' => $request->group_id,
                    'user_id' => $user_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Users added to group']);
    }


    public function removeUserFromGroup(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:group_for_chats,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $group = GroupForChat::find($request->group_id);
        $user = User::find($request->user_id);

        if (!$group || !$user) {
            return response()->json(['status' => 'error', 'message' => 'Group or user not found']);
        }

        $group->users()->detach($user->id);

        return response()->json(['status' => 'success', 'message' => 'User removed from group']);
    }
    public function markAsRead(Request $request)
    {
        $chatIds = $request->input('chat_id');
        $userId = auth()->id();
    
        if (!is_array($chatIds)) {
            $chatIds = [$chatIds]; // Convert single ID to an array
        }
    
        foreach ($chatIds as $chatId) {
            $chat = Chats::find($chatId);
    
            if (!$chat) {
                continue; // Skip if chat not found
            }
    
            $chat->read_by = json_encode("yes"); // Directly set the field to 'yes'
            $chat->save();
        }
    
        return response()->json(['status' => 'success', 'message' => 'Chats marked as read successfully.', 'chat' => $chat]);
    }
}
