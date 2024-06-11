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
        $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        if($role != "admin" && $role != "cashier")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        if(!$request->has('order_master'))
        {
            return response()->json([
                'success' => false,
                'message' => "Validation fails"
            ], 403);
        }
        $validateRequest = Validator::make($request->order_master,[
            'order_type' => 'required|in:delivery,local,withdraw',
            'payment_type' => 'required|in:cash,debit,credit,transfer',
            'discount' => 'required|min:0',
            'delivery_cost' => 'required|min:0'
        ]);

        if($validateRequest->fails())
        {
            return response()->json([
                'success' => false,
                'message' => "Validation fails",
                'errors' => $validateRequest->errors()
            ], 403);
        }

        $orderMaster = [
            'order_type' => $request->order_master['order_type'],
            'payment_type' => $request->order_master['payment_type'],
            'discount' => $request->order_master['discount'],
            'delivery_cost' => $request->order_master['delivery_cost']
        ];

        if($role == "cashier")
        {
            $box = Boxs::where('user_id', Auth::user()->id)->get()->first();
            $log = BoxLogs::where('box_id', $box->id)->get()->last();

            if($log == null)
            {
                return response()->json([
                    'success' => false,
                    'message' => 'Box not opened'
                ], 403);
            }
            else if($log->close_time != null)
            {
                return response()->json([
                    'success' => false,
                    'message' => 'Box not opened'
                ], 403);
            }
        }

        if(isset($request->order_master['table_id']))
        {
            $orderMaster['table_id'] = $request->order_master['table_id'];
        }
        if(isset($request->order_master['user_id']))
        {
            $orderMaster['user_id'] = $request->order_master['user_id'];
        }
        if($role != "cashier" && isset($request->order_master['box_id']))
        {
            $orderMaster['box_id'] = $request->order_master['box_id'];
        }
        if(isset($request->order_master['tip']))
        {
            $orderMaster['tip'] = $request->order_master['tip'];
        }
        
        if($role == "cashier")
        {
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
                'quantity' => $order_detail['quantity']
            ]);

            $totalAmount += $item->sale_price * $order_detail['quantity'];

            array_push($response['order_details'], $orderDetail);
        }

        if($role == "cashier")
        {
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

    public function getAll()
    {
        $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        $orders = OrderMaster::all();
        foreach ($orders as $order) {
            $orderDetails = OrderDetails::all()->where('order_master_id', $order->id);
            $order['total'] = OrderDetails::where('order_master_id', $order->id)->sum('amount');
            $order['order_details'] = DB::table('order_details')
                ->leftJoin('items', 'order_details.item_id', '=', 'items.id')
                ->where('order_master_id', $order->id)
                ->select(['order_details.*', 'items.name', DB::raw('order_details.amount * order_details.quantity AS total')])
                ->get();
        }

        return response()->json($orders, 200);
    }

    public function deleteOrder($id)
    {
        $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        $order = OrderMaster::find($id);
        $orderDetails = OrderDetails::where('order_master_id', $id)->getAll();

        foreach ($orderDetails as $orderDetail) 
        {
            $orderDetail->delete();
        }

        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted'
        ], 200);
    }
}
