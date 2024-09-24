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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use App\Models\Payment;
use App\Models\Notification;
use App\Events\NotificationMessage;


class UserController extends Controller
{
    public function index()
    {
        if (auth()->user()->role == 'admin') {
            $users = User::all();
            // dd($users);
        } else {
            $userAdminId = auth()->user()->admin_id;
            $userId = auth()->user()->id;
            $users = User::where('admin_id', $userAdminId)
                        ->orWhere('id', $userId)
                        ->get();
        }

        // Decrypt passwords for non-admin users (for demonstration purposes only; not secure in production)
        foreach ($users as $user) {
            $encryptedPassword = $user->password;
            $decryption_iv = '1234567891011121';
            $decryption_key = "GeeksforGeeks";
            $ciphering = "AES-128-CTR";
            $options = 0;

            // Decrypt the password
            $decryptedPassword = openssl_decrypt($encryptedPassword, $ciphering, $decryption_key, $options, $decryption_iv);

            // If decryption is successful, update the password field (for demonstration purposes)
            if ($decryptedPassword !== false) {
                $user->password = mb_convert_encoding($decryptedPassword, 'UTF-8', 'UTF-8');
            }
        }

        // Return the users as a JSON response
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
            $passwordMismatchMessage = "No se pudo completar el registro. Verifica la información ingresada e intenta nuevamente.";
            broadcast(new NotificationMessage('notification', $passwordMismatchMessage))->toOthers();
            Notification::create([
                'user_id' => $request->id,
                'notification_type' => 'alert',
                'notification' => $passwordMismatchMessage,
                'admin_id' => $request->admin_id
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error' => $validator->errors(),
                'alert' => $passwordMismatchMessage
            ], 400);
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
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'password' => $encryption,
            'image' => $filename,
            'status' => 'Activa',
        ];

        if ($request->role_id != 1) {
            $userData['admin_id'] = auth()->user()->id; 
        }

        $user = User::create($userData);


