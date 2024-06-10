<?php 
namespace App\Http\Controllers;

use App\Models\ProductionCenter;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductionCenterController extends Controller
{
    public function viewProductionCenter()
    {
        $productionCenters = ProductionCenter::all();
        return response()->json([
            'success' => true,
            'data' => $productionCenters
        ], 200);
    }

    public function storeProductionCenter(Request $request)
    {
        $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'printer_code' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 403);
        }

        $productionCenter = ProductionCenter::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $productionCenter,
            'message' => 'Production Center created successfully.'
        ], 200);
    }

    public function updateProductionCenter(Request $request, $id)
    {
        $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'printer_code' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 403);
        }

        $productionCenter = ProductionCenter::find($id);

        if (!$productionCenter) {
            return response()->json([
                'success' => false,
                'message' => 'Production Center not found'
            ], 404);
        }

        $productionCenter->update([
            'name' => $request->name,
            'printer_code' => $request->printer_code,
        ]);

        return response()->json([
            'success' => true,
            'data' => $productionCenter,
            'message' => 'Production Center updated successfully.'
        ], 200);
    }

    public function destroyProductionCenter($id)
    {
        $productionCenter = ProductionCenter::find($id);

        if (!$productionCenter) {
            return response()->json([
                'success' => false,
                'message' => 'Production Center not found'
            ], 401);
        }

        $productionCenter->delete();

        return response()->json([
            'success' => true,
            'message' => 'Production Center deleted successfully.'
        ], 200);
    }
    public function ProductionCentersearch(Request $request)
    {
        $Ids = $request->input('ids', []);
        $productioncenterQuery = ProductionCenter::query();
        if (!empty($Ids)) {
            $productioncenterQuery->whereIn('id', $Ids);
        }
        $productioncenter = $productioncenterQuery->get();
        return response()->json($productioncenter, 200);
    }   
}
