<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Invite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\FirstLoginMail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validateUser = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required'
        ]);

        if ($validateUser->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'error' => $validateUser->errors()
            ], 401);
        }

        if (Auth::attempt($request->only(['email', 'password']))) {
            return response()->json([
                'success' => false,
                'message' => 'Credential does not found in our records',
            ], 401);
        }
        $user = User::where('email', $request->input('email'))->first();
        $token = $user->createToken($user->role_id)->plainTextToken;
        return response()->json([
            'name' => $user->name,
            'access_token' => $token,
            'role' => Role::find($user->role_id)->name
        ]);
    }

    public function invite(Request $request)
    {
        $validateUser = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email',
        ]);

        if ($validateUser->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateUser->errors()
            ], 422);
        }

        $user = User::create([
            'role_id' => $request->input('role_id'),
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password_reset' => false,
            // 'password_reset_expires_at' => now()->addMinute(),
        ]);

        $token = Str::random(60);
        $user->remember_token = Hash::make($token);
        $user->save();

        Mail::to($user->email)->send(new FirstLoginMail($user, $token));

        return response()->json([
            'success' => true,
            'message' => 'User invited successfully. Email sent with login instructions.',
            'user' => $user,
        ], 201);
    }

    public function setPassword(Request $request, $id)
    {
        $invite = User::findOrFail($id);


        if ($invite->password_reset || now()->greaterThan($invite->password_reset_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Password reset link has either been used or expired.',
            ], 400);
        }

        // Validate the request input
        $validateUser = Validator::make($request->all(), [
            'password' => 'required|string|min:6', // Ensure the password meets your criteria
        ]);

        if ($validateUser->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'error' => $validateUser->errors()
            ], 400);
        }

        $simple_string = $request->password;

        // Encryption setup
        $ciphering = "AES-128-CTR";
        $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;
        $encryption_iv = '1234567891011121';
        $encryption_key = "GeeksforGeeks";
        $encryption = openssl_encrypt($simple_string, $ciphering, $encryption_key, $options, $encryption_iv);

        // Set the password and mark the invite as used
        $invite->password = $encryption;
        $invite->password_reset = true;
        $invite->password_reset_expires_at = null;
        $invite->save();

        return response()->json([
            'success' => true,
            'message' => 'Your password has been set successfully.'
        ], 200);
    }

}
