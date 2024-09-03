<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    public function getRole()
    {
        $role = Role::where('id', Auth::user()->role_id)->first()->name;
       if($role != "admin" &&  $role != "cashier")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 405);
        }
        $roles = Role::all()->select('id', 'name');
        return response()->json($roles, 200);
    }

    public function registerUser(Request $request)
    {
        return response()->json($request, 200);
    }
}
