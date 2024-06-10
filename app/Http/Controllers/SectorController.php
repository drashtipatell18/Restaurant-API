<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Sector;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    public function deleteSector($id)
    {
        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 405);
        }

        $tables = Table::all()->where('sector_id', $id);
        foreach ($tables as $table) {
            $table->delete();
        }
        $sector = Sector::find($id);
        $sector->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sector deleted successfully.'
        ], 200);
    }

    public function getSector()
    {
        $sections = Sector::all('id', 'name')->all();
        return response()->json([
            'success' => true,
            'sectors' => $sections
        ], 200);
    }

    public function getSectionWithTable(Request $request)
    {
        $validateRequest = Validator::make($request->all(), [
            'sectors' => 'array',
            'sectors.*' => 'integer|exists:sectors,id'
        ]);

        if($validateRequest->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        if(isset($request->sectors) && is_array($request->sectors))
        {
            $sectors = Sector::all('id', 'name')->whereIn('id', $request->sectors);
            $data = [];
            foreach ($sectors as $sector) {
                $table = Table::where('sector_id', $sector['id'])->get(['id', 'name', 'status']);
                $pushdata = ['id' => $sector['id'], 'name' => $sector['name'], 'tables' => $table];
                array_push($data, $pushdata);
            }
            return response()->json(["success" => true, "data" => $data], 200);
        }
        else
        {
            $sectors = Sector::all('id', 'name');
            $data = [];
            foreach ($sectors as $sector) {
                $table = Table::where('sector_id', $sector['id'])->get(['id', 'name', 'status']);
                $pushdata = ['id' => $sector['id'], 'name' => $sector['name'], 'tables' => $table];
                array_push($data, $pushdata);
            }
            return response()->json(["success" => true, "data" => $data], 200);
        }
    }

    public function updateTableStatus(Request $request)
    {
        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 405);
        }
        $validateRequest = Validator::make($request->all(), [
            'table_id' => 'required|exists:restauranttables,id',
            'status' => 'required|in:available,busy'
        ]);

        if($validateRequest->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        $table = Table::find($request->table_id);
        $table->status = $request->status;
        $table->save();

        return response()->json([
            'success' => true,
            'message' => 'Table updated to ' . $table->status
        ], 200);
    }

    public function addTables(Request $request)
    {
        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 405);
        }

        $validateRequest = Validator::make($request->all(), [
            'sector_id' => 'required|exists:sectors,id',
            'noOfTables' => 'required|integer|min:1'
        ]);

        if($validateRequest->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        $lastTableName = Table::all()->where('sector_id', $request->sector_id)->last();

        $lastTable = explode(' ', $lastTableName->name);
        $lastNo = $lastTable[1];

        $tables = [];

        for($i = 0; $i < $request->noOfTables; $i++)
        {
            $table = Table::create([
                'user_id' => Auth()->user()->id,
                'sector_id' => $request->sector_id,
                'name'=>'Table ' . (++$lastNo)
            ]);

            array_push($tables, $table);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tables added successfully',
            'tables' => $tables
        ], 200);
    }
}
