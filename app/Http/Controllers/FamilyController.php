<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\Role;
use App\Models\Subfamily;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\select;

class FamilyController extends Controller
{
    // Family
    public function createFamily(Request $request)
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
            'name' => 'required|string|max:255'
        ]);

        if($validateFamily->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateFamily->errors()
            ], 403);
        }

        $family = Family::create([
            'name' => $request->input('name')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Family added successfully.'
        ], 200);
    }

    public function getFamily()
    {
        $families = Family::all()->select('id', 'name');
        return response()->json($families, 200);
    }

    public function updateFamily(Request $request, $id)
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
            'name' => 'required|string|max:255'
        ]);

        if($validateFamily->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateFamily->errors()
            ], 403);
        }

        $family = Family::where('id', $id)->first();

        $family->name = $request->name;
        $family->save();

        return response()->json([
            'success' => true, 
            'family' => $family
        ], 200);
    }

    public function deleteFamily($id)
    {
        $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        $family = Family::where('id', $id)->first();
        $family->delete();
        return response()->json([
            'success' => true,
            'message' => "Family Deleted Successfully."
        ], 200);
    }

    // Sub Family 
    public function createSubFamily(Request $request)
    {
        $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        $validateSubFamily = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'family_id' => 'required|integer|exists:families,id'
        ]);

        if($validateSubFamily->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation Fails.',
                'errors' => $validateSubFamily->errors()
            ], 403);
        }

        Subfamily::create([
            'name' => $request->input('name'),
            'family_id' => $request->input('family_id')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sub Family Added Successfully.'
        ], 200);
    }
    public function getSubFamily()
    {
        $families = DB::table('subfamilies')
            ->leftJoin('families', 'subfamilies.family_id', '=', 'families.id')
            ->select('subfamilies.id', 'subfamilies.name', 'families.name as family_name')
            ->get();
        return response()->json($families, 200);
    }
    public function updateSubFamily(Request $request, $id)
    {
        $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        $validateSubFamily = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'family_id' => 'required|integer|exists:families,id'
        ]);

        if($validateSubFamily->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation Fails.',
                'errors' => $validateSubFamily->errors()
            ], 403);
        }

        $subfamily = Subfamily::find($id);
        $subfamily->name = $request->input('name');
        $subfamily->family_id = $request->input('family_id');
        
        $subfamily->save();

        return response()->json([
            'success' => true,
            'message' => 'Sub Family Updated Successfully.'
        ], 200);
    }
    public function deleteSubFamily($id)
    {
        $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        $family = Subfamily::where('id', $id)->first();
        $family->delete();
        return response()->json([
            'success' => true,
            'message' => "Sub Family Deleted Successfully."
        ], 200);
    }
    public function getMultipleSubFamily(Request $request)
    {
        $validateRequest = Validator::make($request->all(), [
            'families' => 'array',
            'families.*' => 'integer|exists:families,id' // Ensure each family ID is an integer and exists in the families table
        ]);

        if($validateRequest->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        $data = [];

        if(isset($request->families) && is_array($request->families))
        {
            foreach ($request->families as $key => $value) {
                $family = Family::where('id', $value)->first()->name;
                $subfamilies = Subfamily::where('family_id', $value)->get()->select('id', 'name');
                array_push($data, ['id' => $value, 'name' => $family, 'sub_family' => $subfamilies]); 
            }
        }
        else
        {
            $families = Family::all()->select('id', 'name');
            foreach ($families as $family) {
                $subfamilies = Subfamily::where('family_id', $family['id'])->get()->select('id', 'name');
                array_push($data, ['id' => $family['id'], 'name' => $family['name'], 'sub_family' => $subfamilies]); 
            }
        }
        
        return response()->json([
            'success'=>true,
            'data'=>$data
        ], 200);
    }
}
