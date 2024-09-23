<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\OrderDetails;
use App\Models\OrderMaster;
use App\Models\Role;
use App\Models\Sector;
use App\Models\Table;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Notification;
use App\Events\NotificationMessage;

class SectorController extends Controller
{
    public function createSector(Request $request)
    {
        $user = auth()->user();
        $adminId = $user->role_id == 1 ? $user->id : $user->admin_id;

        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if ($role != "admin" && $role != "cashier") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 405);
        }

        $validateRequest = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'noOfTables' => 'required|integer|min:1'
        ]);

        if ($validateRequest->fails()) {
            $errorMessage = 'No se pudo crear el sector. Verifica la información ingresada e intenta nuevamente.';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
             Notification::create([
                'user_id' => $user->id, 
                'notification_type' => 'alert',
                'notification' => $errorMessage,
                'admin_id' => $request->admin_id,
                'role_id' => $user->role_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors(),
                'alert' => $errorMessage
            ], 403);
        }

        $sector = Sector::create([
            'name' => $request->input('name'),
            'admin_id' => $request->input('admin_id')
        ]);
        

        $tables = [];

        for ($i = 0; $i < $request->input('noOfTables'); $i++) {
            $table = Table::create([
                'user_id' => Auth()->user()->id,
                'sector_id' => $sector->id,
                'admin_id' => $sector->admin_id,
                'name' => 'Table ' . ($i + 1)
            ]);

            array_push($tables, $table);
        }

        $successMessage = "El sector {$sector->name} ha sido creado exitosamente con {$request->noOfTables} mesas asignadas.";
        broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
        Notification::create([
            'user_id' => auth()->user()->id,
            'notification_type' => 'notification',
            'notification' => $successMessage,
             'admin_id' => $request->admin_id,
             'role_id' => $user->role_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sector and Tabled added successfully.',
            'sector' => $sector,
            'tables' => $tables,
            'notification'=> $successMessage
        ], 200);
    }

    public function deleteSector(Request $request,$id)
    {

        $user = auth()->user();
        $adminId = $user->role_id == 1 ? $user->id : $user->admin_id;
        

        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if ($role != "admin" && $role != "cashier") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 405);
        }
        $sector = Sector::find($id);

        if (!$sector) {
            $errorMessage = 'No se pudo eliminar el sector. Verifica si el sector está asociado a otros registros e intenta nuevamente..';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
             Notification::create([
                'user_id' => $user->id, 
                'notification_type' => 'alert',
                'notification' => $errorMessage,
                'admin_id' => $adminId,
                'role_id' => $user->role_id
            ]);
            return response()->json([
                'success' => false,
                'alert' => $errorMessage

            ], 404);
        }

    
            $tables = Table::all()->where('sector_id', $id);
            foreach ($tables as $table) {
                $table->delete();
            }
           
            $sector->delete();
    
            $successMessage = "El sector $sector->name ha sido eliminado del sistema.";
            broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
            Notification::create([
                'user_id' =>  $user->id,
                'notification_type' => 'notification',
                'notification' => $successMessage,
                'admin_id' => $adminId,
                 'role_id' => $user->role_id
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'Sector deleted successfully.',
                'notification' => $successMessage
            ], 200);
    }

    public function getSector(Request $request)
    {
        $sections = Sector::where('admin_id', $request->admin_id)->get();
        $sectorsWithTableCount = $sections->map(function ($sector) {
            $tableCount = Table::where('sector_id', $sector->id)
                ->where('admin_id', $sector->admin_id) // Replace $adminId with the actual admin ID variable
                ->count();
            return [
                'id' => $sector->id,
                'name' => $sector->name,
                'admin_id' => $sector->admin_id,
                'noOfTables' => $tableCount
            ];
        });

        return response()->json([
            'success' => true,
            'sectors' => $sectorsWithTableCount
        ], 200);
    }

    public function getSectionWithTable(Request $request)
    {
        // Validate the request
        $validateRequest = Validator::make($request->all(), [
            'sectors' => 'array',
            'sectors.*' => 'integer|exists:sectors,id'
        ]);
        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        // Initialize the data array
        $data = [];

        if (isset($request->sectors) && is_array($request->sectors)) {
            // Get sectors based on provided IDs
            $sectors = Sector::whereIn('id', $request->sectors)
                ->where('admin_id', $request->admin_id)
                ->get(['id', 'name', 'admin_id']);
        } else {
            // Get all sectors
            $sectors = Sector::where('admin_id', $request->admin_id)
                ->get(['id', 'name', 'admin_id']);
        }
        // Loop through each sector
        foreach ($sectors as $sector) {
            // Get tables for the sector
            $tables = Table::where('sector_id', $sector->id)
                ->where('admin_id', $sector->admin_id)

                ->get(['id', 'name', 'status']);
            $tableData = [];

            // Loop through each table
            foreach ($tables as $table) {
                // Prepare table info
                $tableInfo = [
                    'id' => $table->id,
                    'name' => $table->name,
                    'status' => $table->status,
                ];

                // Check if table is busy and get the latest order ID and user_id
                if ($table->status === 'busy') {
                    $order = OrderMaster::where('table_id', $table->id)
                        ->where('admin_id', $table->admin_id)
                        ->latest()->first(['id', 'user_id']);
                    if ($order) {
                        $tableInfo['order_id'] = $order->id;
                        $tableInfo['user_id'] = $order->user_id;
                    }
                }

                // Add table info to table data array
                $tableData[] = $tableInfo;
            }

            // Add sector info to data array
            $data[] = [
                'id' => $sector->id,
                'name' => $sector->name,
                'admin_id' => $sector->admin_id,
                'tables' => $tableData,
            ];
        }

        // Return response
        return response()->json(["success" => true, "data" => $data], 200);
    }

    public function updateTableStatus(Request $request)
    {
        $user = auth()->user();
        $adminId = $user->role_id == 1 ? $user->id : $user->admin_id;

        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if ($role != "admin" && $role != "cashier" && $role != "waitress") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 405);
        }

        $validateRequest = Validator::make($request->all(), [
            'table_id' => 'required|exists:restauranttables,id',
            'status' => 'required|in:available,busy'
        ]);

        if ($validateRequest->fails()) {

            $errorMessage = 'Could not free the table. Check if the order is closed or if there are connection problems and try again.';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
             Notification::create([
                'user_id' => $user->id, 
                'notification_type' => 'alert',
                'notification' => $errorMessage,
                'admin_id' => $request->admin_id,
                'role_id' => $user->role_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors(),
                'alert' => $errorMessage
            ], 403);
        }

        $table = Table::where('id', $request->table_id)
            ->where('admin_id', $request->admin_id) // Ensure admin_id matches
            ->with('sector')
            ->first();

        $previousStatus = $table->status;
            

        if($previousStatus  !== $request->status)
        {
            $table->status = $request->status;
            $table->save();

            $sectorName = $table->sector->name ?? 'Unknown Sector';

            if($previousStatus === 'busy'){
                $successMessage = "La mesa {$table->id} ha sido liberada y está disponible para nuevos pedidos." ;
                broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
                Notification::create([
                    'user_id' => auth()->user()->id,
                    'notification_type' => 'notification',
                    'notification' => $successMessage,
                    'admin_id' => $request->admin_id,
                    'role_id' => $user->role_id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Table updated to ' . $table->status,
                    'notification' => $successMessage
                ], 200);
            }
            return response()->json([
                'success' => true,
                'message' => 'Table updated to ' . $table->status,
            ], 200);
            
        }
       
     
    }

    public function addTables(Request $request)
    {
        $user = auth()->user();
        $adminId = $user->role_id == 1 ? $user->id : $user->admin_id;

        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if ($role != "admin" && $role != "cashier") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 405);
        }

        $validateRequest = Validator::make($request->all(), [
            'sector_id' => 'required|exists:sectors,id',
            'noOfTables' => 'required|integer|min:1'
        ]);

        if ($validateRequest->fails()) {

            $errorMessage = 'No se pudo crear la mesa. Verifica la información ingresada e intenta nuevamente..';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
             Notification::create([
                'user_id' => $user->id, 
                'notification_type' => 'alert',
                'notification' => $errorMessage,
                'admin_id' => $request->admin_id,
                'role_id' => $user->role_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors(),
                'alert' => $errorMessage
            ], 403);
        }

        $lastTableName = Table::all()->where('sector_id', $request->sector_id)->last();

        $lastTable = explode(' ', $lastTableName->name);
        $lastNo = $lastTable[1];

        $tables = [];

        for ($i = 0; $i < $request->noOfTables; $i++) {
            $table = Table::create([
                'user_id' => Auth()->user()->id,
                'sector_id' => $request->sector_id,
                'admin_id' => $request->admin_id,
                'name' => 'Table ' . (++$lastNo)
            ]);

            array_push($tables, $table);
        }

        $successMessage = "La mesa $request->noOfTables ha sido creada exitosamente en el sector $table->name." ;
        broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
        Notification::create([
            'user_id' => auth()->user()->id,
            'notification_type' => 'notification',
            'notification' => $successMessage,
             'admin_id' => $request->admin_id,
             'role_id' => $user->role_id
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Tables added successfully',
            'tables' => $tables,
            'notification' => $successMessage
        ], 200);
    }

    public function getTableStats(Request $request, $id)
    {
        $table = Table::where('id', $id)
            ->where('admin_id', $request->admin_id) // Ensure admin_id matches
            ->first();
        if ($table == null) {
            return response()->json([
                'success' => false,
                'message' => 'Table id invalid'
            ], 403);
        }
        $responseData = [];

        $ordersQuery = OrderMaster::where('table_id', $id)
            ->where('admin_id', $request->admin_id);
           
        if ($request->has('from_month') && $request->has('to_month')) {
            $startDate = Carbon::create(null, $request->query('from_month'), 1)->startOfMonth();
            $endDate = Carbon::create(null, $request->query('to_month'), 1)->endOfMonth();
            $ordersQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        $orders = $ordersQuery->get();
        
        foreach ($orders as $order) {
            $customer = User::where('id', $order->user_id)
                ->where('admin_id', $request->admin_id) // Ensure admin_id matches
                ->first();

            if ($customer != null) {
                $order->customer = $customer->name;
            }

            $orderDetails = OrderDetails::where('order_master_id', $order->id)
                ->where('admin_id', $request->admin_id) // Ensure admin_id matches
                ->get();
            $order->items = $orderDetails;

            $total = 0;
            foreach ($orderDetails as $detail) {
                $detail->total = $detail->quantity * $detail->amount;
                $total += $detail->total;
            }
            $order->order_total = $total;

            $responseData[] = $order;
        }

        return response()->json($responseData, 200);
    }

    public function getKds($table_id)
    {
        $table = Table::find($table_id);

        if ($table == null) {
            return response()->json([
                'success' => false,
                'message' => "Table id invalid"
            ], 403);
        }

        $responseData = ["received" => [], "prepared" => [], "finalized" => [], "delivered" => []];

        $orderMaster = OrderMaster::where('table_id', $table->id)->get();
        foreach ($orderMaster as $order) {
            $orderDetails = OrderDetails::where('order_master_id', $order->id)->get();
            $data = [
                "order_id" => $order->id,
                "from" => Carbon::parse($order->created_at)->format('H:i'),
                "items" => [],
                "notes" => $order->notes
            ];
            foreach ($orderDetails as $orderDetail) {
                $item = Item::find($orderDetail->item_id);
                $data["items"][] = $item->name . "(x" . $orderDetail->quantity . " qty)";
            }

            if ($order->status == "received") {
                $responseData["received"][] = $data;
            }
            if ($order->status == "prepared") {
                $responseData["prepared"][] = $data;
            }
            if ($order->status == "finalized") {
                $responseData["finalized"][] = $data;
            }
            if ($order->status == "delivered") {
                $data["finished_at"] = Carbon::parse($order->updated_at)->format('H:i');
                $responseData["delivered"][] = $data;
            }
        }

        return response()->json($responseData, 200);
    }
    public function getSectorByTableId($tableId)
    {
        $table = Table::find($tableId);

        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Table not found'
            ], 404);
        }

        $sector = Sector::where('id', $table->sector_id)
        ->where('admin_id', $table->admin_id) // Ensure admin_id matches
        ->first();

        if (!$sector) {
            return response()->json([
                'success' => false,
                'message' => 'Sector not found for this table'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'sector' => [
                'id' => $sector->id,
                'name' => $sector->name
            ]
        ], 200);
    }

    public function updateSector(Request $request, $id)
    {
        $user = auth()->user();
        $adminId = $user->role_id == 1 ? $user->id : $user->admin_id;

        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if ($role != "admin" && $role != "cashier") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 405);
        }

        $validateRequest = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'noOfTables' => 'required|integer|min:1'
        ]);

        if ($validateRequest->fails()) {
            $errorMessage = 'No se pudo actualizar el sector. Verifica la información ingresada e intenta nuevamente.';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
             Notification::create([
                'user_id' => $user->id, 
                'notification_type' => 'alert',
                'notification' => $errorMessage,
                'admin_id' => $request->admin_id,
                'role_id' => $user->role_id
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors(),
                'alert' => $errorMessage
            ], 403);
        }

        $sector = Sector::findOrFail($id);
        $sector->update([
            'name' => $request->input('name')
        ]);

        $tables = [];

        // Delete existing tables
        Table::where('sector_id', $sector->id)->delete();

        // Create new tables
        for ($i = 0; $i < $request->input('noOfTables'); $i++) {
            $table = Table::create([
                'user_id' => Auth::user()->id,
                'sector_id' => $sector->id,
                'admin_id' => $sector->admin_id,
                'name' => 'Table ' . ($i + 1)
            ]);

            array_push($tables, $table);
        }

        $successMessage = "El sector $sector->name ha sido actualizado exitosamente.";
        broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
        Notification::create([
            'user_id' => auth()->user()->id,
            'notification_type' => 'notification',
            'notification' => $successMessage,
             'admin_id' => $request->admin_id,
             'role_id' => $user->role_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sector and Tables updated successfully.',
            'sector' => $sector,
            'tables' => $tables,
            'notification' => $successMessage
        ], 200);
    }
    public function getTableSingle(Request $request, $id)
    {
        $table = Table::find($id);
        return response()->json([
            'success' => true,
            'message' => 'Table select .',
            'tables' => $table
        ], 200);
    }
}
