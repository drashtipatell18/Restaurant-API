<?php

namespace App\Http\Controllers;

use App\Models\BoxLogs;
use App\Models\Boxs;
use App\Models\kds;
use App\Models\Item;
use App\Models\OrderDetails;
use App\Models\OrderMaster;
use App\Models\OrderStatusLog;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\CreditNot;
use App\Models\ReturnItem;
use App\Models\Notification;
use App\Events\NotificationMessage;
use App\Models\User;

class OrderController extends Controller
{
    public function getOrderLog($id)
    {
        $orderLogs = OrderStatusLog::where('order_id', $id)->get();
        return response()->json([
            'success' => true,
            'message' => 'Log retrieved successfully.',
            'logs' => $orderLogs
        ], 200);
    }

    public function placeOrder(Request $request)
    {
        // dd($request->all());
        $role = Role::where('id', Auth()->user()->role_id)->first()->name;
        if ($role != "admin" && $role != "cashier" && $role != "waitress") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        if (!$request->has('order_master')) {
            return response()->json([
                'success' => false,
                'message' => "Validation fails"
            ], 403);
        }
        $validateRequest = Validator::make($request->order_master, [
            'order_type' => 'required|in:delivery,local,withdraw',
            'payment_type' => 'in:cash,debit,credit,transfer',
            'status' => 'required|in:received,prepared,delivered,finalized',
            'discount' => 'required|min:0',
            'delivery_cost' => 'required|min:0',
            'transaction_code' => 'boolean'
        ]);

        $admin_id = ($role == 'admin') ? auth()->user()->id : auth()->user()->admin_id;
        $usersRoles = User::where('admin_id', $admin_id)
        ->whereIn('role_id', [1, 2, 3])
        ->orWhere('id', $admin_id)
        ->get();

        if ($validateRequest->fails()) {
            $errorMessage = 'No se pudo crear el pedido. Verifica la información ingresada e intenta nuevamente.';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            foreach ($usersRoles as $recipient) {
                Notification::create([
                    'user_id' => $recipient->id,
                    'notification_type' => 'notification',
                    'notification' => $errorMessage,
                    'admin_id' => $admin_id,
                    'role_id' => $recipient->role_id
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => "Validation fails",
                'errors' => $validateRequest->errors(),
                'notification' => $errorMessage
            ], 403);
        }

        $orderMaster = [
            'order_type' => $request->order_master['order_type'],
            'payment_type' => $request->order_master['payment_type'],
            'status' => $request->order_master['status'],
            'discount' => $request->order_master['discount'],
            'delivery_cost' => $request->order_master['delivery_cost'],
            'customer_name' => $request->order_master['customer_name'],
            'person' => $request->order_master['person'],
            'reason' => $request->order_master['reason'],
            'admin_id' => $request->admin_id
        ];

        // dd($request->admin_id);
        // Generate and add transaction code if requested
        if (isset($request->order_master['transaction_code']) && $request->order_master['transaction_code'] === true) {
            do {
                $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            } while (OrderMaster::where('transaction_code', $code)->exists());

            $orderMaster['transaction_code'] = $code;
        }

        if ($role == "cashier") {
            $box = Boxs::where('user_id', Auth::user()->id)->latest()->first();
            // dd($box);

            if (!$box) {
                return response()->json([
                    'success' => false,
                    'message' => 'Box not found'
                ], 403);
            }

            $log = BoxLogs::where('box_id', $box->id)->latest()->first();
          


            // $log->collected_amount += $totalAmount;

            if ($log == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'caja no abierta'
                ], 403);
            } else if ($log->close_time != null) {
                return response()->json([
                    'success' => false,
                    'message' => 'caja no abierta'
                ], 403);
            }

            // if(empty($log->order_master_id))
            // {
            //     $log->order_master_id = $order->id;
            // }
            // else
            // {
            //     $log->order_master_id .= "," . $order->id;
            // }

            // if(empty($log->payment_id))
            // {
            //     $log->payment_id = $order->payment_id;
            // }
            // else
            // {
            //     $log->payment_id .= "," . $order->payment_id;
            // }

            // $log->save();
        }

        if ($role == "admin") {
            $orderMaster['admin_id'] = Auth::user()->id;
        }

        if (isset($request->order_master['table_id'])) {
            $orderMaster['table_id'] = $request->order_master['table_id'];
        }
        if (isset($request->order_master['user_id'])) {
            $orderMaster['user_id'] = $request->order_master['user_id'];
        }
        if ($role != "cashier" && isset($request->order_master['box_id'])) {
            $orderMaster['box_id'] = $request->order_master['box_id'];
        }
        if (isset($request->order_master['tip'])) {
            $orderMaster['tip'] = $request->order_master['tip'];
        }
        if (isset($request->order_master['customer_name'])) {
            $orderMaster['customer_name'] = $request->order_master['customer_name'];
        }
        if (isset($request->order_master['person'])) {
            $orderMaster['person'] = $request->order_master['person'];
        }

        if ($role == "cashier") {
            $orderMaster['box_id'] = Boxs::where('user_id', Auth::user()->id)->get()->first()->id;
        }

