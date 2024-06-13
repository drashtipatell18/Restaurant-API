<?php

namespace App\Http\Controllers;

use App\Models\BoxLogs;
use App\Models\Boxs;
use App\Models\OrderDetails;
use App\Models\OrderMaster;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Mail\RegistrationConfirmation;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return response()->json($users, 200);
    }

    public function storeUser(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role_id' => 'nullable|exists:roles,id',
            'password' => 'required|string|min:8|same:confirm_password',
            'confirm_password' => 'required|string|min:8|same:password',
            'image' => 'nullable|file|mimes:jpg,png,jpeg,gif|max:2048',
        ]);

        // Handle validation errors
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'error' => $validator->errors()
            ], 401);
        }

        // Move uploaded image to storage if provided
        $filename = '';
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images'), $filename);
        }

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'password' => Hash::make($request->password),
            'image' => $filename,
        ]);

        // Check if 'invite' parameter is present in request
        if ($request->has('invite')) {
            // Generate a new remember token for the user
            $user->remember_token = Str::random(40);
            $user->save();

            // Send the registration confirmation email to the user
            Mail::to($user->email)->send(new RegistrationConfirmation($user, $request->password));

            // Return JSON response with success message and user data
            return response()->json([
                'success' => true,
                'message' => 'Registration successful. Email sent with login details.',
                'user' => $user,
            ], 200);
        }

        // Return JSON response with success message and user data
        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'user' => $user,
        ], 200);
    }

    public function updateUser(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'role_id' => 'nullable|exists:roles,id',
            'password' => 'required|string|min:8|same:confirm_password',
            'confirm_password' => 'required|string|min:8|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'error' => $validator->errors()
            ], 401);
        }

        $users = User::find($id);
        if (is_null($users)) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images'), $filename); // Ensure the 'images' directory exists and is writable
            $users->image = $filename;
        }

        $users->update([
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'password' => Hash::make($request->password),

        ]);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $users,
        ], 200);
    }

    public function destroyUser($id)
    {
        $user = User::find($id);
        if (is_null($user)) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }

    public function Rolesearch(Request $request)
    {
        $roleIds = $request->input('role_ids', []);
        $usersQuery = User::query();
        if (!empty($roleIds)) {
            $usersQuery->whereIn('role_id', $roleIds);
        }
        $users = $usersQuery->get();
        return response()->json($users, 200);
    }

    public function getUser($id)
    {
        $user = User::find($id);
        return response()->json($user, 200);
    }

    public function Monthsearch(Request $request)
    {
        $startMonth = $request->input('start_month');
        $endMonth = $request->input('end_month');
        $year = $request->input('year', Carbon::now()->year); // Default to current year
        // Validate input
        if (is_null($startMonth) || is_null($endMonth) || is_null($year)) {
            return response()->json(['error' => 'Year, start month, and end month are required'], 400);
        }

        if ($startMonth < 1 || $startMonth > 12 || $endMonth < 1 || $endMonth > 12) {
            return response()->json(['error' => 'Invalid month provided'], 400);
        }

        $usersQuery = User::query();

        // Define the date range based on input
        $startDate = Carbon::create($year, $startMonth, 1)->startOfDay();
        $endDate = Carbon::create($year, $endMonth)->endOfMonth()->endOfDay();

        // Add the date range condition
        $usersQuery->whereBetween('created_at', [$startDate, $endDate]);

        // Fetch the users
        $users = $usersQuery->get();

        // Return the users as JSON response
        return response()->json($users, 200);
    }

    public function dashboard(Request $request)
    {
        $validateRequest = Validator::make($request->all(), [
            'duration' => 'in:day,week,month',
            'month' => 'in:1,2,3,4,5,6,7,8,9,10,11,12'
        ]);

        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }
        $responseData = [];

        $orders = OrderMaster::query();
        $orderDetails = OrderDetails::query();

        if ($request->has('duration') && $request->input('duration') == "month") {
            $orders = $orders->whereMonth('created_at', $request->input('month'));
            $orderDetails = $orderDetails->whereMonth('created_at', $request->input('month'));
        }
        if ($request->has('duration') && $request->input('duration') == "day") {
            $orders = $orders->whereDate('created_at', $request->input('day'));
            $orderDetails = $orderDetails->whereDate('created_at', $request->input('day'));
        }

        $orders = $orders->get();
        $orderDetails = $orderDetails->get();

        $sale = 0;
        $cost = 0;
        foreach ($orderDetails as $orderDetail) {
            $sale += $orderDetail->amount * $orderDetail->quantity;
            $cost += $orderDetail->cost * $orderDetail->quantity;
        }

        $responseData['statistical_data'] = [
            "total_orders" => $orders->count(),
            "total_income" => $sale - $cost,
            "delivery_orders" => $orders->where('order_type', 'delivery')->count()
        ];

        $responseData['payment_methods'] = [
            'cash' => $orders->where('payment_type', 'cash')->count(),
            'debit' => $orders->where('payment_type', 'debit')->count(),
            'credit' => $orders->where('payment_type', 'credit')->count(),
            'transfer' => $orders->where('payment_type', 'transfer')->count()
        ];

        $responseData['total_revenue'] = $sale;

        $responseData['statusSummary'] = [
            'received' => $orders->where('status', 'received')->count(),
            'prepared' => $orders->where('status', 'prepared')->count(),
            'delivered' => $orders->where('status', 'delivered')->count(),
            'finalized' => $orders->where('status', 'finalized')->count()
        ];

        $mostOrderedItems = OrderDetails::select('items.name', 'items.image', DB::raw('COUNT(order_details.item_id) as order_count'))
            ->join('items', 'order_details.item_id', '=', 'items.id')
            ->join('order_masters', 'order_details.order_master_id', '=', 'order_masters.id')
            ->groupBy('order_details.item_id', 'items.name', 'items.image')
            ->orderBy('order_count', 'desc')
            ->get();

        $responseData['popular_products'] = $mostOrderedItems;

        $responseData['box_entry'] = [];
        $boxs = Boxs::all();

        foreach ($boxs as $box) {
            $logs = BoxLogs::query('box_id', $box->id);

            if ($request->has('duration') && $request->input('duration') == "month") {
                $logs = $logs->whereMonth('created_at', $request->input('month'));
            }
            if ($request->has('duration') && $request->input('duration') == "day") {
                $logs = $logs->whereDate('created_at', $request->input('day'));
            }

            $logs = $logs->get();

            array_push($responseData['box_entry'], ['box' => $box->name, 'collected_amount' => $logs->sum('collected_amount')]);
        }

        $responseData['cancelled_orders'] = $orders->where('status', 'cancelled')->all();

        return response()->json($responseData, 200);
    }

    public function getOrders(Request $request, $id)
    {
        if(User::find($id) == null)
        {
            return response()->json([
                'success' => false,
                'message' => "User id is not valid"
            ], 403);
        }

        $responseData = [];
        $orders = OrderMaster::where('user_id', $id)->get();

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

        $cancelledOrders = OrderMaster::onlyTrashed()
            ->where('user_id', $id)
            ->get();
        
        foreach ($cancelledOrders as $order) {
            $order['status'] = "cancelled";
            $customer = User::find($order->user_id);

            if ($customer != null) {
                $order['customer'] = $customer->name;
            }

            $orderDetails = OrderDetails::onlyTrashed()
                ->where('order_master_id', $order->id)
                ->get();
            $order['items'] = $orderDetails;

            $total = 0;
            foreach ($orderDetails as $detail) {
                $detail['total'] = $detail->quantity * $detail->amount;
                $total += $detail->total;
            }
            $order['order_total'] = $total;

            $responseData[] = $order;
        }

        if($request->has('order_id'))
        {
            $order = [];
            foreach ($responseData as $response) {
                if($response['id'] == $request->query('order_id'))
                {
                    $order = $response;
                    break;
                }
            }

            $responseData = $order;
        }

        return response()->json($responseData, 200);
    }
}
