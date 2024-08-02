<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
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


    public function getsinglePayments(Request $request)
    {
        $request->validate([
            'order_master_id' => 'required|integer'
        ]);
        $paymentId = Payment::where('order_master_id', $request->input('order_master_id'))->first();
        if ($paymentId) {
            return response()->json([
                'success' => true,
                'data' => $paymentId
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
        ]);

        return response()->json([
            'success' => true,
            'result' => $payment,
            'message' => 'Payment added successfully.'
        ], 200);
    }
}
