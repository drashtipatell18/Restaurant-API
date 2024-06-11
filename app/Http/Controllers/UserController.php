<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return response()->json($users, 200);
    }

    public function storeUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role_id' => 'nullable|exists:roles,id',
            'password' => 'required|string|min:8|same:confirm_password',
            'confirm_password' => 'required|string|min:8|same:password',
            'image' => 'nullable|file|mimes:jpg,png,jpeg,gif|max:2048',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'error' => $validator->errors()
            ], 401);
        }        
    
        $filename = '';
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images'), $filename); // Ensure the 'images' directory exists and is writable
        }
    
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'password' => Hash::make($request->password),
            'image' => $filename,
        ]);
    
        return response()->json([
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
    
    public function getUser($id){
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

}


