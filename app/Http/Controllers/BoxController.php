<?php

namespace App\Http\Controllers;

use App\Models\BoxLogs;
use Illuminate\Http\Request;
use App\Models\Boxs;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BoxController extends Controller
{
    public function index()
    {
        $boxs = Boxs::all();
        return response()->json($boxs, 200);
    }

    public function createBox(Request $request)
    {
        $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }
        $validateFamily = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'name' => 'required|string|max:255'
        ]);

        if($validateFamily->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateFamily->errors()
            ], 401);
        }

        $user = User::find($request->input('user_id'));

        if(Role::find($user->role_id)->name != "cashier")
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => [
                    "user_id" => "Only cashier can be assigned to a box"
                ]
            ], 401);
        }

        $checkBox = Boxs::where('user_id', $request->input('user_id'))->count();

        if($checkBox != 0)
        {
            return response()->json([
                'success' => false,
                'message' => "Validation fails",
                'errors' => [
                    "user_id" => "One cashier can be assigned on box only."
                ]
            ],403);
        }

        $box = Boxs::create([
            'user_id' => $request->input('user_id'),
            'name' => $request->input('name')
        ]);

        return response()->json([
            'success' => true,
            'box' => $box,
            'message' => 'Box added successfully.'
        ], 200);
    }

    public function updateBox(Request $request,$id)
    {
        $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }
        $validateFamily = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'name' => 'required|string|max:255'
        ]);

        if($validateFamily->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateFamily->errors()
            ], 401);
        }

        $user = User::find($request->input('user_id'));

        if(Role::find($user->role_id)->name != "cashier")
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => [
                    "user_id" => "Only cashier can be assigned to a box"
                ]
            ], 401);
        }
        
        $box = Boxs::find($id);
        $box->update([
            'user_id' => $request->input('user_id'),
            'name' => $request->input('name')
        ]);

        return response()->json([
            'success' => true,
            'box' => $box,
            'message' => 'Box Updated Successfully.'
        ], 200);
    }

    public function deleteBox($id)
    {
        $boxs = Boxs::find($id);
        if (is_null($boxs)) {
            return response()->json(['message' => 'Box not found'], 404);
        }
        $boxs->delete();
        return response()->json(['message' => 'Box deleted successfully'], 200);
    }

    public function Boxsearch(Request $request)
    {
        $ids = $request->input('ids', []);
        $boxQuery = Boxs::query();
        if (!empty($roleIds)) {
            $boxQuery->whereIn('ids', $ids);
        }
        $boxs = $boxQuery->get();

        foreach ($boxs as $box) {
            $boxLog = BoxLogs::where('box_id', $box->id)->get()->last();

            if($boxLog == null)
            {
                $box['status'] = "Not opened";
            }
            else if($boxLog->close_time != null)
            {
                $box['status'] = "Not opened";
            }
            else 
            {
                $box['status'] = "Opened";
                $box['open_amount'] = $boxLog->open_amount;
                $box['open_time'] = $boxLog->open_time;
                $box['open_by'] = User::find($boxLog->open_by)->name;
            }

            $box['log'] = $boxLog = BoxLogs::where('box_id', $box->id)->get();
        }
        return response()->json($boxs, 200);
    } 
    
    public function BoxStatusChange(Request $request)
    {
        $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        if($role != "admin" && $role != "cashier")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }
        $validateInitial = Validator::make($request->all(),[
            'box_id' => 'required|exists:boxs,id'
        ]);

        if($validateInitial->fails())
        {
            return response()->json([
                'success' => false,
                'message' => "Validation fails",
                'errors' => $validateInitial->errors()
            ], 403);
        }
        
        $boxLog = BoxLogs::where('box_id', $request->input('box_id'))->get()->last();

        if($boxLog == null)
        {
            $validateLater = Validator::make($request->all(), [
                'open_amount' => 'required|numeric|min:0'
            ]);

            if($validateLater->fails())
            {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation fails',
                    'errors' => $validateLater->errors()
                ], 403);
            }

            $log = BoxLogs::create([
                'box_id' => $request->input('box_id'),
                'open_amount' => $request->input('open_amount'),
                'open_by' => Auth::user()->id,
                'open_time' => Carbon::now(),
                'collected_amount' => 0
            ]);

            return response()->json([
                'success' => true,
                'box' => $log
            ], 200);
        }
        else if($boxLog->close_time != null)
        {
            $validateLater = Validator::make($request->all(), [
                'open_amount' => 'required|numeric|min:0'
            ]);

            if($validateLater->fails())
            {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation fails',
                    'errors' => $validateLater->errors()
                ], 403);
            }

            $log = BoxLogs::create([
                'box_id' => $request->input('box_id'),
                'open_amount' => $request->input('open_amount'),
                'open_by' => Auth::user()->id,
                'open_time' => Carbon::now(),
                'collected_amount' => 0
            ]);

            return response()->json([
                'success' => true,
                'box' => $log
            ], 200);
        }
        else
        {
            $validateLater = Validator::make($request->all(), [
                'close_amount' => 'required|numeric|min:0'
            ]);

            if($validateLater->fails())
            {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation fails',
                    'errors' => $validateLater->errors()
                ], 403);
            }

            $boxLog->close_amount = $request->input('close_amount');
            $boxLog->close_by = Auth::user()->id;
            $boxLog->close_time = Carbon::now();

            $boxLog->save();

            return response()->json([
                'success' => true,
                'box' => $boxLog
            ],200);
        }
    }
}
