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
use App\Models\Restauranttable;

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
        $admin_id = ($role == 'admin') ? auth()->user()->id : auth()->user()->admin_id;
        $usersRoles = User::where('admin_id', $admin_id)
            ->whereIn('role_id', [1, 2])
            ->orWhere('id', $admin_id)
            ->get();
        if ($validateRequest->fails()) {
            $errorMessage = 'No se pudo crear el sector. Verifica la información ingresada e intenta nuevamente.';
            if ($role != "admin" &&  $role != "cashier") {
                broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();

                //  Notification::create([
                //     'user_id' => $user->id, 
                //     'notification_type' => 'alert',
                //     'notification' => $errorMessage,
                //     'admin_id' => $request->admin_id,
                //     'role_id' => $user->role_id
                // ]);
                foreach ($usersRoles as $recipient) {
                    Notification::create([
                        'user_id' => $recipient->id,
                        'notification_type' => 'alert',
                        'notification' => $errorMessage,
                        'admin_id' => $admin_id,
                        'role_id' => $recipient->role_id,
                        'path'=> '/table'
                    ]);
                }
            }
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

 
        $lastTableNumber = Table::where('admin_id', $admin_id)
        ->max(DB::raw('CAST(table_no AS UNSIGNED)'));
    
        $nextTableNumber = $lastTableNumber ? $lastTableNumber + 1 : 1;
     

        $tables = [];
       
        for ($i = 0; $i < $request->input('noOfTables'); $i++) {
            $table = Table::create([
                'user_id' => Auth()->user()->id,
                'sector_id' => $sector->id,
                'admin_id' => $sector->admin_id,
                // 'name' => 'Mesa ' . ($i + 1),
                // 'table_no' => $nextTableNumber
                'name' => 'Mesa ' . ($nextTableNumber),
                'table_no' => $nextTableNumber
            ]);
            $nextTableNumber++;

            array_push($tables, $table);
        }
        $successMessage = "El sector {$sector->name} ha sido creado exitosamente con {$request->noOfTables} mesas asignadas.";
        broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
        foreach ($usersRoles as $recipient) {
            Notification::create([
                'user_id' => $recipient->id,
                'notification_type' => 'notification',
                'notification' => $successMessage,
                'admin_id' => $admin_id,
                'role_id' => $recipient->role_id,
                'path'=> '/table'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sector and Tabled added successfully.',
            'sector' => $sector,
            'tables' => $tables,
            'notification' => $successMessage
        ], 200);
    }

    public function deleteSector(Request $request, $id)
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
        $admin_id = ($role == 'admin') ? auth()->user()->id : auth()->user()->admin_id;
        $usersRoles = User::where('admin_id', $admin_id)
            ->whereIn('role_id', [1, 2])
            ->orWhere('id', $admin_id)
            ->get();

        if (!$sector) {
            $errorMessage = 'No se pudo eliminar el sector. Verifica si el sector está asociado a otros registros e intenta nuevamente..';
            if ($role != "admin" && $role != "cashier") {
                broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
                foreach ($usersRoles as $recipient) {
                    Notification::create([
                        'user_id' => $recipient->id,
                        'notification_type' => 'alert',
                        'notification' => $errorMessage,
                        'admin_id' => $admin_id,
                        'role_id' => $recipient->role_id,
                        'path'=> '/table'
                    ]);
                }
            }

            //  Notification::create([
            //     'user_id' => $user->id, 
            //     'notification_type' => 'alert',
            //     'notification' => $errorMessage,
            //     'admin_id' => $adminId,
            //     'role_id' => $user->role_id
            // ]);
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
        foreach ($usersRoles as $recipient) {
            Notification::create([
                'user_id' => $recipient->id,
                'notification_type' => 'notification',
                'notification' => $successMessage,
                'admin_id' => $admin_id,
                'role_id' => $recipient->role_id,
                'path'=> '/table'
            ]);
        }
        // Notification::create([
        //     'user_id' =>  $user->id,
        //     'notification_type' => 'notification',
        //     'notification' => $successMessage,
        //     'admin_id' => $adminId,
        //      'role_id' => $user->role_id
        // ]);

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

                ->get(['id', 'name', 'status','table_no']);
               
            $tableData = [];

            // Loop through each table
            foreach ($tables as $table) {
                // Prepare table info
                $tableInfo = [
                    'id' => $table->id,
                    'name' => $table->name,
                    'status' => $table->status,
                    'table_no'=>$table->table_no
                ];

                // Check if table is busy and get the latest order ID and user_id
                if ($table->status === 'busy') {
                    $order = OrderMaster::where('table_id', $table->id)
                        ->where('admin_id', $sector->admin_id)
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

    public function updateTableName(Request $request)
    {
        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if ($role != "admin" && $role != "cashier") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 405);
        }

        $validateRequest = Validator::make($request->all(), [
            'table_id' => 'required|exists:restauranttables,id',
            'name' => 'required'
        ]);

        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        $table = Table::find($request->table_id);
        $table->name = $request->name;
        $table->save();

        return response()->json([
            'success' => true,
            'message' => 'Table updated to ' . $table->name
        ], 200);
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
        $admin_id = ($role == 'admin') ? auth()->user()->id : auth()->user()->admin_id;
        $usersRoles = User::where('admin_id', $admin_id)
            ->whereIn('role_id', [1, 2, 3])
            ->orWhere('id', $admin_id)
            ->get();
        if ($validateRequest->fails()) {
            $errorMessage = 'No se pudo liberar la mesa. Verifica si el pedido está cerrado o si hay problemas de conexión e intenta nuevamente.';
            if ($role != "admin" &&  $role != "cashier" && $role != "waitress") {
                broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
                foreach ($usersRoles as $recipient) {
                    //  Notification::create([
                    //     'user_id' => $user->id, 
                    //     'notification_type' => 'alert',
                    //     'notification' => $errorMessage,
                    //     'admin_id' => $request->admin_id,
                    //     'role_id' => $user->role_id
                    // ]);
                    Notification::create([
                        'user_id' => $recipient->id,
                        'notification_type' => 'alert',
                        'notification' => $errorMessage,
                        'admin_id' => $admin_id,
                        'role_id' => $recipient->role_id,
                        'path'=> '/table'
                    ]);
                }
            }

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


        if ($previousStatus  !== $request->status) {
            $table->status = $request->status;
            $table->save();

            $sectorName = $table->sector->name ?? 'Unknown Sector';

            if ($previousStatus === 'busy') {
                $successMessage = "La mesa {$table->id} ha sido liberada y está disponible para nuevos pedidos.";
                broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
                foreach ($usersRoles as $recipient) {
                    Notification::create([
                        'user_id' => $recipient->id,
                        'notification_type' => 'notification',
                        'notification' => $successMessage,
                        'admin_id' => $admin_id,
                        'role_id' => $recipient->role_id,
                        'path'=> '/table'
                    ]);
                }
                // Notification::create([
                //     'user_id' => auth()->user()->id,
                //     'notification_type' => 'notification',
                //     'notification' => $successMessage,
                //     'admin_id' => $request->admin_id,
                //     'role_id' => $user->role_id
                // ]);

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

    public function deleteTable(Request $request, $id)
    {
        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if ($role != "admin" && $role != "cashier") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 405);
        }

        $table = Table::find($id);
        $table->delete();

        return response()->json([
            'success' => true,
            'message' => 'Table deleted successfully.'
        ], 200);
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
        $admin_id = ($role == 'admin') ? auth()->user()->id : auth()->user()->admin_id;
        $usersRoles = User::where('admin_id', $admin_id)
            ->whereIn('role_id', [1, 2])
            ->orWhere('id', $admin_id)
            ->get();
        if ($validateRequest->fails()) {
            // dd($validateRequest->fails());
            if ($role != "admin" &&  $role != "cashier") {
                $errorMessage = 'No se pudo crear la mesa. Verifica la información ingresada e intenta nuevamente.';
                broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
                foreach ($usersRoles as $recipient) {
                    Notification::create([
                        'user_id' => $recipient->id,
                        'notification_type' => 'alert',
                        'notification' => $errorMessage,
                        'admin_id' => $admin_id,
                        'role_id' => $recipient->role_id,
                        'path'=> '/table'
                    ]);
                }
                // Notification::create([
                //     'user_id' => $user->id, 
                //     'notification_type' => 'alert',
                //     'notification' => $errorMessage,
                //     'admin_id' => $request->admin_id,
                //     'role_id' => $user->role_id
                // ]);


                return response()->json([
                    'success' => false,
                    'message' => 'Validation fails',
                    'errors' => $validateRequest->errors(),
                    'alert' => $errorMessage
                ], 403);
            }
        }

        // $lastTableName = Table::orderBy('table_no', 'desc')->first();
        // $lastTableNo = $lastTableName ? $lastTableName->table_no : 0;
    

        // $lastTableName = Table::all()->where('sector_id', $request->sector_id)->last();
        // $lastTable = explode(' ', $lastTableName->name);
        // $lastNo = $lastTable[1];
        // $lastTableNo = $lastTableName->table_no;
       
        $lastTableNo = Table::where('admin_id', $admin_id)
        ->max(DB::raw('CAST(table_no AS UNSIGNED)'));
       

        $tables = [];
        for ($i = 0; $i < $request->noOfTables; $i++) {
            $lastTableNo++;
            $table = Table::create([
                'user_id' => Auth()->user()->id,
                'sector_id' => $request->sector_id,
                'admin_id' => $request->admin_id,
                // 'name' => 'Mesa ' . (++$lastNo),
                // 'table_no'=>++$lastTableNo
                'name' => 'Mesa ' . $lastTableNo,
                'table_no' => $lastTableNo 
            ]);

            array_push($tables, $table);
        }

        $successMessage = "La mesa $request->noOfTables ha sido creada exitosamente en el sector .";
        broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
        foreach ($usersRoles as $recipient) {

            Notification::create([
                'user_id' => $recipient->id,
                'notification_type' => 'notification',
                'notification' => $successMessage,
                'admin_id' => $admin_id,
                'role_id' => $recipient->role_id,
                'path'=> '/table'
            ]);
        }
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
// dd($orderDetails);
            $total = 0;
            foreach ($orderDetails as $detail) {
                $item = Item::find($detail->item_id);
                if ($item) {
                    // Add product_id to the order detail
                    $detail->production_center_id = $item->production_center_id ;
                }
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
           
        ]);
        $admin_id = ($role == 'admin') ? auth()->user()->id : auth()->user()->admin_id;

        $usersRoles = User::where('admin_id', $admin_id)
            ->whereIn('role_id', [1, 2])
            ->orWhere('id', $admin_id)
            ->get();
        if ($validateRequest->fails()) {
            if ($role != "admin" &&  $role != "cashier") {
                $errorMessage = 'No se pudo actualizar el sector. Verifica la información ingresada e intenta nuevamente.';
                broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();

                // Notification::create([
                //     'user_id' => $user->id,
                //     'notification_type' => 'alert',
                //     'notification' => $errorMessage,
                //     'admin_id' => $request->admin_id,
                //     'role_id' => $user->role_id
                // ]);
                foreach($usersRoles as $recipient){
                    Notification::create([
                        'user_id' => $recipient->id,
                        'notification_type' => 'alert',
                        'notification' => $errorMessage,
                        'admin_id' => $admin_id,
                        'role_id' => $recipient->role_id,
                        'path'=> '/table'
                    ]);
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Validation fails',
                    'errors' => $validateRequest->errors(),
                    'alert' => $errorMessage
                ], 403);
            }
        }

        $sector = Sector::findOrFail($id);
        $sector->update([
            'name' => $request->input('name')
        ]);

        // $tables = [];

        // Delete existing tables
        // Table::where('sector_id', $sector->id)->delete();

        // Create new tables
        // for ($i = 0; $i < $request->input('noOfTables'); $i++) {
        //     $table = Table::create([
        //         'user_id' => Auth::user()->id,
        //         'sector_id' => $sector->id,
        //         'admin_id' => $sector->admin_id,
        //         'name' => 'Table ' . ($i + 1)
        //     ]);

        //     array_push($tables, $table);
        // }

        $successMessage = "El sector $sector->name ha sido actualizado exitosamente.";
        broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
        foreach($usersRoles as $recipient){
            Notification::create([
                'user_id' => $recipient->id,
                'notification_type' => 'notification',
                'notification' => $successMessage,
                'admin_id' => $admin_id,
                'role_id' => $recipient->role_id,
                'path'=> '/table'
            ]);
        }
        // Notification::create([
        //     'user_id' => auth()->user()->id,
        //     'notification_type' => 'notification',
        //     'notification' => $successMessage,
        //     'admin_id' => $request->admin_id,
        //     'role_id' => $user->role_id
        // ]);

        return response()->json([
            'success' => true,
            'message' => 'Sector and Tables updated successfully.',
            'sector' => $sector,
            // 'tables' => $tables,
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
