<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return response()->json($users, 200);
    }

    public function storeUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role_id' => 'nullable|exists:roles,id',
            'password' => 'required|string|min:8|same:confirm_password',
            'confirm_password' => 'required|string|min:8|same:password',
        ]);

        if ($validator->fails()) {
           return response()->json([
                    'success' => false,
                    'message' => 'Validation fails',
                    'error' => $validator->errors()
                ], 401);
        }        

        $filename = '';
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move('images', $filename);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'password' => Hash::make($request->password),
            'image' => $filename,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
        ], 200);
    }

    public function updateUser(Request $request, $id)
    {
        $user = Auth::user();
        dd($user);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'role_id' => 'nullable|exists:roles,id',
            'password' => 'required|string|min:8|same:confirm_password',
            'confirm_password' => 'required|string|min:8|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                     'success' => false,
                     'message' => 'Validation fails',
                     'error' => $validator->errors()
                 ], 401);
         }     

        $user = User::find($id);
        if (is_null($user)) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move('images', $filename);
            $user->image = $filename;
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role_id = $request->role_id;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ], 200);
    }

    public function destroyUser($id)
    {
        $user = User::find($id);
        if (is_null($user)) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }
}