        $order = OrderMaster::create($orderMaster);
        $response = ["order_master" => $order, "order_details" => []];
        $totalAmount = 0;
        foreach ($request->order_details as $order_detail) {
            $item = Item::find($order_detail['item_id']);
            $orderDetail = OrderDetails::create([
                'order_master_id' => $order->id,
                'item_id' => $order_detail['item_id'],
                'amount' => $item->sale_price,
                'cost' => $item->cost_price,
                'notes' => $order_detail['notes'],
                'quantity' => $order_detail['quantity'],
                'admin_id' => $request->admin_id
                                                
            ]);

            $totalAmount += $item->sale_price * $order_detail['quantity'];

            array_push($response['order_details'], $orderDetail);
        }
        if(isset($request->order_master['box_id'])){
            if ($role == "cashier" || $role == "admin" ) {
                $box = Boxs::where('id', $request->order_master['box_id'])->latest()->first();
                $log = BoxLogs::where('box_id', $box->id)->latest()->first();
                   
                    if (empty($log->order_master_id)) {
                        $log->order_master_id = $order->id; // If no value exists, store the order_id directly
                    } else {
                        // Check if the order_id is already in the list
                        $existingOrderIds = explode(',', $log->order_master_id);
                        if (!in_array($order->id, $existingOrderIds)) {
                            $log->order_master_id .= "," . $order->id; // Append only if not already present
                        }
                    }
                   
                
               
                // dd($box->id);
                // $log->collected_amount += $totalAmount;
                // if (empty($log->order_master_id)) {
                //     $log->order_master_id = $order->id;
                // } else {
                //     $log->order_master_id .= "," . $order->id;
                // }
    
    
                // if (empty($log->order_master_id)) {
                //     $log->order_master_id = $order->id; // If no value exists, store the order_id directly
                // } else {
                //     // Check if the order_id is already in the list
                //     $existingOrderIds = explode(',', $log->order_master_id);
                //     if (!in_array($order->id, $existingOrderIds)) {
                //         $log->order_master_id .= "," . $order->id; // Append only if not already present
                //     }
                // }
    
                // if(empty($log->payment_id))
                // {
                //     $log->payment_id .= "," . $order->payment_type;
                // }
                // else
                // {
                //     $log->payment_id .= "," . $order->payment_id;
                // }
    
    
                $log->save();
            }
        }
        

        $kdsOrder = kds::create([
            'order_id' => $order->id,
            'box_id' => $order->box_id,
            'user_id' => $order->user_id,
            'admin_id' => $order->admin_id,
            'finished_at' => $order->finished_at,
            'order_type' => $order->order_type,
            'payment_type' => $order->payment_type,
            'status' => $order->status,
            'tip' => $order->tip,
            'discount' => $order->discount,
            'delivery_cost' => $order->delivery_cost,
            'customer_name' => $order->customer_name,
            'person' => $order->person,
            'reason' => $order->reason,
            'total_amount' => $totalAmount,
            'transaction_code' => $order->transaction_code,
            'notes' => $order->notes,
            'table_id' => $order->table_id
        ]);

        // $results = \DB::table('kds')
        // ->join('order_details', 'kds.order_id', '=', 'order_details.order_master_id')
        // ->join('items', 'order_details.item_id', '=', 'items.id')
        // ->join('production_centers', 'items.production_center_id', '=', 'production_centers.id')
        // ->select('kds.order_id', 'order_details.item_id', 'items.production_center_id', 'production_centers.name')
        // ->get();

        $results =  DB::table('kds')
        ->select('kds.order_id', 'order_details.item_id', 'items.production_center_id', 'production_centers.name')
        ->join('order_details', 'kds.order_id', '=', 'order_details.order_master_id')
        ->join('items', 'order_details.item_id', '=', 'items.id')
        ->join('production_centers', 'items.production_center_id', '=', 'production_centers.id')
        ->where('kds.order_id', $order->id)
        ->groupBy('kds.order_id', 'order_details.item_id', 'items.production_center_id', 'production_centers.name') // Added missing columns
        ->get();

