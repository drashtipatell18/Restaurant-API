<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\Wallet_log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WalletLogController extends Controller
{
    public function createWalletLog(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'transcation_id'        => 'required',
            'wallet_id'             => 'required',
            'credit_amount'         => 'required',
            'transcation_type'      => 'required',
        ]);

        if($validate->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validate->errors()
            ], 401);
        }

        Wallet_log::create([
            'transcation_id'    => $request->input('transcation_id'),
            'wallet_id'         => $request->input('wallet_id'),
            'credit_amount'     => $request->input('credit_amount'),
            'transcation_type'  => $request->input('transcation_type'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'WalletLog added successfully.'
        ], 200);
    }

    public function updateWalletLog(Request $request,$id)
    {
        $validate = Validator::make($request->all(), [
            'transcation_id'        => 'required',
            'wallet_id'             => 'required',
            'credit_amount'         => 'required',
            'transcation_type'      => 'required',
        ]);

        if($validate->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validate->errors()
            ], 401);
        }

        $walletlog = Wallet_log::find($id);
        
        $walletlog->update([
            'transcation_id'   => $request->input('transcation_id'),
            'wallet_id'        => $request->input('wallet_id'),
            'credit_amount'    => $request->input('credit_amount'),
            'transcation_type' => $request->input('transcation_type'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Wallet updated successfully.'
        ], 200);
    }

    public function deleteWalletLog($id)
    {
        $walletlog = Wallet_log::find($id);
        if (is_null($user)) {
            return response()->json(['message' => 'Wallet not found'], 404);
        }

        $walletlog->delete();
        return response()->json(['message' => 'Wallet deleted successfully'], 200);
    }

}