        // Check if 'invite' parameter is present in request
        if ($request->has('invite')) {

            if (auth()->user()->role_id !== 1) {
                // Return an error response if the user is not an admin
                return response()->json([
                    'success' => false,
                    'message' => 'Error: Admin not found. Only admins can invite users.'
                ], 403);
            }

            // Generate a new remember token for the user
            $user->remember_token = Str::random(40);
            $user->save();

            // Send the registration confirmation email to the user
            Mail::to($user->email)->send(new RegistrationConfirmation($user, $request->password));
            $adminId = $user->role_id == 1 ? $user->id : $user->admin_id;
            $successMessage = "La invitación para el usuario {$user->name} ha sido enviada exitosamente al correo {$user->email}.";
                broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
                Notification::create([
                    'user_id' => $user->id,
                    'notification_type' => 'notification',
                    'notification' => $successMessage,
                    'admin_id' => $user->id,
                    'role_id' => $user->role_id
                ]);
                

            // Return JSON response with success message and user data
            return response()->json([
                'success' => true,
                'message' => 'Registration successful. Email sent with login details.',
                'user' => $user,
                'notification' => $successMessage,
            ], 200);
        
        $successMessage = "El usuario {$user->name} se ha registrado exitosamente en la aplicación..";
        broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
        Notification::create([
            'user_id' => $user->id,
            'notification_type' => 'notification',
            'notification' => $successMessage,
            'admin_id' => $user->admin_id
        ]);
        // Return JSON response with success message and user data
        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'user' => $user,
            'notification' => $successMessage
        ], 200);
    }
}


    // public function updateUser(Request $request, $id)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|string|email|max:255|unique:users,email,' . $id,
    //         'role_id' => 'nullable|exists:roles,id',
    //         'password' => 'required|string|min:8|same:confirm_password',
    //         'confirm_password' => 'required|string|min:8|same:password',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation fails',
    //             'error' => $validator->errors()
    //         ], 401);
    //     }

    //     $users = User::find($id);
    //     if (is_null($users)) {
    //         return response()->json(['message' => 'User not found'], 404);
    //     }

    //     if ($request->hasFile('image')) {
    //         $image = $request->file('image');
    //         $filename = time() . '.' . $image->getClientOriginalExtension();
    //         $image->move(public_path('images'), $filename); // Ensure the 'images' directory exists and is writable
    //         $users->image = $filename;
    //     }

    //     $simple_string = $request->password;

    //     $ciphering = "AES-128-CTR";
    //     $iv_length = openssl_cipher_iv_length($ciphering);
    //     $options = 0;
    //     $encryption_iv = '1234567891011121';
    //     $encryption_key = "GeeksforGeeks";
    //     $encryption = openssl_encrypt(
    //         $simple_string,
    //         $ciphering,
    //         $encryption_key,
    //         $options,
    //         $encryption_iv
    //     );


    //     // Check if 'invite' parameter is present in request
    //     if ($request->has('invite')) {
    //         // Generate a new remember token for the user
    //         $users->remember_token = Str::random(40);
    //         $users->save();

    //         // Send the registration confirmation email to the user
    //         Mail::to($users->email)->send(new UpdateConfirmation($users, $request->password));

    //         // Return JSON response with success message and user data
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Registration successful. Email sent with login details.',
    //             'user' => $users,
    //         ], 200);
    //     }
    //     $users->update([
    //         'name' => $request->name,
    //         'email' => $request->email,
    //         'role_id' => $request->role_id,
    //         'password' => $encryption,

    //     ]);

    //     return response()->json([
    //         'message' => 'User updated successfully',
    //         'user' => $users,
    //     ], 200);
    // }

    public function updateUser(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'role_id' => 'nullable|exists:roles,id',

            'password' => 'nullable|string|min:8|same:confirm_password',
            'confirm_password' => 'nullable|string|min:8|same:password',
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
            $image->move(public_path('images'), $filename);
            $users->image = $filename;
        }
    
        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
        ];
    
        if ($request->filled('role_id')) {
            $updateData['role_id'] = $request->role_id;
        }
    
        if ($request->filled('password')) {
            $simple_string = $request->password;
            $ciphering = "AES-128-CTR";
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
            $updateData['password'] = $encryption;
        }
    
        if ($request->has('invite')) {
            $users->remember_token = Str::random(40);
            $users->save();
    
            Mail::to($users->email)->send(new UpdateConfirmation($users, $request->password));
    
            return response()->json([
                'success' => true,
                'message' => 'Registration successful. Email sent with login details.',
                'user' => $users,
            ], 200);
        }

    
        $users->update($updateData);
    


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
        $authUser = auth()->user();
        $cashier_id = $authUser->id;
        $role = $authUser->role_id;

        if ($role == 1) { // Admin
            $user = User::where('admin_id' ,auth()->user()->id)->find($id);
        } else if ($role == 2) { // Cashier
        
            $cashierRecord = User::find($cashier_id);
            $cashIcd = $cashierRecord->admin_id;
    
        
            $user = User::where('admin_id', $cashIcd)->find($id);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        //     $abc = "123456789";
        //     $bcrypassword = bcrypt($abc);
        //    $bcrypassword = decrypt($bcrypassword);

       
        $originalPassword = $user->password;

       

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
        // Validate the input
        $validateRequest = Validator::make($request->all(), [
            'admin_id' => 'required|integer', // Ensure admin_id is present
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
    
        // Get the admin_id from the request
        $adminId = $request->input('admin_id');
    
        // Initialize the query for orders and filter by admin_id
        $orders = OrderMaster::query()->where('admin_id', $adminId);
    
        // Apply filters based on the 'duration'
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
    
        // Count the delivery methods based on order_type
        $deliveryMethods = [
            'delivery' => $orders->clone()->where('order_type', 'delivery')->count(),
            'withdrawal' => $orders->clone()->where('order_type', 'withdraw')->count(),
            'local' => $orders->clone()->where('order_type', 'local')->count(),
            'platform' => $orders->clone()->where('order_type', 'platform')->count()
        ];
    
        // Return the response with delivery methods data
        return response()->json([
            'admin_id' => $adminId,
            'delivery_methods' => $deliveryMethods
        ], 200);
    }
    
    // public function getStatisticalData(Request $request)
    // {
    //     $validateRequest = Validator::make($request->all(), [
    //         'duration' => 'in:day,week,month',
    //         'day' => 'date', // Validate the day as a date
    //         'month' => 'in:1,2,3,4,5,6,7,8,9,10,11,12'
    //     ]);

    //     if ($validateRequest->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation fails',
    //             'errors' => $validateRequest->errors()
    //         ], 403);
    //     }

    //     $orders = OrderMaster::query();
    //     $payment = Payment::query();

    //     $orderDetails = OrderDetails::query();

    //     if ($request->input('duration') == "month") {
    //         $orders->whereMonth('created_at', $request->input('month'));
    //         $orderDetails->whereMonth('created_at', $request->input('month'));
    //         $payment->whereMonth('created_at', $request->input('month'));
    //     } elseif ($request->input('duration') == "day") {
    //         $orders->whereDate('created_at', $request->input('day'));
    //         $orderDetails->whereDate('created_at', $request->input('day'));
    //         $payment->whereDate('created_at', $request->input('day'));
    //     } elseif ($request->input('duration') == "week") {
    //         $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
    //         $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');
    //         $orders->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
    //         $orderDetails->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
    //         $payment->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
    //     }
    //      $totalOrdersCount = $orders->count();
    //      $totalDays = 0;

    //     if ($request->has('duration')) {
    //         $duration = $request->input('duration');
    //         if ($duration === 'month') {
    //             $totalDays = now()->copy()->month($request->input('month'))->daysInMonth;
    //         } elseif ($duration === 'week') {
    //             $totalDays = 7; // A week has 7 days
    //         } elseif ($duration === 'day') {
    //             $totalDays = 1; // For a single day
    //         }
    //     } else {
    //         $totalDays = 365; // Default to 1 year if no duration is provided
    //     }
         

    //     $sale = $payment->sum('amount');
    //     $returns = $payment->sum('return');
        
    //     $totalAverage = ($sale - $returns)/ $totalDays;
        

    //     $statisticalData = [
    //         "total_orders_count" => $orders->count(),
    //         "total_orders" => $orders->get(),
    //         "total_payments" => $payment->get(),
    //         "total_income" => $sale - $returns,
    //         "delivery_orders_count" => $orders->where('status', 'delivered')->count(),
    //          "delivery_orders" => $orders->where('status', 'delivered')->get(),
    //          "total_average" => $totalAverage,
    //          "total_days" => $totalDays
    //     ];

    //     return response()->json(['statistical_data' => $statisticalData], 200);
    // }
    
    public function getStatisticalData(Request $request)
{
    // Step 1: Validate the request inputs
    $validateRequest = Validator::make($request->all(), [
        'duration' => 'in:day,week,month',  // Ensure the duration is valid
        'day' => 'date',                    // Validate 'day' as a valid date
        'month' => 'in:1,2,3,4,5,6,7,8,9,10,11,12'  // Ensure month is between 1 and 12
    ]);

    // Step 2: Handle validation failure
    if ($validateRequest->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation fails',
            'errors' => $validateRequest->errors()
        ], 403);
    }

    // Step 3: Get the currently logged-in admin's 'admin_id'
    // $user = auth()->user(); // Get the authenticated user
    $adminId = $request->admin_id; 
    // dd($adminId);// Get the admin_id of the logged-in admin

    // Step 4: Ensure that only data related to this admin is fetched
    $orders = OrderMaster::where('admin_id', $adminId); // Filter by the logged-in admin's ID
    $payment = Payment::where('admin_id', $adminId);    // Filter payments by the same admin_id
    $orderDetails = OrderDetails::where('admin_id', $adminId); // Same admin_id for order details

    // Step 5: Apply the duration-based filtering for day, week, or month
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

    // Step 6: Calculate the total orders, income, and delivery counts
    $totalOrdersCount = $orders->count(); // Total number of orders
    $totalDays = 0; // Default number of days for calculations

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

    // Step 7: Calculate sale, returns, and the average
    $sale = $payment->sum('amount');       // Total sales
    $returns = $payment->sum('return');    // Total returns
    $totalAverage = ($sale - $returns) / $totalDays; // Average daily income

    // Step 8: Prepare the statistical data for response
    $statisticalData = [
        "admin_id" => $adminId,  // Include the admin_id in the response
        "total_orders_count" => $orders->count(),
        "total_orders" => $orders->get(),
        "total_payments" => $payment->get(),
        "total_income" => $sale - $returns,
        "delivery_orders_count" => $orders->where('status', 'delivered')->count(),
        "delivery_orders" => $orders->where('status', 'delivered')->get(),
        "total_average" => $totalAverage,
        "total_days" => $totalDays
    ];
    // dd($statisticalData);

    // Step 9: Return the statistical data as a JSON response
    return response()->json(['statistical_data' => $statisticalData], 200);
}

    

    //  public function getPaymentMethods(Request $request)
    // {
    //     // Validate the request inputs
    //     $validateRequest = Validator::make($request->all(), [
    //         'duration' => 'in:day,week,month',
    //         'day' => 'date', // Validate day as a date
    //         'month' => 'in:1,2,3,4,5,6,7,8,9,10,11,12'
    //     ]);

    //     // Return an error response if validation fails
    //     if ($validateRequest->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation fails',
    //             'errors' => $validateRequest->errors()
    //         ], 403);
    //     }
    //     $adminId = $request->admin_id; 
    //     // Initialize the query for orders
    //     $orders = OrderMaster::query();


    //     // Filter by month, day, or week based on the 'duration' input
    //     if ($request->input('duration') == "month") {
    //         $orders->whereMonth('created_at', $request->input('month'));
    //     } elseif ($request->input('duration') == "day") {
    //         $orders->whereDate('created_at', $request->input('day'));
    //     } elseif ($request->input('duration') == "week") {
    //         $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
    //         $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');
    //         $orders->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
    //     }

    //     // Count orders by payment type
    //       $paymentMethods = [
    //             'cash' => $orders->clone()->where('payment_type', 'cash')->count(),
    //             'debit' => $orders->clone()->where('payment_type', 'debit')->count(),
    //             'credit' => $orders->clone()->where('payment_type', 'credit')->count(),
    //             'transfer' => $orders->clone()->where('payment_type', 'transfer')->count()
    //         ];

    //     // Return the payment methods data as a JSON response
    //     return response()->json(['payment_methods' => $paymentMethods], 200);
    // }


    public function getPaymentMethods(Request $request)
{
    // Validate the request inputs
    $validateRequest = Validator::make($request->all(), [
        'duration' => 'in:day,week,month',
        'day' => 'date', // Validate day as a date
        'month' => 'in:1,2,3,4,5,6,7,8,9,10,11,12',
        // 'admin_id' => 'required|integer' // Ensure admin_id is present and valid
    ]);

    // Return an error response if validation fails
    if ($validateRequest->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validateRequest->errors()
        ], 403);
    }

        // Get the admin ID
    $adminId = $request->admin_id;
    // dd($adminId);

    // Initialize the query for orders, filter by admin_id
    $orders = OrderMaster::query()->where('admin_id', $adminId);
    // dd($orders);

    // Filter by month, day, or week based on the 'duration' input
    if ($request->input('duration') == "month") {
        $orders->whereMonth('created_at', $request->input('month'));
    } elseif ($request->input('duration') == "day") {
        $orders->whereDate('created_at', $request->input('day'));
    } elseif ($request->input('duration') == "week") {
        $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
        $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');
        $orders->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
    }

    // Count orders by payment type
    $paymentMethods = [
        'admin_id' => $adminId,
        'cash' => (clone $orders)->where('payment_type', 'cash')->count(),
        'debit' => (clone $orders)->where('payment_type', 'debit')->count(),
        'credit' => (clone $orders)->where('payment_type', 'credit')->count(),
        'transfer' => (clone $orders)->where('payment_type', 'transfer')->count()
    ];

    // Return the payment methods data as a JSON response
    return response()->json(['payment_methods' => $paymentMethods], 200);
}

    
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
    
    $adminId = $request->admin_id;
    $orderDetails = OrderDetails::query()->where('admin_id', $adminId);

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
        'admin_id' => $adminId,
        'total_revenue' => $data
        
    ], 200);


}
public function getStatusSummary(Request $request)
{
    // Validate the request inputs
    $validateRequest = Validator::make($request->all(), [
        'duration' => 'in:day,week,month',
        'day' => 'date', // Validate the day as a date
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

    // Retrieve the admin ID
    $adminId = $request->admin_id;
    // dd($adminId);
    // Initialize the query for orders and filter by admin_id
    $ordersQuery = OrderMaster::query()->where('admin_id', $adminId);

    // Apply the appropriate filters based on 'duration'
    if ($request->input('duration') == "month") {
        $ordersQuery->whereMonth('created_at', $request->input('month'));
    } elseif ($request->input('duration') == "day") {
        $ordersQuery->whereDate('created_at', $request->input('day'));
    } elseif ($request->input('duration') == "week") {
        $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
        $endOfWeek = Carbon::now()->endOfWeek()->format('Y-m-d');
        $ordersQuery->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
    }

    // Count the orders by status
    $statusSummary = [
        'received' => (clone $ordersQuery)->where('status', 'received')->count(),
        'prepared' => (clone $ordersQuery)->where('status', 'prepared')->count(),
        'delivered' => (clone $ordersQuery)->where('status', 'delivered')->count(),
        'finalized' => (clone $ordersQuery)->where('status', 'finalized')->count()
    ];

    // Return the status summary as a JSON response
    return response()->json(['statusSummary' => $statusSummary], 200);
}

    
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

    // Get the admin_id from the request
    $adminId = $request->admin_id;

    // Check if admin_id exists in the OrderDetails table
    $adminExists = OrderDetails::where('admin_id', $adminId)->exists();
    if (!$adminExists) {
        return response()->json([
            'success' => false,
            'message' => 'Admin ID not found or has no orders',
        ], 404);
    }

    // Query to get the popular products for the given admin_id
    $orderDetailsQuery = OrderDetails::select(
        'items.name', 
        'items.image', 
        DB::raw('SUM(order_details.amount) as total_amount'), // Sum the amounts for each product
        DB::raw('COUNT(order_details.item_id) as order_count')
    )
    ->join('items', 'order_details.item_id', '=', 'items.id')
    ->where('order_details.admin_id', $adminId) // Filter by admin_id
    ->groupBy('order_details.item_id', 'items.name', 'items.image')
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

    // Fetch the most ordered items
    $mostOrderedItems = $orderDetailsQuery->get();

    // Return the response with popular products and admin_id
    return response()->json([
        'admin_id' => $adminId,
        'popular_products' => $mostOrderedItems
    ], 200);
}

public function getBoxEntry(Request $request)
{
    // Validate the input
    $validateRequest = Validator::make($request->all(), [
        // 'admin_id' => 'required|integer', // Ensure admin_id is present and valid
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

    $adminId = $request->input('admin_id'); // Get admin_id from the request
    $boxEntry = [];

    // Get all boxes
    $boxes = Boxs::all();

    // Loop through each box
    foreach ($boxes as $box) {
        // Initialize the query for BoxLogs for the current box and filter by admin_id
        $logs = BoxLogs::where('box_id', $box->id)
            ->where('admin_id', $adminId); // Filter by admin_id

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

        // Check if there are logs for this box before adding them to the result
        if ($filteredLogs->isNotEmpty()) {
            // Calculate collect_amount for each log
            foreach ($filteredLogs as $log) {
                $log->collect_amount = $log->close_amount - $log->open_amount;
            }

            // Add the box and its filtered logs to the boxEntry array
            $boxEntry[] = [
                'box_id' => $box->id,
                'box_name' => $box->name,
                'logs' => $filteredLogs
            ];
        }
    }

    // Return the box entries with their logs
    return response()->json([
        'admin_id' => $adminId, 
        'box_entries' => $boxEntry
    ], 200);
}

public function cancelOrders(Request $request)
{
    // Validate the input
    $validator = Validator::make($request->all(), [
        // 'admin_id' => 'required|integer', // Ensure admin_id is present
        'duration' => 'in:day,week,month',
        'day' => 'date',
        'month' => 'in:1,2,3,4,5,6,7,8,9,10,11,12'
    ]);

    // If validation fails, return an error response
   if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 403);
    }
    // Get the admin_id from the request
    $adminId = $request->input('admin_id');
    // Query to get cancelled orders for the specific admin_id
    $orders = OrderMaster::where('status', 'cancelled')
    ->where('admin_id', $adminId); // Filter by admin_id
    // dd($orders);

    // Apply filters based on the 'duration'
    if ($request->input('duration') == "month") {
        $orders->whereMonth('created_at', $request->input('month'));
    } elseif ($request->input('duration') == "week") {
        $orders->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    } elseif ($request->input('duration') == "day") {
        $orders->whereDate('created_at', $request->input('day'));
    }

    // Get the filtered cancelled orders
    $cancelledOrders = $orders->get();

    // Return the response with the cancelled orders
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
    public function updateUserStatus(Request $request, $id)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:Activa,Suspender', // Define valid statuses
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'error' => $validator->errors()
            ], 401);
        }

        $user = User::find($id);
        if (is_null($user)) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Update the user's status
        $user->status = $request->status;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully',
            'user' => $user,
        ], 200);
    }
}
