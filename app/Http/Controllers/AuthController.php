<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Invite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\FirstLoginMail;
use Illuminate\Support\Str;
// use App\Models\Notification;
// use App\Events\NotificationMessage;


class AuthController extends Controller
{
     public function login(Request $request)
    {
        $validateUser = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);
    
        if ($validateUser->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => $validateUser->errors()
            ], 401);
        }
    
        // Find user by email
        $user = User::where('email', $request->input('email'))->first();
      

        // $errorMessage = 'No se pudo ingresar al sistema. Verifica tu correo y contraseña e intenta nuevamente.';
        if (!$user) {
            // broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();

            // Notification::create([
            //     'user_id' => 1, // No specific user associated with this notification
            //     'notification_type' => 'alert',
            //     'notification' => $errorMessage,
            // ]);

    
            return response()->json([
                'success' => false,
                // 'alert' => $errorMessage,
            ], 401);
        }
         if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario ingresado no existe en la base de datos del sistema'
            ], 401);
        }
    
    if ($user->status === 'Suspender') {
            return response()->json([
                'success' => false,
                'message' => 'Su cuenta está suspendida. Póngase en contacto con el servicio de asistencia.'
            ], 403);
        }
       
    
        // Decrypt the stored password and compare
        $ciphering = "AES-128-CTR";
        $options = 0;
        $decryption_iv = '1234567891011121'; // Ensure this matches the encryption IV
        $decryption_key = "GeeksforGeeks"; // Ensure this matches the encryption key
    
        try {
            $decrypted_password = openssl_decrypt($user->password, $ciphering, $decryption_key, $options, $decryption_iv);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error decrypting password'
            ], 500);
        }
    
        // Check if the decrypted password matches the provided password
        if ($decrypted_password !== $request->input('password')) {
            // broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            return response()->json([
                    'success' => false,
                    'message' => 'La contraseña ingresada no coincide con el correo electrónico proporcionado',
                    // 'alert' => $errorMessage,
                ], 401);
        }
    
        // Create token and respond with user info
        $token = $user->createToken($user->role_id)->plainTextToken;
    
        // $successMessage = "Bienvenido, {$user->name}. Has ingresado exitosamente al sistema.";
        // broadcast(new NotificationMessage('notification', $successMessage))->toOthers();

        // Notification::create([
        //     'user_id' => $user->id,
        //     'notification_type' => 'notification',
        //     'notification' => $successMessage,
        // ]);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'access_token' => $token,
            'role' => Role::find($user->role_id)->name,
            // 'notification' => $successMessage,
            'admin_id' => $user->admin_id
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
                'message' => 'El enlace para restablecer contraseña se ha utilizado o ha expirado.',
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
