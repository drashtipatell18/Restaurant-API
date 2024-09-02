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
use App\Mail\UpdateConfirmation;
use App\Models\Payment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;


class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        foreach ($users as $user) {
            $encryptedPassword = $user->password;
            $decryption_iv = '1234567891011121';
            $decryption_key = "GeeksforGeeks";
            $ciphering = "AES-128-CTR";
            $options = 0;


            // Decrypt the password
            $decryptedPassword = openssl_decrypt($encryptedPassword, $ciphering, $decryption_key, $options, $decryption_iv);

            // Update the user's password with the decrypted password
            $user->password = mb_convert_encoding($decryptedPassword, 'UTF-8', 'UTF-8');
            $user->confirm_password = mb_convert_encoding($decryptedPassword, 'UTF-8', 'UTF-8');
        }

        return response()->json($users, 200, [], JSON_UNESCAPED_UNICODE);
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

        $newpassword = $request->password;


        // Password Encryption store in pass filed to database

        $simple_string = $request->password;

        $ciphering = "AES-128-CTR";
        $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;
        $encryption_iv = '1234567891011121';
        $encryption_key = "GeeksforGeeks";
        $encryption = openssl_encrypt(
            $simple_string,
            $ciphering,
            $encryption_key,
            $options,
            $encryption_iv
        );


        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'password' => $encryption,
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

        $simple_string = $request->password;

        $ciphering = "AES-128-CTR";
        $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;
        $encryption_iv = '1234567891011121';
        $encryption_key = "GeeksforGeeks";
        $encryption = openssl_encrypt(
            $simple_string,
            $ciphering,
            $encryption_key,
            $options,
            $encryption_iv
        );


        // Check if 'invite' parameter is present in request
        if ($request->has('invite')) {
            // Generate a new remember token for the user
            $users->remember_token = Str::random(40);
            $users->save();

            // Send the registration confirmation email to the user
            Mail::to($users->email)->send(new UpdateConfirmation($users, $request->password));

            // Return JSON response with success message and user data
            return response()->json([
                'success' => true,
                'message' => 'Registration successful. Email sent with login details.',
                'user' => $users,
            ], 200);
        }
        $users->update([
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'password' => $encryption,

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

        //     $abc = "123456789";
        //     $bcrypassword = bcrypt($abc);
        //    $bcrypassword = decrypt($bcrypassword);

        $user = User::find($id);
        $originalPassword = $user->password;

        $user = User::find($id);

        // Decryption To pass Filed
        $encryption = $user->password;
        $decryption_iv = '1234567891011121';
        $decryption_key = "GeeksforGeeks";
        $ciphering = "AES-128-CTR";
        $options = 0;
        $decryption = openssl_decrypt($encryption, $ciphering, $decryption_key, $options, $decryption_iv);

        $user->password = $decryption;
        $user->confirm_password = $decryption;

        return response()->json([$user, 200]);
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

    public function getDelivery(Request $request)
    {
        $validateRequest = Validator::make($request->all(), [
            'duration' => 'in:day,week,month',
            'day' => 'date', // Validate day as a date
            'month' => 'in:1,2,3,4,5,6,7,8,9,10,11,12'
        ]);

        // Return an error response if validation fails
        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }
        $orders = OrderMaster::query();

        if ($request->has('duration')) {
            if ($request->input('duration') == "month" && $request->has('month')) {
                $orders->whereMonth('created_at', $request->input('month'));
            } elseif ($request->input('duration') == "day" && $request->has('day')) {
                $orders->whereDate('created_at', $request->input('day'));
            } elseif ($request->input('duration') == "week") {
                $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
                $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');
                $orders->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
            }
        }

        $deliveryMethods = [
            'delivery' => $orders->clone()->where('order_type', 'delivery')->count(),
            'withdrawal' => $orders->clone()->where('order_type', 'withdrawal')->count(),
            'local' => $orders->clone()->where('order_type', 'local')->count(),
            'platform' => $orders->clone()->where('order_type', 'platform')->count()
        ];

        return response()->json(['delivery_methods' => $deliveryMethods], 200);


    }

    public function getStatisticalData(Request $request)
    {
        $validateRequest = Validator::make($request->all(), [
            'duration' => 'in:day,week,month',
            'day' => 'date', // Validate the day as a date
            'month' => 'in:1,2,3,4,5,6,7,8,9,10,11,12'
        ]);

        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        $orders = OrderMaster::query();
        $payment = Payment::query();

        $orderDetails = OrderDetails::query();

        if ($request->input('duration') == "month") {
            $orders->whereMonth('created_at', $request->input('month'));
            $orderDetails->whereMonth('created_at', $request->input('month'));
            $payment->whereMonth('created_at', $request->input('month'));
        } elseif ($request->input('duration') == "day") {
            $orders->whereDate('created_at', $request->input('day'));
            $orderDetails->whereDate('created_at', $request->input('day'));
            $payment->whereDate('created_at', $request->input('day'));
        } elseif ($request->input('duration') == "week") {
            $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
            $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');
            $orders->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
            $orderDetails->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
            $payment->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
        }

        $totalOrdersCount = $orders->count();

        $totalDays = 0;

        if ($request->has('duration')) {
            $duration = $request->input('duration');
            if ($duration === 'month') {
                $totalDays = now()->copy()->month($request->input('month'))->daysInMonth;
            } elseif ($duration === 'week') {
                $totalDays = 7; // A week has 7 days
            } elseif ($duration === 'day') {
                $totalDays = 1; // For a single day
            }
        } else {
            $totalDays = 365; // Default to 1 year if no duration is provided
        }

        $sale = $payment->sum('amount');
        $returns = $payment->sum('return');

        $totalAverage =( $sale - $returns )/ $totalDays;

        $statisticalData = [
            "total_orders_count" => $orders->count(),
            "total_orders" => $orders->get(),
            "total_payments" => $payment->get(),
            "total_income" => $sale - $returns,
            "delivery_orders_count" => $orders->where('status', 'delivered')->count(),
             "delivery_orders" => $orders->where('status', 'delivered')->get(),
             "total_average" => $totalAverage,
             "total_days" => $totalDays
        ];

        return response()->json(['statistical_data' => $statisticalData], 200);
    }

    public function getPaymentMethods(Request $request)
    {
        // Validate the request inputs
        $validateRequest = Validator::make($request->all(), [
            'duration' => 'in:day,week,month',
            'day' => 'date', // Validate day as a date
            'month' => 'in:1,2,3,4,5,6,7,8,9,10,11,12'
        ]);

        // Return an error response if validation fails
        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        // Initialize the query for orders
        $orders = OrderMaster::query();


        // Filter by month, day, or week based on the 'duration' input
        if ($request->has('duration')) {
            if ($request->input('duration') == "month" && $request->has('month')) {
                $orders->whereMonth('created_at', $request->input('month'));
            } elseif ($request->input('duration') == "day" && $request->has('day')) {
                $orders->whereDate('created_at', $request->input('day'));
            } elseif ($request->input('duration') == "week") {
                $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
                $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');
                $orders->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
            }
        }

        // Count orders by payment type
        $paymentMethods = [
            'cash' => $orders->clone()->where('payment_type', 'cash')->count(),
            'debit' => $orders->clone()->where('payment_type', 'debit')->count(),
            'credit' => $orders->clone()->where('payment_type', 'credit')->count(),
            'transfer' => $orders->clone()->where('payment_type', 'transfer')->count()
        ];


        // Return the payment methods data as a JSON response
        return response()->json(['payment_methods' => $paymentMethods], 200);
    }

    // public function getTotalRevenue(Request $request)
    // {
    //     $validateRequest = Validator::make($request->all(), [
    //         'duration' => 'in:day,week,month',
    //         'day' => 'date',
    //         'month' => 'in:1,2,3,4,5,6,7,8,9,10,11,12'
    //     ]);

    //     if ($validateRequest->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation fails',
    //             'errors' => $validateRequest->errors()
    //         ], 403);
    //     }

    //     $orderDetails = OrderDetails::query();

    //     if ($request->input('duration') == "month") {
    //         $orderDetails = $orderDetails->whereMonth('created_at', $request->input('month'));
    //     } elseif ($request->input('duration') == "day") {
    //         $orderDetails = $orderDetails->whereDate('created_at', $request->input('day'));
    //     } elseif ($request->input('duration') == "week") {
    //         $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
    //         $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');
    //         $orderDetails = $orderDetails->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
    //     }

    //     $totalRevenue = $orderDetails->sum(DB::raw('amount * quantity'));

    //     return response()->json(['total_revenue' => $totalRevenue], 200);
    // }

    public function getTotalRevenue(Request $request)
    {
        $validateRequest = Validator::make($request->all(), [
            'duration' => 'in:day,week,month',
            'day' => 'date',
            'month' => 'in:1,2,3,4,5,6,7,8,9,10,11,12'
        ]);
    
        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }
    
        $orderDetails = OrderDetails::query();
    
        if ($request->input('duration') == "month") {
            $orderDetails = $orderDetails->whereMonth('created_at', $request->input('month'));
        } elseif ($request->input('duration') == "day") {
            $orderDetails = $orderDetails->whereDate('created_at', $request->input('day'));
        } elseif ($request->input('duration') == "week") {
            $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
            $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');
            $orderDetails = $orderDetails->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
        }
    
        $totalRevenue = $orderDetails->sum(DB::raw('amount * quantity'));
    
        $orderDetails = $orderDetails->get(['amount', 'quantity', 'created_at']);
    
       $data = [
    'total_revenue' => $totalRevenue,
            'order_details' => $orderDetails
    ];
        return response()->json([
            'total_revenue' => $data
            
        ], 200);
    
    
    }
    public function getStatusSummary(Request $request)
    {
        $validateRequest = Validator::make($request->all(), [
            'duration' => 'in:day,week,month',
            'day' => 'date', // Validate the day as a date
            'month' => 'in:1,2,3,4,5,6,7,8,9,10,11,12'
        ]);

        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        $orders = OrderMaster::get();

        if ($request->input('duration') == "month") {
            $orders = $orders->whereMonth('created_at', $request->input('month'));
        } elseif ($request->input('duration') == "day") {
            $orders = $orders->whereDate('created_at', $request->input('day'));
        } elseif ($request->input('duration') == "week") {
            $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
            $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');
            $orders = $orders->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
        }

        $statusSummary = [
            'received' => $orders->where('status', 'received')->count(),
            'prepared' => $orders->where('status', 'prepared')->count(),
            'delivered' => $orders->where('status', 'delivered')->count(),
            'finalized' => $orders->where('status', 'finalized')->count()
        ];

        return response()->json(['statusSummary' => $statusSummary], 200);
    }

    // public function getPopularProducts(Request $request)
    // {
    //     // Validate the input
    //     $validateRequest = Validator::make($request->all(), [
    //         'duration' => 'in:day,week,month',
    //         'month' => 'in:1,2,3,4,5,6,7,8,9,10,11,12',
    //         'day' => 'date',
    //         'week' => 'integer|min:1|max:53' // Assuming week can be 1 to 53
    //     ]);

    //     if ($validateRequest->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation fails',
    //             'errors' => $validateRequest->errors()
    //         ], 403);
    //     }

    //     $orderDetailsQuery = OrderDetails::select('items.name', 'items.image', DB::raw('COUNT(order_details.item_id) as order_count'))
    //         ->join('items', 'order_details.item_id', '=', 'items.id')
    //         ->groupBy('order_details.item_id', 'items.name', 'items.image')
    //         ->orderBy('order_count', 'desc');



    //     // Apply the filter based on the duration
    //     if ($request->input('duration') == "month") {
    //         $orderDetailsQuery->whereMonth('order_details.created_at', $request->input('month'));
    //     } elseif ($request->input('duration') == "day") {
    //         $orderDetailsQuery->whereDate('order_details.created_at', $request->input('day'));
    //     } elseif ($request->input('duration') == "week") {
    //         $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
    //         $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');
    //         $orderDetailsQuery->whereBetween('order_details.created_at', [$startOfWeek, $endOfWeek]);
    //     }

    //     $mostOrderedItems = $orderDetailsQuery->get();

    //     return response()->json(['popular_products' => $mostOrderedItems], 200);
    // }

    public function getPopularProducts(Request $request)
    {
        // Validate the input
        $validateRequest = Validator::make($request->all(), [
            'duration' => 'in:day,week,month',
            'month' => 'in:1,2,3,4,5,6,7,8,9,10,11,12',
            'day' => 'date',
            'week' => 'integer|min:1|max:53' // Assuming week can be 1 to 53
        ]);

        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        // $orderDetailsQuery = OrderDetails::select('items.name', 'items.image', DB::raw('COUNT(order_details.item_id) as order_count'))
        //     ->join('items', 'order_details.item_id', '=', 'items.id')
        //     ->groupBy('order_details.item_id', 'items.name', 'items.image')
        //     ->orderBy('order_count', 'desc');
         $orderDetailsQuery = OrderDetails::select(
            'items.name',
            'items.image',
            'order_details.amount', // Include the amount from order_details
            DB::raw('COUNT(order_details.item_id) as order_count')
        )
        ->join('items', 'order_details.item_id', '=', 'items.id')
        ->groupBy('order_details.item_id', 'items.name', 'items.image', 'order_details.amount')
        ->orderBy('order_count', 'desc');

        // Apply the filter based on the duration
        if ($request->input('duration') == "month") {
            $orderDetailsQuery->whereMonth('order_details.created_at', $request->input('month'));
        } elseif ($request->input('duration') == "day") {
            $orderDetailsQuery->whereDate('order_details.created_at', $request->input('day'));
        } elseif ($request->input('duration') == "week") {
            $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
            $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');
            $orderDetailsQuery->whereBetween('order_details.created_at', [$startOfWeek, $endOfWeek]);
        }

        $mostOrderedItems = $orderDetailsQuery->get();

        return response()->json(['popular_products' => $mostOrderedItems], 200);
    }

    public function getBoxEntry(Request $request)
    {

        // Validate the input
        $validateRequest = Validator::make($request->all(), [
            'duration' => 'in:day,week,month',
            'month' => 'in:1,2,3,4,5,6,7,8,9,10,11,12',
            'day' => 'date',
            'week' => 'integer|min:1|max:53' // Assuming week can be 1 to 53
        ]);

        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        $boxEntry = [];

        // Get all boxes
        $boxes = Boxs::all();

        // Loop through each box
        foreach ($boxes as $box) {
            // Start the query for BoxLogs for the current box
            $logs = BoxLogs::where('box_id', $box->id);

            // Apply filtering based on the duration
            if ($request->input('duration') == "month") {
                $logs->whereMonth('created_at', $request->input('month'));
            } elseif ($request->input('duration') == "day") {
                $logs->whereDate('created_at', $request->input('day'));
            } elseif ($request->input('duration') == "week") {
                $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
                $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');
                $logs->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
            }

            // Get the filtered logs
            $filteredLogs = $logs->get();

            foreach ($filteredLogs as $log) {
                $log->collect_amount = $log->close_amount - $log->open_amount;
            }
            // Add the box with its filtered logs to the boxEntry array
            $boxEntry[] = [
                'box_id' => $box->id,
                'box_name' => $box->name,
                'logs' => $filteredLogs
            ];

        }

        // Return the box entries with their logs
        return response()->json(['box_entries' => $boxEntry], 200);
    }

    public function cancelOrders(Request $request)
    {
        $validateRequest = Validator::make($request->all(), [
            'duration' => 'in:day,week,month',
            'day' => 'date',
            'month' => 'in:1,2,3,4,5,6,7,8,9,10,11,12'
        ]);

        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        $orders = OrderMaster::query()->where('status', 'cancelled');

        if ($request->has('duration') && $request->input('duration') == "month") {
            $orders = $orders->whereMonth('created_at', $request->input('month'));
        } elseif ($request->has('duration') && $request->input('duration') == "week") {
            $orders = $orders->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($request->has('duration') && $request->input('duration') == "day") {
            $orders = $orders->whereDate('created_at', $request->input('day'));
        }

        $cancelledOrders = $orders->get();

        return response()->json([
            'success' => true,
            'message' => 'List of cancelled orders',
            'cancelled_orders' => $cancelledOrders
        ], 200);
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

        $totalOrdersCount = $orders->count();

        $totalDays = 0;

        if ($request->has('duration')) {
            $duration = $request->input('duration');
            if ($duration === 'month') {
                $totalDays = now()->copy()->month($request->input('month'))->daysInMonth;
            } elseif ($duration === 'week') {
                $totalDays = 7; // A week has 7 days
            } elseif ($duration === 'day') {
                $totalDays = 1; // For a single day
            }
        } else {
            $totalDays = 365; // Default to 1 year if no duration is provided
        }


        $totalAverage = $totalDays > 0 ? $totalOrdersCount / $totalDays : 0;

        $responseData['statistical_data'] = [
            "total_orders" => $orders->count(),
            "total_income" => $sale - $cost,
            "delivery_orders" => $orders->where('order_type', 'delivery')->count(),
            "total_average" => $totalAverage
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
        if (User::find($id) == null) {
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

        if ($request->has('order_id')) {
            $order = [];
            foreach ($responseData as $response) {
                if ($response['id'] == $request->query('order_id')) {
                    $order = $response;
                    break;
                }
            }

            $responseData = $order;
        }

        return response()->json($responseData, 200);
    }

    public function getCasherUser()
    {

        $users = User::where('role_id', 2)->get();
        return response()->json($users, 200);
    }
}
