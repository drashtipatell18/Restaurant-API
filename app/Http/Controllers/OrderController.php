<?php

namespace App\Http\Controllers;

use App\Models\BoxLogs;
use App\Models\Boxs;
use App\Models\Item;
use App\Models\OrderDetails;
use App\Models\OrderMaster;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function placeOrder(Request $request)
    {
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
            'payment_type' => 'required|in:cash,debit,credit,transfer',
            'status' => 'required|in:received,prepared,delivered,finalized',
            'discount' => 'required|min:0',
            'delivery_cost' => 'required|min:0'
        ]);

        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => "Validation fails",
                'errors' => $validateRequest->errors()
            ], 403);
        }

        $orderMaster = [
            'order_type' => $request->order_master['order_type'],
            'payment_type' => $request->order_master['payment_type'],
            'status' => $request->order_master['status'],
            'discount' => $request->order_master['discount'],
            'delivery_cost' => $request->order_master['delivery_cost'],
            'customer_name' =>  $request->order_master['customer_name'],
            'person' =>  $request->order_master['person']
        ];

        if ($role == "cashier") {
            $box = Boxs::where('user_id', Auth::user()->id)->get()->first();
            $log = BoxLogs::where('box_id', $box->id)->get()->last();

            if ($log == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Box not opened'
                ], 403);
            } else if ($log->close_time != null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Box not opened'
                ], 403);
            }
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
        if (isset($request->order_master['notes'])) {
            $orderMaster['notes'] = $request->order_master['notes'];
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
                'notes' =>  $order_detail['notes'],
                'quantity' => $order_detail['quantity']
            ]);

            $totalAmount += $item->sale_price * $order_detail['quantity'];

            array_push($response['order_details'], $orderDetail);
        }

        if ($role == "cashier") {
            $box = Boxs::where('user_id', Auth::user()->id)->get()->first();
            $log = BoxLogs::where('box_id', $box->id)->get()->last();

            $log->collected_amount += $totalAmount;

            $log->save();
        }

        return response()->json([
            'success' => true,
            'message' => "Order placed successfully",
            'details' => $response
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
            ]);

            array_push($responseData['order_details'], $detail);
        }

        return response()->json($responseData, 200);
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
            $detail = OrderDetails::find($id);
            $detail->update([
                'order_master_id' => $order->id,
                'item_id' => $order_detail['item_id'],
                'quantity' => $order_detail['quantity'],
                'amount' => $item->sale_price,
                'cost' => $item->cost_price,
            ]);

            array_push($responseData['order_details'], $detail);
        }

        return response()->json($responseData, 200);
    }

    public function getAll(Request $request)
    {
        // $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        // if($role != "admin")
        // {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Unauthorised'
        //     ], 401);
        // }

        $orders = OrderMaster::all();

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

    public function getSingle($id)
    {
        $order = OrderMaster::find($id);

        if ($order == null) {
            return response()->json([
                'success' => false,
                'message' => "Invalid order id"
            ], 403);
        }

        $order['total'] = OrderDetails::where('order_master_id', $order->id)->sum('amount');
        $order['order_details'] = DB::table('order_details')
            ->leftJoin('items', 'order_details.item_id', '=', 'items.id')
            ->where('order_master_id', $order->id)
            ->whereNull('order_details.deleted_at')
            ->select(['order_details.*', 'items.name', DB::raw('order_details.amount * order_details.quantity AS total')])
            ->get();
        return response()->json($order);
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
        $orderDetails = OrderDetails::where('order_master_id', $id)->getAll();

        foreach ($orderDetails as $orderDetail) {
            $orderDetail->delete();
        }

        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted'
        ], 200);
    }

    public function updateOrderStatus(Request $request)
    {
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

        $order = OrderMaster::find($request->input('order_id'));
        $order->status = $request->input('status');
        $order->save();

        return response()->json([
            'success' => true,
            'message' => "Status updated successfully"
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
        $order->save();

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
            $orderMaster->notes .= "," . $request->input('notes');
        }

        $orderMaster->save();

        return response()->json([
            'success' => true,
            'message' => 'Note added successfully'
        ], 200);
    }

    public function getLastOrder()
    {
        $role = Role::where('id', Auth()->user()->role_id)->first()->name;
        if ($role != "admin" && $role != "cashier" && $role != "waitress") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        $lastOrder = OrderMaster::orderBy('id', 'desc')->first();

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
}