        if($kdsOrder)
        {
            foreach ($results as $result) {
                $successMessage = "El pedido {$order->id} ha sido asignado exitosamente al KDS del centro de producción {$result->name} ";
                broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
                foreach ($usersRoles as $recipient) {
                    Notification::create([
                        'user_id' => $recipient->id,
                        'notification_type' => 'notification',
                        'notification' => $successMessage,
                        'admin_id' => $admin_id,
                        'role_id' => $recipient->role_id,
                        'path'=> '/kds'
                    ]);
                }
            }
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'kdsOrder' => $kdsOrder
            ], 200);
        } else {
            $errorMessage = "No se pudo asignar el pedido {$order->id} al KDS. Verifica la información e intenta nuevamente..";
            broadcast(new NotificationMessage('error', $errorMessage))->toOthers();
            foreach ($usersRoles as $recipient) {
                Notification::create([
                    'user_id' => $recipient->id,
                    'notification_type' => 'error',
                    'notification' => $errorMessage,
                    'admin_id' => $admin_id,
                    'role_id' => $recipient->role_id,
                    'path'=> '/kds'
                ]);
            }
            return response()->json([
                'success' => true,
                'message' => $errorMessage,
                'kdsOrder' => $kdsOrder
            ], 200);
        }

        $successMessage = "El pedido {$order->id} ha sido creado exitosamente para el cliente {$request->order_master['customer_name']}.";
        broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
        foreach ($usersRoles as $recipient) {
            Notification::create([
                'user_id' => $recipient->id,
                'notification_type' => 'notification',
                'notification' => $successMessage,
                'admin_id' => $admin_id,
                'role_id' => $recipient->role_id,
                'path'=> '/home_Pedidos'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Order placed successfully",
            'details' => $response,
            'notification' => $successMessage
        ], 200);
    }

    public function addItem(Request $request)
    {
        $role = Role::where('id', Auth()->user()->role_id)->first()->name;
        if ($role != "admin" && $role != "cashier" && $role != "waitress") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        $validateRequest = Validator::make($request->all(), [
            'order_id' => 'required|exists:order_masters,id',
            'order_details' => 'required|array'
        ]);



        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        $order = OrderMaster::find($request->input('order_id'));
        $responseData = ["order" => $order, "order_details" => []];
        foreach ($request->order_details as $order_detail) {
            $item = Item::find($order_detail['item_id']);
            $detail = OrderDetails::create([
                'order_master_id' => $order->id,
                'item_id' => $order_detail['item_id'],
                'quantity' => $order_detail['quantity'],
                'amount' => $item->sale_price,
                'cost' => $item->cost_price,
                'admin_id' => $request->admin_id,
                'notes' => $order_detail['notes'] ?? null // Use notes from order_detail
            ]);

            array_push($responseData['order_details'], $detail);
        }



        return response()->json($responseData, 200);
    }

    public function UpdateOrderReason(Request $request, $id)
    {
        $order = OrderMaster::find($id);
        $kds = kds::where('order_id', $id)->first();

        if ($order) {
            $order->update([
                'reason' => $request->input('reason')
            ]);
            $kds->update([
                'reason' => $request->input('reason')
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Reason updated successfully.',
                'reason' => $order
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.'
            ], 404);
        }
    }

    public function UpdateItem(Request $request, $id)
    {

        $role = Role::where('id', Auth()->user()->role_id)->first()->name;
        if ($role != "admin" && $role != "cashier" && $role != "waitress") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        $validateRequest = Validator::make($request->all(), [
            'order_id' => 'required|exists:order_masters,id',
            'order_details' => 'nullable|array', // Changed 'required' to 'nullable'
            'transaction_code' => 'nullable|boolean'
        ]);

        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }
        

        $order = OrderMaster::find($request->input('order_id'));
        $kds = kds::where('order_id', $request->input('order_id'))->first();
        if ($kds) {
            // return response()->json([
            //     'success' => false,
            //     'message' => 'KDS record not found.',
            // ], 404);
            $kds->update([
                'order_id' => $order->id, // Corrected to use $order->id
                'box_id' => $order->box_id,
                'user_id' => $order->user_id,
                'admin_id' => $order->admin_id,
                'finished_at' => $order->finished_at,
                'order_type' => $order->order_type,
                'payment_type' => $order->payment_type,
                'status' => $order->status,
                'tip' => $order->tip,
                'discount' => $order->discount,
                'delivery_cost' => $order->delivery_cost,
                'customer_name' => $order->customer_name,
                'person' => $order->person,
                'reason' => $order->reason,
                'transaction_code' => $order->transaction_code,
                'notes' => $order->notes,
                'table_id' => $order->table_id,
            ]);
        }

        // Update KDS record
       
        // Generate and update unique transaction code if requested
        if ($request->input('transaction_code', false)) {
            do {
                $transactionCode = rand(100000, 999999); // Generate a 6-digit number
            } while (OrderMaster::where('transaction_code', $transactionCode)->exists()); // Check for uniqueness

            $order->transaction_code = $transactionCode; // Assign unique code
            $order->save();
        }

        $responseData = ["order" => $order, "order_details" => []];
        if ($request->has('order_details')) {
            foreach ($request->order_details as $order_detail) {
                $item = Item::find($order_detail['item_id']);
                $detail = OrderDetails::find($id); // Ensure this is the correct ID for the order detail
                // dd($detail);

                if ($detail) { // Check if detail is found
                    $detail->update([
                        'order_master_id' => $order->id,
                        'item_id' => $order_detail['item_id'],
                        'quantity' => $order_detail['quantity'],
                        'amount' => $item->sale_price,
                        'cost' => $item->cost_price,
                    ]);

                    array_push($responseData['order_details'], $detail);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Order detail not found for ID: ' . $id
                    ], 404); // Return a 404 error if the detail is not found
                }
            }
        }



        return response()->json($responseData, 200);
    }


    public function getAllOrder(Request $request)
    {
        // $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        // if($role != "admin")
        // {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Unauthorised'
        //     ], 401);
        // }

        $admin = $request->admin_id;

        $orders = OrderMaster::where('admin_id', $admin)->get();



        $filter = [];
        $flag = false;
        if ($request->has('received') && $request->query('received') == "yes") {
            $filter[] = "received";
            $flag = true;
        }
        if ($request->has('prepared') && $request->query('prepared') == "yes") {
            $filter[] = "prepared";
            $flag = true;
        }
        if ($request->has('delivered') && $request->query('delivered') == "yes") {
            $filter[] = "delivered";
            $flag = true;
        }
        if ($request->has('finalized') && $request->query('finalized') == "yes") {
            $filter[] = "finalized";
            $flag = true;
        }

        if ($flag) {
            $orders = $orders->whereIn('status', $filter)->all();
        }
        foreach ($orders as $order) {
            $order['total'] = OrderDetails::where('order_master_id', $order->id)->sum('amount');
            $order['order_details'] = DB::table('order_details')
                ->leftJoin('items', 'order_details.item_id', '=', 'items.id')
                ->where('order_master_id', $order->id)
                ->whereNull('order_details.deleted_at')
                ->select(['order_details.*', 'items.name', DB::raw('order_details.amount * order_details.quantity AS total')])
                ->get();
        }

        return response()->json($orders, 200);
    }
    public function getAllKds(Request $request)
    {
       
        // $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        // if($role != "admin")
        // {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Unauthorised'
        //     ], 401);
        // }

        $admin = $request->admin_id;

        $orders = kds::where('admin_id', $admin)->get();



        $filter = [];
        $flag = false;
        if ($request->has('received') && $request->query('received') == "yes") {
            $filter[] = "received";
            $flag = true;
        }
        if ($request->has('prepared') && $request->query('prepared') == "yes") {
            $filter[] = "prepared";
            $flag = true;
        }
        if ($request->has('delivered') && $request->query('delivered') == "yes") {
            $filter[] = "delivered";
            $flag = true;
        }
        if ($request->has('finalized') && $request->query('finalized') == "yes") {
            $filter[] = "finalized";
            $flag = true;
        }

        if ($flag) {
            $orders = $orders->whereIn('status', $filter)->all();
        }
        foreach ($orders as $order) {
            $order['total'] = OrderDetails::where('order_master_id', $order->id)->sum('amount');
            $order['order_details'] = DB::table('order_details')
                ->leftJoin('items', 'order_details.item_id', '=', 'items.id')
                ->where('order_master_id', $order->order_id)
                ->whereNull('order_details.deleted_at')
                ->select(['order_details.*', 'items.name', DB::raw('order_details.amount * order_details.quantity AS total')])
                ->get();
        }


        return response()->json($orders, 200);
    }
    public function getSingle(Request $request, $id)
    {
        $user = auth()->user();


        $role = Role::where('id', Auth()->user()->role_id)->first()->name;
     
        $adminId = $request->admin_id;
        // $order = OrderMaster::find($id)->where('admin_id', $adminId)->get();
        $adminId = $user->role_id == 1 ? $user->id : $user->admin_id;
        $order = OrderMaster::where('id', $id)->where('admin_id', $adminId)->first();

        $admin_id = ($role == 'admin') ? auth()->user()->id : auth()->user()->admin_id;
        $usersCancelOrder = User::where('admin_id', $admin_id)
        ->whereIn('role_id', [1, 2])
        ->orWhere('id', $admin_id)
        ->get();



        if ($order == null) {
            $errorMessage = 'No se pudo consultar los detalles del pedido. Intenta nuevamente más tarde';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            foreach ($usersCancelOrder as $recipient) {
                Notification::create([
                    'user_id' => $recipient->id,
                    'notification_type' => 'alert',
                    'notification' => $errorMessage,
                    'admin_id' => $admin_id,
                    'role_id' => $recipient->role_id,
                    'path'=>'/home/usa'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => "Invalid order id",
                'alert' => $errorMessage,
            ], 405);
        }


        $order['total'] = OrderDetails::where('order_master_id', $order->id)->sum('amount');

        $order['order_details'] = DB::table('order_details')
            ->leftJoin('items', 'order_details.item_id', '=', 'items.id')
            ->where('order_master_id', $order->id)
            ->whereNull('order_details.deleted_at')
            ->select(['order_details.*', 'items.name', DB::raw('order_details.amount * order_details.quantity AS total')])
            ->get();


        $successMessage = "Los detalles del pedido {$order->id} han sido consultados exitosamente.";
        // Broadcast notification message
        broadcast(new NotificationMessage('notification', $successMessage))->toOthers();

        // Save the notification to the database
foreach ($usersCancelOrder as $recipient) {
    Notification::create([
        'user_id' => $recipient->id,
        'notification_type' => 'notification',
        'notification' => $successMessage,
        'admin_id' => $admin_id,
        'role_id' => $recipient->role_id,
        'path' => "/home/usa/information/" . $order->id
    ]);
}




        return response()->json([$order, 'notification' => $successMessage]);
    }

    public function deleteOrder($id)
    {
        $role = Role::where('id', Auth()->user()->role_id)->first()->name;

        if ($role != "admin") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        $order = OrderMaster::find($id);
        // $kds = kds::find($id);
        $kdsRecords= kds::where('order_id', $id)->get();
// dd($kds); 
        $orderDetails = OrderDetails::where('order_master_id', $id)->get();

        foreach ($orderDetails as $orderDetail) {
            $orderDetail->delete();
        }

        if($order){
            $order->delete();
        }
        foreach ($kdsRecords as $kds) {
        $kds->delete();
    }


        return response()->json([
            'success' => true,
            'message' => 'Order deleted'
        ], 200);
    }

    public function updateOrderStatus(Request $request)
    {
        $role = Role::where('id', Auth()->user()->role_id)->first()->name;
        $validateRequest = Validator::make($request->all(), [
            'order_id' => 'required|exists:order_masters,id',
            'status' => 'required|in:received,prepared,delivered,finalized,cancelled'
        ]);

        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }
        $user = auth()->user();
        $adminId = $user->role_id == 1 ? $user->id : $user->admin_id;

        $order = OrderMaster::find($request->input('order_id'));
        $order->status = $request->input('status');
    $order->save();
        $kds = kds::where('order_id', $order->id)->first();
        
        if ($request->input('status') == 'delivered') {
            $order->finished_at = now(); // Update current date in finish_at column if status is delivered
            $kds->finished_at = now(); // Update current date in finish_at column if status is delivered
        }
        // dd(now());

        if ($kds) {
            $kds->status = $order->status; // Update KDS status
            $kds->save(); // Save the updated KDS record

            $admin_id = ($role == 'admin') ? auth()->user()->id : auth()->user()->admin_id;
            $users = User::where('admin_id', $admin_id)->orWhere('id', $admin_id)->get();
            $usersRoles = User::where('admin_id', $admin_id)
            ->whereIn('role_id', [1, 2, 3, 4])
            ->orWhere('id', $admin_id)
            ->get();

            // $results = DB::table('kds')
            // ->join('order_details', 'kds.order_id', '=', 'order_details.order_master_id')
            // ->join('items', 'order_details.item_id', '=', 'items.id')
            // ->join('production_centers', 'items.production_center_id', '=', 'production_centers.id')
            // ->select('kds.order_id', 'order_details.item_id', 'items.production_center_id', 'production_centers.name')
            // ->get();
            $results =  DB::table('kds')
            ->select('kds.order_id', 'order_details.item_id', 'items.production_center_id', 'production_centers.name')
            ->join('order_details', 'kds.order_id', '=', 'order_details.order_master_id')
            ->join('items', 'order_details.item_id', '=', 'items.id')
            ->join('production_centers', 'items.production_center_id', '=', 'production_centers.id')
            ->where('kds.order_id', $order->id)
            ->groupBy('kds.order_id', 'order_details.item_id', 'items.production_center_id', 'production_centers.name') // Added missing columns
            ->get();
           
            // dd($kds->status);
     
            if($kds->status === 'prepared')
            {
                foreach ($results as $result) {
                    $successMessage = "El pedido {$order->id} ha sido marcado como 'En Proceso' en el KDS del centro de producción {$result->name}";
                    broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
                    foreach ($users as $recipient) {
                        Notification::create([
                            'user_id' => $recipient->id,
                            'notification_type' => 'notification',
                            'notification' => $successMessage,
                            'admin_id' => $admin_id,
                            'role_id' => $recipient->role_id,
                             'path'=>'/kds/Preparado'
                        ]); 
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'KDS updated successfully.',
                    'notification' => $successMessage
                ], 200);
            } 
            // Corrected condition for finalized status
            elseif($kds->status === 'finalized')
            {
                foreach ($results as $result) {
                    $successMessage = "El pedido {$order->id} ha sido completado exitosamente en el KDS del centro de producción {$result->name}";
                    broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
                    foreach ($users as $recipient) {
                        Notification::create([
                            'user_id' => $recipient->id,
                            'notification_type' => 'notification',
                            'notification' => $successMessage,
                            'admin_id' => $admin_id,
                            'role_id' => $recipient->role_id,
                            'path'=>'/kds'
                        ]);
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'KDS updated successfully.',
                    'notification' => $successMessage
                ], 200);
            }
            else if($kds->status === 'delivered')
            {

                foreach ($results as $result) {
                    $successMessage = "El pedido {$order->id} ha sido entregado en el KDS del centro de producción {$result->name}";
                    // $successMessage = "dele";
                    broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
                    foreach ($users as $recipient) {
                        Notification::create([
                            'user_id' => $recipient->id,
                            'notification_type' => 'notification',
                            'notification' => $successMessage,
                            'admin_id' => $admin_id,
                            'role_id' => $recipient->role_id,
                            'path'=>'/kds'
                        ]);
                    }
                }
        
                return response()->json([
                    'success' => true,
                    'message' => 'KDS added successfully.',
                    'notification' => $successMessage
                ], 200);
            }
            else if($kds->status === 'cancelled')
            {
                foreach ($results as $result) {
                    $successMessage = "El pedido {$order->id} ha sido cancelado en el KDS del centro de producción {$result->name}";
                    // $successMessage = "cancel";
                    broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
                    foreach ($users as $recipient) {
                        Notification::create([
                            'user_id' => $recipient->id,
                            'notification_type' => 'notification',
                            'notification' => $successMessage,
                            'admin_id' => $admin_id,
                            'role_id' => $recipient->role_id,
                            'path'=>'/kds'
                        ]);
                    }
                }
        
                return response()->json([
                    'success' => true,
                    'message' => 'KDS added successfully.',
                    'notification' => $successMessage
                ], 200);
            }
        
            else {
                // Handle other statuses
                $admin_id = ($role == 'admin') ? auth()->user()->id : auth()->user()->admin_id;
                $usersRoles = User::where('admin_id', $admin_id)
                ->whereIn('role_id', [1, 4])
                ->orWhere('id', $admin_id)
                ->get();
                
                $errorMessage = "No se pudo marcar el pedido {$order->id} como '{$kds->status}'. Verifica la información e intenta nuevamente";
                broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
                foreach ($usersRoles as $recipient) {
                    Notification::create([
                        'user_id' => $recipient->id,
                        'notification_type' => 'alert',
                        'notification' => $errorMessage,
                        'admin_id' => $admin_id,
                        'role_id' => $recipient->role_id,
                        'path'=>'/kds'
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'KDS update failed.',
                    'notification' => $errorMessage
                ], 200);
            }

           
            // else{

            //     $admin_id = ($role == 'admin') ? auth()->user()->id : auth()->user()->admin_id;
            //     $usersRoles = User::where('admin_id', $admin_id)
            //     ->whereIn('role_id', [1, 4])
            //     ->orWhere('id', $admin_id)
            //     ->get();
            
            //     $errorMessage = "No se pudo cancelar el pedido {$order->id} en el KDS. Verifica la información e intenta nuevamente..";
            //     broadcast(new NotificationMessage('notification', $errorMessage ))->toOthers();
            //     foreach ($usersRoles as $recipient) {
            //         Notification::create([
            //             'user_id' => $recipient->id,
            //             'notification_type' => 'alert',
            //             'notification' => $errorMessage,
            //             'admin_id' => $admin_id,
            //             'role_id' => $recipient->role_id
            //         ]);
            //     }
        
            //     return response()->json([
            //         'success' => true,
            //         'message' => 'KDS Failed successfully.',
            //         'notification' => $errorMessage
            //     ], 200);
            // }
   
   

        }

        $admin_id = ($role == 'admin') ? auth()->user()->id : auth()->user()->admin_id;
        $usersRoles = User::where('admin_id', $admin_id)
        ->whereIn('role_id', [1, 2, 3])
        ->orWhere('id', $admin_id)
        ->get();

        $usersCancelRoles = User::where('admin_id', $admin_id)
        ->whereIn('role_id', [1, 2])
        ->orWhere('id', $admin_id)
        ->get();

        if ($order->status === 'finalized') {
            $currentStatus = OrderMaster::find($order->id)->status;
            if ($currentStatus || $role != "admin" && $role != "cashier") {
                if ($currentStatus === 'finalized') {
                    $errorMessage = 'No se pudo anular el pedido. Verifica si el pedido ya ha sido finalizado o si hay problemas de conexión e intenta nuevamente';
                    broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
                    foreach ($usersCancelRoles as $recipient) {
                        Notification::create([
                            'user_id' => $recipient->id,
                            'notification_type' => 'alert',
                            'notification' => $errorMessage,
                            'admin_id' => $request->admin_id,
                            'role_id' => $recipient->role_id,
                            'path'=>'/kds'
                        ]);
                    }

                    return response()->json([
                        'success' => false,
                        'alert' => $errorMessage,

                    ], 403);

                  
                }
            }
        }


        

        if ($role != "admin" && $role != "cashier" && $role != "waitress") {
            if ($request->input('status') === 'cancelled') {
                $successMessage = "El pedido {$order->id} ha sido anulado exitosamente.";
                broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
                foreach ($usersRoles as $recipient) {
                    Notification::create([
                        'user_id' => $recipient->id,
                        'notification_type' => 'notification',
                        'notification' => $successMessage,
                        'admin_id' => $request->admin_id,
                        'role_id' => $recipient->role_id
                    ]);
                }
            }
        }

        $order->save();
        $kds->save();
        return response()->json([
            'success' => true,
            'message' => "Status updated successfully",
            'notification' => $successMessage ?? null
        ], 200);
    }

    public function deleteSingle($id)
    {
        $orderDetail = OrderDetails::where('id', $id);

        if ($orderDetail == null) {
            return response()->json([
                'success' => false,
                'message' => 'Order Detail id not valid'
            ], 403);
        }

        $orderDetail->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item deleted from order'
        ], 200);
    }

    public function addTip(Request $request, $id)
    {
        $order = OrderMaster::find($id);
        $kds = kds::where('order_id', $id)->first();
        if ($order == null) {
            return response()->json([
                'success' => false,
                'message' => 'Order id not valid'
            ], 403);
        }

        if (!$request->has('tip_amount')) {
            return response()->json([
                'success' => false,
                'message' => "Validation fails",
                'errors' => [
                    'tip_amount' => 'It is required parameter'
                ]
            ], 403);
        }

        $order->tip = $request->query('tip_amount');
        $kds->tip = $request->query('tip_amount');
        $order->save();
        $kds->save();

        return response()->json([
            'success' => true,
            'message' => "Tip added successfully"
        ], 200);
    }

    public function addNote(Request $request, $id)
    {
        $orderMaster = OrderDetails::find($id);

        if ($orderMaster == null) {
            return response()->json([
                'success' => false,
                'message' => 'Order ID invalid'
            ], 403);
        }

        if ($orderMaster->notes == null || $orderMaster->notes == "") {
            $orderMaster->notes = $request->input('notes');
        } else {
            $orderMaster->notes = $request->input('notes');
        }

        $orderMaster->save();

        return response()->json([
            'success' => true,
            'message' => 'Note added successfully'
        ], 200);
    }

    public function getLastOrder(Request $request)
    {
        $adminId = $request->admin_id;


        $role = Role::where('id', Auth()->user()->role_id)->first()->name;
        if ($role != "admin" && $role != "cashier" && $role != "waitress") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }


        $lastOrder = OrderMaster::where('admin_id', $adminId)->orderBy('id', 'desc')->first();

        if ($lastOrder == null) {
            return response()->json([
                'success' => false,
                'message' => 'No orders found'
            ], 404);
        }

        $lastOrder['total'] = OrderDetails::where('order_master_id', $lastOrder->id)->sum('amount');
        $lastOrder['order_details'] = DB::table('order_details')
            ->leftJoin('items', 'order_details.item_id', '=', 'items.id')
            ->where('order_master_id', $lastOrder->id)
            ->whereNull('order_details.deleted_at')
            ->select(['order_details.*', 'items.name', DB::raw('order_details.amount * order_details.quantity AS total')])
            ->get();

        return response()->json([
            'success' => true,
            'order' => $lastOrder
        ], 200);
    }
    public function orderUpdateItem(Request $request, $order_id)
    {
        // Check user role for authorization
        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if ($role != "admin" && $role != "cashier" && $role != "waitress") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $admin_id = ($role == 'admin') ? auth()->user()->id : auth()->user()->admin_id;
        $usersRoles = User::where('admin_id', $admin_id)
        ->whereIn('role_id', [1, 2, 3])
        ->orWhere('id', $admin_id)
        ->get();

        // Validate the request data
        $validateRequest = Validator::make($request->all(), [
            'order_details' => 'nullable|array',
            'order_details.*.item_id' => 'required_with:order_details|exists:items,id',
            'order_details.*.quantity' => 'required_with:order_details|integer|min:1',
            'tip' => 'nullable|numeric|min:0',
            'payment_type' => 'nullable|string',
            'transaction_code' => 'nullable|boolean'
        ]);

        if ($validateRequest->fails()) {
            $errorMessage = 'No se pudo actualizar el pedido. Verifica la información ingresada e intenta nuevamente';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            foreach ($usersRoles as $recipient) {
                Notification::create([
                    'user_id' => $recipient->id,
                    'notification_type' => 'notification',
                    'notification' => $errorMessage,
                    'admin_id' => $admin_id,
                    'role_id' => $recipient->role_id
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => "Validation fails",
                'errors' => $validateRequest->errors(),
                'notification' => $errorMessage
            ], 403);
           
        }

        // Find the order using the order_id from the URL
        $order = OrderMaster::find($order_id);
        // Find the KDS record
        // dd($order_id);
        $all = kds::all();
        // dd($all);
        $kds = kds::where('order_id', $order_id)->first();
        if ($kds) {
            $kds->update([
            'order_id' => $order->id,
            'box_id' => $order->box_id,
            'user_id' => $order->user_id,
            'admin_id' => $order->admin_id,
            'finished_at' => $order->finished_at,
            'order_type' => $order->order_type,
            'payment_type' => $order->payment_type,
            'status' => $order->status,
            'tip' => $order->tip,
            'discount' => $order->discount,
            'delivery_cost' => $order->delivery_cost,
            'customer_name' => $order->customer_name,
            'person' => $order->person,
            'reason' => $order->reason,
            'transaction_code' => $order->transaction_code,
            'notes' => $order->notes,
            'table_id' => $order->table_id,
        ]);
        }
        // Update KDS record
        // dd($order);
        
        // Check if the order exists
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        // Update tip and payment_type if provided
        $orderUpdateData = [];
        if ($request->has('tip')) {
            $orderUpdateData['tip'] = $request->input('tip');
        }
        if ($request->has('payment_type')) {
            $orderUpdateData['payment_type'] = $request->input('payment_type');
        }
        if ($request->has('box_id')) {
            $orderUpdateData['box_id'] = $request->input('box_id');
        }
        if ($request->has('customer_name')) {
            $orderUpdateData['customer_name'] = $request->input('customer_name');
        }
        // dd($orderUpdateData);

        // Generate and update transaction code if requested
        if ($request->input('transaction_code') === true) {
            do {
                $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            } while (OrderMaster::where('transaction_code', $code)->exists());

            $orderUpdateData['transaction_code'] = $code;
        }

        if (!empty($orderUpdateData)) {
            $order->update($orderUpdateData);
        }

        // Prepare response data
        $responseData = ["order" => $order, "order_details" => []];

        // Handle order details if provided
        if ($request->has('order_details')) {
            // Get the item IDs from the request
            $requestedItemIds = collect($request->input('order_details'))->pluck('item_id')->toArray();

            // Remove existing order details that are not in the request
            OrderDetails::where('order_master_id', $order->id)
                ->whereNotIn('item_id', $requestedItemIds)
                ->delete();

            // Loop through the order details from the request
            foreach ($request->input('order_details') as $order_detail) {
                // Find the existing detail or create a new one
                $detail = OrderDetails::updateOrCreate(
                    [
                        'order_master_id' => $order->id,
                        'item_id' => $order_detail['item_id']
                    ],
                    [
                        'quantity' => $order_detail['quantity']
                    ]
                );

                // Add the updated or newly created detail to the response
                $responseData['order_details'][] = $detail;
            }
        } else {
            // If no order details provided, fetch existing ones for the response
            $responseData['order_details'] = $order->orderDetails;
        }
        if ($role == "cashier" || $role == "admin") {
            $box = Boxs::where('id', $orderUpdateData['box_id'])->latest()->first();
            

            if($box)
            {
                $log = BoxLogs::where('box_id', $box->id)->latest()->first();

                if($log)
                {
                    if (empty($log->order_master_id)) {
                        $log->order_master_id = $order->id;
                    } else {
                        $log->order_master_id .= "," . $order->id;
                    }
                }

            }
            


            // if (empty($log->order_master_id)) {
            //     $log->order_master_id = $order->id;
            // } else {
            //     $log->order_master_id .= "," . $order->id;
            // }

            // if(empty($log->payment_id))
            // {
            //     $log->payment_id .= "," . $order->payment_type;
            // }
            // else
            // {
            //     $log->payment_id .= "," . $order->payment_id;
            // }

            $successMessage = "El pedido {$order->id} ha sido actualizado exitosamente";
            broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
            foreach ($usersRoles as $recipient) {
                Notification::create([
                    'user_id' => $recipient->id,
                    'notification_type' => 'notification',
                    'notification' => $successMessage,
                    'admin_id' => $admin_id,
                    'role_id' => $recipient->role_id
                ]);
            }
            

            $log->save();
            
        }
$successMessage = "skjdgqgwe";
        // Return the response
        return response()->json([$responseData, 200,'notification' => $successMessage]);
    }


    public function getCredit(Request $request)
    {

        $adminId = $request->admin_id;
        $creditNotes = CreditNot::where('admin_id', $adminId)->with('returnItems')->get();


        // Return the credit notes as JSON
        return response()->json([
            'success' => true,
            'data' => $creditNotes
        ], 200);
    }

    public function creditNote(Request $request)
    {

        $user = auth()->user();
        $role = Role::where('id', Auth()->user()->role_id)->first()->name;
        if ($role != "admin" && $role != "cashier") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        $admin_id = ($role == 'admin') ? auth()->user()->id : auth()->user()->admin_id;
        $usersRoles = User::where('admin_id', $admin_id)
        ->whereIn('role_id', [1, 2])
        ->orWhere('id', $admin_id)
        ->get();

        // $validateRequest = Validator::make($request->all(), [
        //     'credit_note.order_id' => 'required|integer',
        //     'credit_note.payment_id' => 'required|integer',
        //     'credit_note.status' => 'required|string',
        //     'credit_note.name' => 'required|string',
        //     'credit_note.email' => 'required|email',
        //     'credit_note.delivery_cost' => 'required|numeric',
        //     'credit_note.code' => 'required|integer',
        //     // 'credit_note.destination' => 'required|integer',
        //     'credit_note.payment_status' => 'required|string',
        //     'credit_note.credit_method' => 'required|string|in:credit,debit,cash,future purchase',
        //     'return_items.*.item_id' => 'required|integer',
        //     'return_items.*.name' => 'required|string',
        //     'return_items.*.quantity' => 'required|integer',
        //     'return_items.*.cost' => 'required|numeric',
        //     'return_items.*.amount' => 'required|numeric',
        //     'return_items.*.notes' => 'nullable|string',
        // ]);
        
        
             $validateRequest = Validator::make($request->all(), [
            'credit_note.order_id' => 'required|integer',
            'credit_note.payment_id' => 'required|integer',
            'credit_note.status' => 'required|string',
            // 'credit_note.name' => 'required|string',
            // 'credit_note.email' => 'email',
            'credit_note.delivery_cost' => 'numeric',
            'credit_note.code' => 'required|integer',
            // 'credit_note.destination' => 'required|integer',
            'credit_note.payment_status' => 'required|string',
            'credit_note.credit_method' => 'required|string|in:credit,debit,cash,future purchase',
            'return_items.*.item_id' => 'required|integer',
            'return_items.*.name' => 'required|string',
            'return_items.*.quantity' => 'required|integer',
            'return_items.*.cost' => 'required|numeric',
            'return_items.*.amount' => 'required|numeric',
            'return_items.*.notes' => 'nullable|string',
        ]);


        if ($validateRequest->fails()) {
            $errorMessage = 'No se pudo crear la nota de crédito para el pedido. Verifica la información ingresada e intenta nuevamente';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            foreach ($usersRoles as $recipient) {
                Notification::create([
                    'user_id' => $recipient->id,
                    'notification_type' => 'alert',
                    'notification' => $errorMessage,
                    'admin_id' => $admin_id,
                    'role_id' => $recipient->role_id,
                    'path'=>'/home/client'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors(),
                'alert' => $errorMessage
            ], 403);
        }

        $creditNoteData = $request->input('credit_note');

        if ($creditNoteData == null) {
            $errorMessage = 'No se pudo consultar los detalles del pedido. Intenta nuevamente más tarde';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            foreach ($usersRoles as $recipient) {
                Notification::create([
                    'user_id' => $recipient->id,
                    'notification_type' => 'alert',
                    'notification' => $errorMessage,
                    'admin_id' => $admin_id,
                    'role_id' => $recipient->role_id
                ]);
            }

            return response()->json([
                'success' => false,
                'alert' => $errorMessage,
            ], 405);
        }

        $returnItemsData = $request->input('return_items');
        $admin_id = $request->input('admin_id');
        $email = filter_var($creditNoteData['email'], FILTER_VALIDATE_EMAIL) ? $creditNoteData['email'] : '';
        // Create the credit note
        $creditNote = CreditNot::create([
            'order_id' => $creditNoteData['order_id'],
            'payment_id' => $creditNoteData['payment_id'],
            'status' => $creditNoteData['status'],
            'name' => $creditNoteData['name'],
            'email' => $email,
            'code' => $creditNoteData['code'],
            'destination' => $creditNoteData['destination'],
            'delivery_cost' => $creditNoteData['delivery_cost'],
            'payment_status' => $creditNoteData['payment_status'],
            'credit_method' => $creditNoteData['credit_method'],
            'admin_id' => $admin_id
        ]);



        $successMessage = "La nota de crédito ha sido creada exitosamente para el pedido {$creditNote->order_id}.";
        // Broadcast notification message
        broadcast(new NotificationMessage('notification', $successMessage))->toOthers();

        foreach ($usersRoles as $recipient) {
            Notification::create([
                'user_id' => $recipient->id,
                'notification_type' => 'notification',
                'notification' => $successMessage,
                'admin_id' => $admin_id,
                'role_id' => $recipient->role_id
            ]);
        }

        // Process return items (if you need to process them but not include in the response)
        $returnItems = [];
        foreach ($returnItemsData as $orderDetail) {
            $item = Item::find($orderDetail['item_id']);

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item not found'
                ], 404);
            }

            $cost = $orderDetail['cost'] ?? null;
            $amount = $orderDetail['amount'] ?? null;
            $note = $orderDetail['notes'] ?? null;
            $name = $orderDetail['name'] ?? null;

            // Prepare the item details to be added to the array
            $returnItem = ReturnItem::create([
                'credit_note_id' => $creditNote->id,
                'item_id' => $orderDetail['item_id'],
                'name' => $name,
                'quantity' => $orderDetail['quantity'],
                'cost' => $cost,
                'amount' => $amount,
                'notes' => $note,
            ]);

            $returnItems[] = $returnItem;
        }

        // Return the response without including return_items
        return response()->json([
            'success' => true,
            'credit_note' => $creditNote,
            'return_items' => $returnItems,
            'notification' => $successMessage,
        ]);
    }

    public function orderCreditUpdate(Request $request, $id)
    {
        $validatedData = $request->validate([
            'status' => 'required|string',
            'destination' => 'nullable',
        ]);
        $creditNote = CreditNot::find($id);
        // dd($creditNote);
        if (!$creditNote) {
            return response()->json([
                'success' => false,
                'message' => 'Credit Note not found'
            ], 404);
        }
        $creditNote->status = $validatedData['status'];
        $creditNote->destination = $validatedData['destination'];
        $creditNote->save();
        return response()->json([
            'success' => true,
            'message' => 'Credit Note status updated successfully',
            'credit_note' => $creditNote
        ]);
    }

    public function orderCreditDelete($id)
    {
        // Find the CreditNote by ID
        $creditNote = CreditNot::find($id);

        // Check if the CreditNote exists
        if (!$creditNote) {
            return response()->json([
                'success' => false,
                'message' => 'Credit Note not found'
            ], 404);
        }

        // Delete the CreditNote
        $creditNote->delete();

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Credit Note deleted successfully'
        ]);
    }
}
