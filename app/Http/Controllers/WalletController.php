<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wallet;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    public function createWallet(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'user_id' => 'required',
            'credit' => 'required'
        ]);

        if($validate->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validate->errors()
            ], 401);
        }

        Wallet::create([
            'user_id' => $request->input('user_id'),
            'credit' => $request->input('credit')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Wallet added successfully.'
        ], 200);
    }

    public function updateWallet(Request $request,$id)
    {
        $validate = Validator::make($request->all(), [
            'user_id' => 'required',
            'credit' => 'required'
        ]);

        if($validate->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validate->errors()
            ], 401);
        }

        $wallet = Wallet::find($id);
        
        $wallet->update([
            'user_id' => $request->input('user_id'),
            'credit' => $request->input('credit')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Wallet updated successfully.'
        ], 200);
    }

    public function deleteWallet($id)
    {
        $wallet = Wallet::find($id);
        if (is_null($wallet)) {
            return response()->json(['message' => 'Wallet not found'], 404);
        }

        $wallet->delete();
        return response()->json(['message' => 'Wallet deleted successfully'], 200);
    }

}
