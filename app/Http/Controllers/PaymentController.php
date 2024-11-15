<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use Illuminate\Support\Facades\Validator;
use App\Models\BoxLogs;
use App\Models\Role;
use App\Models\Notification;
use App\Events\NotificationMessage;
use App\Models\User;

class PaymentController extends Controller
{

    public function GetPayment(Request $request){
        $admin_id = $request->admin_id;
        $payment = Payment::where('admin_id', $admin_id)->get();
        return response()->json([
            'success' => true,
            'result' => $payment,
            'message' => 'Payment Data successfully.'
        ], 200);
    }
  public function getsinglePayments($order_master_id)
    {
        // Assuming the route parameter is directly used.
        $payment = Payment::where('order_master_id', $order_master_id)->first();

        if ($payment) {
            return response()->json([
                'success' => true,
                'data' => $payment
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }
    }
    // public function InsertPayment(Request $request){
    //     $adminId = $request->admin_id;
    //     $validateRequest = Validator::make($request->all(), [
    //         'order_master_id' => 'required|exists:order_masters,id',
    //         'rut' => 'required',
    //         'lastname' => 'required',
    //         'tour' => 'required',
    //         'address' => 'required',
    //         'type' => 'required|in:cash,transfer,debit,credit',
    //         'amount' => 'required'
    //     ]);
    //     $user = auth()->user();
    //     $adminId = $user->role_id == 1 ? $user->id : $user->admin_id;

    //     if ($validateRequest->fails()) {
    //         $role = Role::where('id', Auth()->user()->role_id)->first()->name;
    //         if ($role != "admin" && $role != "cashier") {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Unauthorised'   
    //             ], 401);
    //         }
    //         $errorMessage = 'Ocurrió un error al procesar el pago. Por favor, verifica los detalles e intenta nuevamente.';
    //         broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
    //         Notification::create([
    //             'user_id' => auth()->user()->id,
    //             'notification_type' => 'notification',
    //             'notification' => $errorMessage,
    //             'admin_id' => $request->admin_id,
    //              'role_id' => $user->role_id
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation fails',
    //             'errors' => $validateRequest->errors(),
    //             'alert' => $errorMessage
    //         ], 403);
    //     }

    //     $payment = Payment::create([
    //         'order_master_id' => $request->input('order_master_id'),
    //         'admin_id' => $request->admin_id,
    //         'rut' => $request->input('rut'),
    //         'firstname' => $request->input('firstname'),
    //         'lastname' => $request->input('lastname'),
    //         'business_name' => $request->input('business_name'),
    //         'ltda' => $request->input('ltda'),
    //         'tour' => $request->input('tour'),
    //         'address' => $request->input('address'),
    //         'email' => $request->input('email'),
    //         'phone' => $request->input('phone'),
    //         'type' => $request->input('type'),
    //         'amount' => $request->input('amount'),
    //         'return' => $request->input('return'),
    //         'tax' => $request->input('tax'),
    //     ]);
        
    //     $log = BoxLogs::where('order_master_id', 'like', '%' . $request->input('order_master_id') . '%')->first();
        
    //    if ($log) {
    //     // If payment_id is null or empty, set it to the new payment ID
    //     if (empty($log->payment_id)) {
    //         $log->payment_id = $payment->id;
    //     } else {
    //         // Append the new payment ID
    //         $log->payment_id .= "," . $payment->id;
    //     }
    //     $log->save();
    // } else {
    //     // Handle case where no log is found, if necessary
    //     // Optionally create a new BoxLogs entry or handle the error
    //     return response()->json([
    //         'success' => false,
    //         'message' => 'Box log not found for the given order_master_id.'
    //     ], 404);
    // }

    // try{
    //     $successMessage = "El recibo de pago para el pedido {$payment->order_master_id} ha sido generado e impreso correctamente.";
    //     broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
    //     Notification::create([
    //         'user_id' => auth()->user()->id,
    //         'notification_type' => 'notification',
    //         'notification' => $successMessage,
    //         'admin_id' => $request->admin_id,
    //         'role_id' => $user->role_id
    //     ]);
    // }
    // catch(Exception $e)
    // {
    //      $errorMessage = 'No se pudo generar el recibo de pago. Por favor, intenta nuevamente.';
    //         broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
    //         Notification::create([
    //             'user_id' => auth()->user()->id,
    //             'notification_type' => 'notification',
    //             'notification' => $errorMessage,
    //             'admin_id' => $request->admin_id,
    //              'role_id' => $user->role_id
    //         ]);
    // }
      
    
    
    //     // $log->save();

    //     return response()->json([
    //         'success' => true,
    //         'result' => $payment,
    //         'message' => 'Payment added successfully.',
    //         'notification' => $successMessage
    //     ], 200);

    //     return response()->json([
    //         'success' => false,
    //         'message' => 'Payment Failed.',
    //         'notification' => $errorMessage
    //     ], 403);
    // }

    public function InsertPayment(Request $request)
    {
        // Validate the incoming request
        $validateRequest = Validator::make($request->all(), [
            'order_master_id' => 'required|exists:order_masters,id',
            // 'rut' => 'required',
            // 'lastname' => 'required',
            // 'tour' => 'required',
            // 'address' => 'required',
            'type' => 'required|array',
            'type.*' => 'in:cash,transfer,debit,credit',
            'amount' => 'required'
        ]);
    
        // If validation fails, handle the error
        if ($validateRequest->fails()) {
            $role = Role::where('id', Auth()->user()->role_id)->first()->name;
            if ($role != "admin" && $role != "cashier") {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
    
            // Notify about validation failure
            $errorMessage = 'Ocurri�� un error al procesar el pago. Por favor, verifica los detalles e intenta nuevamente.';
            $user = auth()->user();
            $admin_id = ($role == 'admin') ? $user->id : $user->admin_id;
    
            // Broadcast notification
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
    
            // Notify relevant users
            $usersRoles = User::where('admin_id', $admin_id)
                ->whereIn('role_id', [1, 2])
                ->orWhere('id', $admin_id)
                ->get();
    
            foreach ($usersRoles as $recipient) {
                Notification::create([
                    'user_id' => $recipient->id,
                    'notification_type' => 'notification',
                    'notification' => $errorMessage,
                    'admin_id' => $admin_id,
                    'role_id' => $recipient->role_id
                ]);
            }
    
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors(),
                'alert' => $errorMessage
            ], 403);
        }
    
        // Gather user info and set admin ID
        $user = auth()->user();
        $adminId = $user->role_id == 1 ? $user->id : $user->admin_id;
    
        // Create the payment record
        $payment = Payment::create(array_merge(
            $request->only([
                'order_master_id', 'rut', 'firstname',
                'lastname', 'business_name', 'ltda',
                'tour', 'address', 'email', 'phone',
                'amount', 'return', 'tax',
            ]),
            [
                'admin_id' => $request->admin_id,
                'type' => implode(',', $request->type) // Convert array to string
            ]
        ));
    
        // Log payment details
        $log = BoxLogs::where('order_master_id', 'like', '%' . $request->input('order_master_id') . '%')->first();
        // dd(BoxLogs::where('order_master_id', $request->input('order_master_id')));
        // dd($log);
        if ($log) {
            // Update payment ID in the log
            $log->payment_id = $log->payment_id ? $log->payment_id . "," . $payment->id : $payment->id;
            $log->save();
        } 
    
        // Broadcast success notification
        try {
            $successMessage = "El recibo de pago para el pedido {$payment->order_master_id} ha sido generado e impreso correctamente.";
            broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
    
            // Notify relevant users about the success
            $usersRoles = User::where('admin_id', $adminId)
                ->whereIn('role_id', [1, 2])
                ->orWhere('id', $adminId)
                ->get();
    
            foreach ($usersRoles as $recipient) {
                Notification::create([
                    'user_id' => $recipient->id,
                    'notification_type' => 'notification',
                    'notification' => $successMessage,
                    'admin_id' => $request->admin_id,
                    'role_id' => $recipient->role_id
                ]);
            }
    
            return response()->json([
                'success' => true,
                'result' => $payment,
                'message' => 'Payment added successfully.',
                'notification' => $successMessage
            ], 200);
        } catch (Exception $e) {
            // Handle notification failure
            $errorMessage = 'No se pudo generar el recibo de pago. Por favor, intenta nuevamente.';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
    
            // Notify relevant users about the failure
            $usersRoles = User::where('admin_id', $adminId)
                ->whereIn('role_id', [1, 2])
                ->orWhere('id', $adminId)
                ->get();
    
            foreach ($usersRoles as $recipient) {
                Notification::create([
                    'user_id' => $recipient->id,
                    'notification_type' => 'notification',
                    'notification' => $errorMessage,
                    'admin_id' => $adminId,
                    'role_id' => $recipient->role_id
                ]);
            }
    
            return response()->json([
                'success' => false,
                'message' => 'Payment Failed.',
                'notification' => $errorMessage
            ], 403);
        }
    }
        public function getPaymentById(Request $request,$id)
    {
        // Retrieve the payment record by ID
        $adminId = $request->input('admin_id');
        
        $payment = Payment::find($id);
        // dd($payment->id,$adminId);
        
        if ($payment && $payment->admin_id == $adminId) {
            return response()->json([
                'success' => true,
                'data' => $payment
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found or Admin ID does not match'
            ], 404);
        }
    }

}
