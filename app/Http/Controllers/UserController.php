<?php

namespace App\Http\Controllers;
use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return response()->json($users, 200);
    }

    public function storeUser(Request $request)
    {
        $request->validate([
           'name' => 'required|string|max:255',
           'email' => 'required|string|email|max:255|unique:users',
           'role_id' => 'nullable|exists:roles,id',
           'password' => 'required|string|min:8|same:confirmpassword',
           'confirmpassword' => 'required|string|min:8|same:password',
        ]);

        $filename = '';
        if ($request->hasFile('image')){
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

    public function upateUser(Request $request ,$id){
        $request->validate([
             'name' => 'required|string|max:255',
             'email' => 'required|string|email|max:255|unique:users',
             'role_id' => 'nullable|exists:roles,id',
             'password' => 'required|string|min:8|same:confirmpassword',
             'confirmpassword' => 'required|string|min:8|same:password',
         ]);
 
         $filename = '';
         if ($request->hasFile('image')){
             $image = $request->file('image');
             $filename = time() . '.' . $image->getClientOriginalExtension();
             $image->move('images', $filename);
         }
         $users = User::find($id);

         $users->update([
             'name' => $request->name,
             'email' => $request->email,
             'role_id' => $request->role_id,
             'password' => Hash::make($request->password),
             'image' => $filename,
         ]);
 
         return response()->json([
             'message' => 'User updated successfully',
             'user' => $users,
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
