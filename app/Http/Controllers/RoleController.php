<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;

class RoleController extends Controller
{
    public function getRole()
    {
        $roles = Role::all()->select('id', 'name');
        return response()->json($roles, 200);
    }

    public function registerUser(Request $request)
    {
        return response()->json($request, 200);
    }
}
