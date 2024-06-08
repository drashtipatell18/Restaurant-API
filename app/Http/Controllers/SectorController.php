<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Sector;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SectorController extends Controller
{
    public function createSector(Request $request)
    {
        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 405);
        }

        $validateRequest = Validator::make($request->all(),[
            'name'=>'required|string|max:255',
            'noOfTables'=>'required|integer|min:1'
        ]);

        if($validateRequest->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        $sector = Sector::create([
            'name'=>$request->input('name')
        ]);

        $tables = [];

        for($i = 0; $i < $request->input('noOfTables'); $i++)
        {
            $table = Table::create([
                'user_id' => Auth()->user()->id,
                'sector_id' => $sector->id,
                'name'=>'Table ' . ($i+1)
            ]);

            array_push($tables, $table);
        }

        return response()->json([
            'success' => true, 
            'message' => 'Sector and Tabled added successfully.',
            'sector'=> $sector,
            'tables' => $tables
        ], 200);
    }
}
