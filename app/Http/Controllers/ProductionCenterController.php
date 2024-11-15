<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ProductionCenter;
use App\Models\Item_Production_Join;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;
use App\Events\NotificationMessage;

class ProductionCenterController extends Controller
{
    public function viewProductionCenter(Request $request)
    {
        $productionCenters = ProductionCenter::where('admin_id', $request->admin_id)->get(); // Filter by admin_id
        return response()->json([
            'success' => true,
            'data' => $productionCenters
        ], 200);
    }

    public function storeProductionCenter(Request $request)
    {
        $role = Role::where('id', Auth()->user()->role_id)->first()->name;
        if ($role != "admin") {
            $errorMessage = 'No se pudo crear el centro de producción. Verifica la información ingresada e intenta nuevamente.';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            Notification::create([
                'user_id' => auth()->user()->id,
                'notification_type' => 'alert',
                'notification' => $errorMessage,
                 'path'=>'/productioncenter'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorised',
                'notification' => $errorMessage
            ], 401);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'printer_code' => 'nullable|integer',
        ]);
        $user = auth()->user();
        $admin_id = ($role == 'admin') ? auth()->user()->id : auth()->user()->admin_id;
        if ($validator->fails()) {
            $errorMessage = 'No se pudo crear el centro de producción. Verifica la información ingresada e intenta nuevamente.';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            Notification::create([
                'user_id' => auth()->user()->id,
                'notification_type' => 'alert',
                'notification' => $errorMessage,
                'admin_id' => $request->admin_id,
                'role_id' =>  $user->role_id,
                'path'=>'/productioncenter'
            ]);


            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
                'notification' => $errorMessage,
            ], 403);
        }
    
        $productionCenter = ProductionCenter::create([
            'name' => $request->name,
            'printer_code' => $request->printer_code,
            'admin_id' => $request->admin_id,
            'role_id' =>  $user->role_id
        ]);

        $successMessage = "La familia {$productionCenter->name} ha sido creada exitosamente.";
        broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
        Notification::create([
            'user_id' => auth()->user()->id,
            'notification_type' => 'notification',
            'notification' => $successMessage,
            'admin_id' => $request->admin_id,
            'role_id' =>  $user->role_id,
            'path'=>'/productioncenter'
        ]);
        return response()->json([
            'success' => true,
            'data' => $productionCenter,
            'message' => 'Production Center created successfully.',
            'notification' => $successMessage
        ], 200);
    }

    public function addToMenuProducation(Request $request)
    {
        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if ($role != "admin") {

            $errorMessage = 'No se pudo agregar el artículo al centro de producción. Verifica la información ingresada e intenta nuevamente.';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            Notification::create([
                'user_id' => auth()->user()->id,
                'notification_type' => 'alert',
                'notification' => $errorMessage,
                'path'=>'/productioncenter'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorised',
                'notification' => $errorMessage
            ], 401);
        }

        $validateRequest = Validator::make($request->all(), [
            'item_ids' => 'required|array',
            'item_ids.*' => 'integer|exists:items,id',
            'production_id' => 'required|exists:production_centers,id'
        ]);
        $user = auth()->user();
        $admin_id = ($role == 'admin') ? auth()->user()->id : auth()->user()->admin_id;

        if ($validateRequest->fails()) {
            $errorMessage = 'No se pudo agregar el artículo al centro de producción. Verifica la información ingresada e intenta nuevamente.';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            Notification::create([
                'user_id' => auth()->user()->id,
                'notification_type' => 'alert',
                'notification' => $errorMessage,
                'admin_id' => $request->admin_id,
                'role_id' =>  $user->role_id,
                'path'=>'/productioncenter'
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateRequest->errors()
            ], 403);
        }
        $production = ProductionCenter::find($request->production_id);
        foreach ($request->item_ids as $item_id) {
            Item_Production_Join::create([
                'production_id' => $production->id,
                'item_id' => $item_id,
                'admin_id' => $request->admin_id,
                'role_id' =>  $user->role_id
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Items added to production successfully"
        ], 200);
    }
    public function updateProductionCenter(Request $request, $id)
    {
        
        $role = Role::where('id', Auth()->user()->role_id)->first()->name;
        $user = auth()->user();
        $admin_id = ($role == 'admin') ? auth()->user()->id : auth()->user()->admin_id;
        if ($role != "admin") {
            $errorMessage = 'No se pudo crear el centro de producción. Verifica la información ingresada e intenta nuevamente.';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            Notification::create([
                'user_id' => auth()->user()->id,
                'notification_type' => 'alert',
                'notification' => $errorMessage,
                'admin_id' => $request->admin_id,
                'role_id' =>  $user->role_id,
                'path'=>'/productioncenter'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorised',
                'notification' => $errorMessage
            ], 401);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'printer_code' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            $errorMessage = 'No se pudo crear el centro de producción. Verifica la información ingresada e intenta nuevamente.';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            Notification::create([
                'user_id' => auth()->user()->id,
                'notification_type' => 'alert',
                'notification' => $errorMessage,
                'admin_id' => $request->admin_id,
                'role_id' =>  $user->role_id,
                'path'=>'/productioncenter'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
                'notification' => $errorMessage,
            ], 403);
        }

        $productionCenter = ProductionCenter::find($id);   
        if(is_null($productionCenter))
        {
            $errorMessage = 'No se pudo eliminar el centro de producción. Verifica si el centro está asociado a otros registros e intenta nuevamente.';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            Notification::create([
                'user_id' => auth()->user()->id,
                'notification_type' => 'alert',
                'notification' => $errorMessage,
                'admin_id' => $request->admin_id,
                'role_id' =>  $user->role_id,
                'path'=>'/productioncenter'
            ]);
            if (!$productionCenter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Production Center not found',
                    'notification' => $errorMessage,
                ], 404);
            }
        }

        $productionCenter->update([
            'name' => $request->name,
            'printer_code' => $request->printer_code,
        ]);
        $successMessage = "El centro de producción {$productionCenter->name} ha sido actualizado exitosamente";
        broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
        Notification::create([
            'user_id' => auth()->user()->id,
            'notification_type' => 'notification',
            'notification' => $successMessage,
            'admin_id' => $request->admin_id,
            'role_id' =>  $user->role_id,
             'path'=>'/productioncenter'
        ]);

        return response()->json([
            'success' => true,
            'data' => $productionCenter,
            'message' => 'Production Center updated successfully.',
            'notification' => $successMessage,
        ], 200);
    }

    // public function destroyProductionCenter(Request $request,$id)
    // {
    //     $role = Role::where('id', Auth()->user()->role_id)->first()->name;
    //     $user = auth()->user();
    //     $admin_id = ($role == 'admin') ? auth()->user()->id : auth()->user()->admin_id;

    //     $productionCenter = ProductionCenter::find($id);
    //     $errorMessage = 'No se pudo eliminar el centro de producción. Verifica si el centro está asociado a otros registros e intenta nuevamente.';
    //     broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
    //     Notification::create([
    //         'user_id' => auth()->user()->id,
    //         'notification_type' => 'alert',
    //         'notification' => $errorMessage,
    //         'admin_id' => $request->admin_id,
    //         'role_id' =>  $user->role_id
    //     ]);
    //     if (!$productionCenter) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Production Center not found',
    //             'notification' => $errorMessage,
    //         ], 404);
    //     }
        
    //      $itemProductionJoin = Item_Production_Join::where('production_id', $id)->delete();

    //     $productionCenter->delete();

    //     $successMessage = "El centro de producción { $productionCenter->name } ha sido eliminado del sistema.";

    //     broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
    //     Notification::create([
    //         'user_id' => auth()->user()->id,
    //         'notification_type' => 'notification',
    //         'notification' => $successMessage,
    //         'admin_id' => $request->admin_id,
    //         'role_id' =>  $user->role_id
    //     ]);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Production Center deleted successfully.',
    //         'notification' => $successMessage,
    //     ], 200);
    // }
    
    
    public function destroyProductionCenter(Request $request,$id)     
    {
        $role = Role::where('id', Auth()->user()->role_id)->first()->name;
        $user = auth()->user();
        $admin_id = ($role == 'admin') ? auth()->user()->id : auth()->user()->admin_id;

        $productionCenter = ProductionCenter::find($id);
       
        $errorMessage = 'No se pudo eliminar el centro de producción. Verifica si el centro está asociado a otros registros e intenta nuevamente.';
        broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
        Notification::create([
            'user_id' => auth()->user()->id,
            'notification_type' => 'alert',
            'notification' => $errorMessage,
            'admin_id' => $request->admin_id,
            'role_id' =>  $user->role_id,
             'path'=>'/productioncenter'
        ]);
        if (!$productionCenter) {
            return response()->json([
                'success' => false,
                'message' => 'Production Center not found',
                'notification' => $errorMessage,
            ], 404);
        }
        
        $itemProductionJoin = Item_Production_Join::where('production_id', $id)->delete();

        $productionCenter->delete();

        $successMessage = "El centro de producción { $productionCenter->name } ha sido eliminado del sistema.";

        broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
        Notification::create([
            'user_id' => auth()->user()->id,
            'notification_type' => 'notification',
            'notification' => $successMessage,
            'admin_id' => $request->admin_id,
            'role_id' =>  $user->role_id,
             'path'=>'/productioncenter'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Production Center deleted successfully.',
            'notification' => $successMessage,
        ], 200);
    }
    
    public function ProductionCentersearch(Request $request)
    {
        $Ids = $request->input('ids', []);
        $productioncenterQuery = ProductionCenter::query();
        if (!empty($Ids)) {
            $productioncenterQuery->whereIn('id', $Ids)->where('admin_id', $request->admin_id); 
        }
        $productioncenter = $productioncenterQuery->get();
        return response()->json($productioncenter, 200);
    }

    public function getProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'productioncenter_ids' => 'required|array',
            'productioncenter_ids.*' => 'integer|exists:production_centers,id'
        ]);

        if ($validator->fails()) {
            
            return response()->json([
                'success' => false,
                'message' => "Validation error",
                'errors' => $validator->errors()
            ], 403);
        }
        $items = Item::whereIn('production_center_id', $request->productioncenter_ids)
                     ->where('admin_id', $request->admin_id) 
                     ->get();
        return response()->json($items, 200);
    }
}
