<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Boxs;
use App\Models\Role;
use Illuminate\Support\Facades\Validator;

class BoxController extends Controller
{
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
            'user_id' => 'required|integer',
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
            'user_id' => 'required|integer',
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
        return response()->json($boxs, 200);
    }   

}
