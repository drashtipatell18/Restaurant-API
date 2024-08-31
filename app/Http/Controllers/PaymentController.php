<?php

namespace App\Http\Controllers;

use App\Models\BoxLogs;
use Illuminate\Http\Request;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function GetPayment(Request $request){
    $payment = Payment::all();
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



    public function InsertPayment(Request $request){
        $validateRequest = Validator::make($request->all(), [
            'order_master_id' => 'required|exists:order_masters,id',
            'rut' => 'required',
            'lastname' => 'required',
            'ltda' => 'required',
            'tour' => 'required',
            'address' => 'required',
            'type' => 'required|in:cash,transfer,debit,credit',
            'amount' => 'required'
        ]);

        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }
        $payment = Payment::create([
            'order_master_id' => $request->input('order_master_id'),
            'rut' => $request->input('rut'),
            'firstname' => $request->input('firstname'),
            'lastname' => $request->input('lastname'),
            'business_name' => $request->input('business_name'),
            'ltda' => $request->input('ltda'),
            'tour' => $request->input('tour'),
            'address' => $request->input('address'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'type' => $request->input('type'),
            'amount' => $request->input('amount'),
            'return' => $request->input('return'),
            'tax' => $request->input('tax')
        ]);

        $log = BoxLogs::where('order_master_id', 'like', '%' . $request->input('order_master_id') . '%')->first();
        // if(empty($log->payment_id))
        // {
        //     $log->payment_id = $payment->id;
        // }
        // else
        // {
        //     $log->payment_id .= "," . $payment->id;
        // }
        // $log->save();


        if ($log) {
            // If payment_id is null or empty, set it to the new payment ID
            if (empty($log->payment_id)) {
                $log->payment_id = $payment->id;
            } else {
                // Append the new payment ID
                $log->payment_id .= "," . $payment->id;
            }
            $log->save();
        } else {
            // Handle case where no log is found, if necessary
            // Optionally create a new BoxLogs entry or handle the error
            return response()->json([
                'success' => false,
                'message' => 'Box log not found for the given order_master_id.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'result' => $payment,
            'message' => 'Payment added successfully.'
        ], 200);
    }
}
