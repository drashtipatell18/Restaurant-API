<?php

namespace App\Http\Controllers;

use App\Models\BoxLogs;
use Illuminate\Http\Request;
use App\Models\Boxs;
use App\Models\OrderDetails;
use App\Models\OrderMaster;
use App\Models\Role;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Notification;
use App\Events\NotificationMessage;

class BoxController extends Controller
{
    public function index()
    {
        // Check if the user is an admin
        if (auth()->user()->role == 'admin') {
            // If the user is an admin, retrieve all records
            $boxs = Boxs::all();
        } else {
            // Otherwise, retrieve records where admin_id matches the authenticated user
            $boxs = Boxs::where('admin_id', auth()->user()->id)->get();
        }
    
        return response()->json($boxs, 200);
    }

    public function createBox(Request $request)
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
            'user_id' => 'required|integer|exists:users,id',
            'name' => 'required|string|max:255'
        ]);

        if($validateFamily->fails())
        {
            // $errorMessage = 'No se pudo crear la caja. Verifica la información ingresada e intenta nuevamente.';
            // broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            // Notification::create([
            //     'user_id' => auth()->user()->id,
            //     'notification_type' => 'alert',
            //     'notification' => $errorMessage,
            // ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateFamily->errors(),
                // 'alert' => $errorMessage,
            ], 401);
        }

        $user = User::find($request->input('user_id'));

        if(Role::find($user->role_id)->name != "cashier")
        {
            return response()->json([
                'success' => false,
                'message' => 'Solo se puede asignar un cajero a una caja',
                'errors' => [
                    "user_id" => "Only cashier can be assigned to a box"
                ]
            ], 401);
        }

        $checkBox = Boxs::where('user_id', $request->input('user_id'))->where('admin_id', auth()->user()->id)->count();
      

        if($checkBox != 0)
        {
            return response()->json([
                'success' => false,
                'message' => "A cada cajero se le puede asignar una sola caja.",
                'errors' => [
                    "user_id" => "One cashier can be assigned on box only."
                ]
            ],403);
        }

        $box = Boxs::create([
            'user_id' => $request->input('user_id'),
            'name' => $request->input('name'),
            'admin_id' => auth()->user()->id 
        ]);

        // $successMessage = "La caja {$box->name} ha sido creada exitosamente y asignada a {$user->name}.";
        // broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
        // Notification::create([
        //     'user_id' => auth()->user()->id, 
        //     'notification_type' => 'notification',
        //     'notification' => $successMessage,
        // ]);

        return response()->json([
            'success' => true,
            'box' => $box,
            'message' => 'Box added successfully.',
            // 'notification' => $successMessage,
        ], 200);
    }
    
     public function getAllBoxsLog(){
        $boxlog = BoxLogs::all();
        return response()->json($boxlog, 200);
    }

    public function updateBox(Request $request,$id)
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
            'user_id' => 'required|integer|exists:users,id',
            'name' => 'required|string|max:255'
        ]);

        if($validateFamily->fails())
        {
            // $errorMessage = 'No se pudo actualizar la caja. Verifica la información ingresada e intenta nuevamente.';
            // broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            // Notification::create([
            //     'user_id' => auth()->user()->id,
            //     'notification_type' => 'alert',
            //     'notification' => $errorMessage,
            // ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateFamily->errors(),
                // 'alert' => $errorMessage,
            ], 401);
        }

        $user = User::find($request->input('user_id'));

        if(Role::find($user->role_id)->name != "cashier")
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => [
                    "user_id" => "Only cashier can be assigned to a box"
                ]
            ], 401);
        }
        
        $box = Boxs::find($id);
        $box->update([
            'user_id' => $request->input('user_id'),
            'name' => $request->input('name'),
            'admin_id' => auth()->user()->id 
        ]);

        // $successMessage = "La caja {$box->name} ha sido actualizada exitosamente.";
        // broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
        // Notification::create([
        //     'user_id' => auth()->user()->id, 
        //     'notification_type' => 'notification',
        //     'notification' => $successMessage,
        // ]);


        return response()->json([
            'success' => true,
            'box' => $box,
            'message' => 'Box Updated Successfully.',
            // 'notification' => $successMessage,
        ], 200);
    }

    public function deleteBox($id)
    {
        $boxs = Boxs::find($id);
        if (is_null($boxs)) {
            // $errorMessage = 'No se pudo eliminar la caja. Verifica si la caja está asociada a otros registros e intenta nuevamente.';
            // broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            // Notification::create([
            //     'user_id' => auth()->user()->id,
            //     'notification_type' => 'alert',
            //     'notification' => $errorMessage,
            // ]);
            return response()->json(['message' => 'Box not found','alert'=>$errorMessage], 404);
        }
        $boxs->delete();

        // $successMessage = "La caja {$boxs->name} ha sido eliminada del sistema.";
        // broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
        // Notification::create([
        //     'user_id' => auth()->user()->id, 
        //     'notification_type' => 'notification',
        //     'notification' => $successMessage,
        // ]);

        return response()->json(['message' => 'Box deleted successfully','notification'=>$successMessage], 200);
    }

    public function Boxsearch(Request $request)
    {
        $ids = $request->input('ids', []);
        $boxQuery = Boxs::query();
        if (!empty($roleIds)) {
            $boxQuery->whereIn('ids', $ids);
        }
        $boxs = $boxQuery->get();

        foreach ($boxs as $box) {
            $boxLog = BoxLogs::where('box_id', $box->id)->get()->last();

            if($boxLog == null)
            {
                $box['status'] = "Not opened";
            }
            else if($boxLog->close_time != null)
            {
                $box['status'] = "Not opened";
            }
            else 
            {
                $box['status'] = "Opened";
                $box['open_amount'] = $boxLog->open_amount;
                $box['open_time'] = $boxLog->open_time;
                $box['open_by'] = User::find($boxLog->open_by)->name;
            }

            $box['log'] = $boxLog = BoxLogs::where('box_id', $box->id)->get();
        }
        return response()->json($boxs, 200);
    } 
    
    public function getAllBox(Request $request,$id)
    {
            $query = BoxLogs::where('box_id', $id);

            if ($request->has('from_month') && $request->has('to_month')) {
    
                $startDate = Carbon::create(null, $request->query('from_month'), 1)->startOfMonth();
                $endDate = Carbon::create(null, $request->query('to_month'), 1)->endOfMonth();
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
    
            $boxs = $query->get();
            return response()->json($boxs, 200);
    }

public function GetAllBoxLog(Request $request, $id)
{
    // Validate incoming request
    $request->validate([
        'payment_id' => 'nullable|exists:payments,id',
        'order_master_id' => 'nullable|array', // Ensure this is an array
        'order_master_id.*' => 'exists:order_masters,id', // Validate each order_master_id
    ]);

    // Fetch the box log by ID
    $boxLog = BoxLogs::find($id); // Fetch a single BoxLog by ID
    if (!$boxLog) {
        return response()->json(['message' => 'Box log not found'], 404);
    }

    // Insert or update payment ID if provided
    if ($request->has('payment_id')) {
        $boxLog->payment_id = $request->input('payment_id');
        $boxLog->save(); // Save the updated payment_id
    }

    // Update order_master_id for each provided ID
    if ($request->has('order_master_id')) {
        $orderMasterIds = $request->input('order_master_id');
        $boxLog->order_master_id = json_encode($orderMasterIds); // Store as JSON
        $boxLog->save(); // Save the updated order_master_id
    }

    // Fetch related orders based on the updated order_master_id
    $orderMasterIds = json_decode($boxLog->order_master_id, true); // Decode as array
    $orders = is_array($orderMasterIds) ? OrderMaster::whereIn('id', $orderMasterIds)->get() : collect();

    // Process each order
    foreach ($orders as $order) {
        $customer = User::find($order->user_id);
        $boxLog->customer = $customer ? $customer->name : null;

        $orderDetails = OrderDetails::where('order_master_id', $order->id)->get();
        $boxLog->items = $orderDetails;

        $total = 0;
        foreach ($orderDetails as $detail) {
            $detail->total = $detail->quantity * $detail->amount;
            $total += $detail->total;
        }
        $boxLog->order_total = $total;

        // Adding tip, discount, and delivery cost
        $boxLog->tip = $order->tip; // Assuming tip is a field in OrderMaster
        $boxLog->discount = $order->discount; // Assuming discount is a field in OrderMaster
        $boxLog->delivery_cost = $order->delivery_cost; // Assuming delivery_cost is a field in OrderMaster

        // Fetching payment details
        $payment = Payment::where('order_master_id', $order->id)->first(); // Assuming Payment model exists
        $boxLog->payment_id = $payment ? $payment->id : null;
        $boxLog->payment_type = $payment ? $payment->type : null; // Assuming 'type' is a field in Payment
        $boxLog->payment_amount = $payment ? $payment->amount : null; // Assuming 'amount' is a field in Payment
    }

    return response()->json($boxLog, 200); // Return the updated box log
}
    public function BoxStatusChange(Request $request)
    {
        $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        if($role != "admin" && $role != "cashier")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }
        $validateInitial = Validator::make($request->all(),[
            'box_id' => 'required|exists:boxs,id'
        ]);

        if($validateInitial->fails())
        {
            return response()->json([
                'success' => false,
                'message' => "Validation fails",
                'errors' => $validateInitial->errors()
            ], 403);
        }
        
        $boxLog = BoxLogs::where('box_id', $request->input('box_id'))->get()->last();

        if($boxLog == null)
        {
            $validateLater = Validator::make($request->all(), [
                'open_amount' => 'required|numeric|min:0'
            ]);

            if($validateLater->fails())
            {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation fails',
                    'errors' => $validateLater->errors()
                ], 403);
            }

            $log = BoxLogs::create([
                'box_id' => $request->input('box_id'),
                'open_amount' => $request->input('open_amount'),
                'open_by' => Auth::user()->id,
                'open_time' => Carbon::now(),
                'collected_amount' => 0
            ]);

            return response()->json([
                'success' => true,
                'box' => $log
            ], 200);
        }
        else if($boxLog->close_time != null)   
        {
            $validateLater = Validator::make($request->all(), [
                'open_amount' => 'required|numeric|min:0'
            ]);

            if($validateLater->fails())
            {
                // $errorMessage = 'No se pudo abrir la caja. Verifica la información ingresada e intenta nuevamente.';
                // broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
                // Notification::create([
                //     'user_id' => auth()->user()->id,
                //     'notification_type' => 'alert',
                //     'notification' => $errorMessage,
                // ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validation fails',
                    'errors' => $validateLater->errors(),
                    // 'alert' => $errorMessage,
                ], 403);
            }
        
            $box = Boxs::find($request->box_id);
           
            $log = BoxLogs::create([
                'box_id' => $request->input('box_id'),
                'open_amount' => $request->input('open_amount'),
                'open_by' => Auth::user()->id,
                'open_time' => Carbon::now(),
                'collected_amount' => 0
            ]);

            // $successMessage = "La caja {$box->name} ha sido abierta exitosamente con un monto inicial de {$request->open_amount}.";
            // broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
            // Notification::create([
            //     'user_id' => auth()->user()->id, 
            //     'notification_type' => 'notification',
            //     'notification' => $successMessage,
            // ]);

            return response()->json([
                'success' => true,
                'box' => $log,
                // 'notification' => $successMessage,
            ], 200);
        }
        else
        {
            $validateLater = Validator::make($request->all(), [
                'close_amount' => 'required|numeric|min:0'
            ]);

            if($validateLater->fails())
            {
                // $errorMessage = 'No se pudo cerrar la caja. Verifica la información ingresada e intenta nuevamente.';
                // broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
                // Notification::create([
                //     'user_id' => auth()->user()->id,
                //     'notification_type' => 'alert',
                //     'notification' => $errorMessage,
                // ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validation fails',
                    'errors' => $validateLater->errors(),
                    // 'alert' => $errorMessage
                ], 403);
            }
            $box = Boxs::find($request->box_id);

            $boxLog->close_amount = $request->input('close_amount');
            $boxLog->close_by = Auth::user()->id;
            $boxLog->close_time = Carbon::now();

            $boxLog->save();

            // $successMessage = "La caja {$box->name} ha sido cerrada exitosamente con un monto final de {$request->close_amount}.";
            // broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
            // Notification::create([
            //     'user_id' => auth()->user()->id, 
            //     'notification_type' => 'notification',
            //     'notification' => $successMessage,
            // ]);

            return response()->json([
                'success' => true,
                'box' => $boxLog,
                // 'notification'=> $successMessage
            ],200);
        }
    }

    public function BoxReportMonthWise(Request $request, $id)
    {
        if(Boxs::find($id) == null)
        {
            return response()->json([
                'success' => false,
                'message' => 'Box is is not valid'
            ],403);
        }

        $responseData = [];

        $ordersQuery = OrderMaster::where('box_id', $id);

        if($request->has('from_month') && $request->has('to_month'))
        {
            $startDate = Carbon::create(null, $request->query('from_month'), 1)->startOfMonth();
            $endDate = Carbon::create(null, $request->query('to_month'), 1)->endOfMonth();
            $ordersQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        $orders = $ordersQuery->get();

        foreach ($orders as $order) {
            $customer = User::find($order->user_id);

            if ($customer != null) {
                $order->customer = $customer->name;
            }

            $orderDetails = OrderDetails::where('order_master_id', $order->id)->get();
            $order->items = $orderDetails;

            $total = 0;
            foreach ($orderDetails as $detail) {
                $detail->total = $detail->quantity * $detail->amount;
                $total += $detail->total;
            }
            $order->order_total = $total;

            $responseData[] = $order;
        }
        $box = Boxs::find($id);
        // $successMessage = "La consulta de la caja {$box->name} se ha realizado exitosamente.";
        // broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
        // Notification::create([
        //     'user_id' => auth()->user()->id, 
        //     'notification_type' => 'notification',
        //     'notification' => $successMessage,
        // ]);

        return response()->json([$orders, 200]);
    }
}
