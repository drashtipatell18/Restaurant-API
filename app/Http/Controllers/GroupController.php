<?php

namespace App\Http\Controllers;

use App\Models\Chats;
use App\Models\GroupForChat;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGroupJoin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    public function create(Request $request)
    {
        $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        $validateRequest = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'photo' => 'required|file|mimes:jpg,png,jpeg,gif|max:2048'
        ]);

        if($validateRequest->fails())
        {
            return response()->json([
                'success' => false,
                'message' => "Validation fails",
                'errors' => $validateRequest->errors()
            ]);
        }

        $filename = '';
        if($request->hasFile('photo'))
        {
            $image = $request->file('photo');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images'), $filename);
        }

        $group = GroupForChat::create([
            'name' => $request->input('name'),
            'photo' => $filename
        ]);

        return response()->json([
            'success' => true, 
            'message' => "Group created successfully",
            "group" => $group
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        $validateRequest = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'photo' => 'file|mimes:jpg,png,jpeg,gif|max:2048'
        ]);

        if($validateRequest->fails())
        {
            return response()->json([
                'success' => false,
                'message' => "Validation fails",
                'errors' => $validateRequest->errors()
            ]);
        }
        $group = GroupForChat::find($id);
        $filename = '';
        if($request->hasFile('photo'))
        {
            $image = $request->file('photo');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images'), $filename);

            $group->photo = $filename;
        }

        $group->name = $request->input('name');

        return response()->json([
            'success' => true, 
            'message' => "Group updated successfully",
            "group" => $group
        ], 200);
    }

    public function delete($id)
    {
        $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        $group = GroupForChat::find($id);

        if($group == null)
        {
            return response()->json([
                'success' => false,
                'message' => 'Group id invalid',
            ], 403);
        }

        $userGroupJoins = UserGroupJoin::where('group_id', $id)->get();
        foreach ($userGroupJoins as $userGroupJoin) {
            $userGroupJoin->delete();
        }
        $group->delete();

        return response()->json([
            'success' => true,
            'message' => "Group deleted successfully"
        ], 200);
    }

    public function addUser(Request $request)
    {
        $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        $validateRequest = Validator::make($request->all(),[
            'group_id' => 'required|exists:group_for_chats,id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id'
        ]);

        if($validateRequest->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        foreach ($request->user_ids as $user_id) {
            UserGroupJoin::create([
                'group_id' => $request->input('group_id'),
                'user_id' => $user_id
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Users added to group'
        ], 200);
    }

    public function deleteUser(Request $request)
    {
        $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        $validateRequest = Validator::make($request->all(),[
            'group_id' => 'required|exists:group_for_chats,id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id'
        ]);

        if($validateRequest->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        foreach ($request->user_ids as $user_id) {
            $join = UserGroupJoin::where([
                'group_id' => $request->input('group_id'),
                'user_id' => $user_id
            ]);
            $join->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Users deleted from group'
        ], 200);
    }

    public function getAllGroups()
    {
        $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        $groups = GroupForChat::all();
        return response()->json(['groups' => $groups], 200);
    }

    public function getMyGroups()
    {
        $groups = DB::table('user_group_joins')
                ->leftJoin('users', 'user_group_joins.user_id', '=', 'users.id')
                ->leftJoin('group_for_chats', 'user_group_joins.group_id', '=', 'group_for_chats.id')
                ->where('user_group_joins.user_id', Auth()->user()->id)
                ->whereNull('user_group_joins.deleted_at')
                ->select('group_for_chats.*')
                ->get();
        return response()->json([
            'success' => true,
            'groups' => $groups
        ], 200);
    }

    public function getGroupChats($id)
    {
        $user = Auth()->user();
        $group = GroupForChat::find($id);

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

        $chats = Chats::where('group_id', $group->id)->orderBy('created_at', 'desc') ->get();

        foreach ($chats as $chat) {
            $chat['sender'] = User::find($chat->sender_id)->name;
        }

        return response()->json([
            'success' => true,
            'chats' => $chats
        ], 200);
    }
}
