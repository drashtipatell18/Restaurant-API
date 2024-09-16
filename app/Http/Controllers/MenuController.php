<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Menu;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class MenuController extends Controller
{
    public function createMenu(Request $request)
    {
        $role = Role::where('id',Auth()->user()->role_id)->first()->name;
        if($role != "admin" &&  $role != "cashier")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }
        $validateFamily = Validator::make($request->all(), [
            'name' => 'required|string|max:255'
        ]);

        if($validateFamily->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateFamily->errors()
            ], 401);
        }

        $admin_id = null;
        if ($role == 'admin') {
            // If the user is an admin, store their own ID
            $admin_id = auth()->user()->id;
        } elseif ($role == 'cashier') {
           $admin_id = auth()->user()->admin_id;
        }



        Menu::create([
            'name' => $request->input('name'),
            'admin_id' => $admin_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Menu added successfully.',
            'admin_id' => $admin_id
        ], 200);
    }

    public function updateMenu(Request $request,$id)
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
            'name' => 'required|string|max:255'
        ]);

        if($validateFamily->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateFamily->errors()
            ], 401);
        }

        $menu = Menu::find($id);
        $menu->update([
            'name' => $request->input('name')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Menu updated successfully.'
        ], 200);
    }

    public function deleteMenu($id)
    {
        $menu = Menu::find($id);
        if (is_null($menu)) {
            return response()->json(['message' => 'Menu not found'], 404);
        }

        $menu->delete();

        return response()->json(['message' => 'Menu deleted successfully'], 200);
    }

    // public function getMenu(Request $request)
    // {
    //     // Validate the request
    //     $validateRequest = Validator::make($request->all(), [
    //         'menu_ids' => 'array',
    //         'menu_ids.*' => 'integer|exists:menus,id' // Ensure each menu ID is an integer and exists in the menus table
    //     ]);
    
    //     if ($validateRequest->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation error',
    //             'errors' => $validateRequest->errors()
    //         ], 403);
    //     }
    
    //     $adminId = Auth::user()->admin_id;
    
    //     // Initialize menus
    //     $menus = [];
    //     $returnData = []; // Initialize return data array
    
    //     if (isset($request->menu_ids) && is_array($request->menu_ids)) {
    //         // Prepare the query to get menus based on menu_ids and admin_id
    //         $query = DB::table('item__menu__joins')
    //             ->leftJoin('menus', 'item__menu__joins.menu_id', '=', 'menus.id')
    //             ->select('menus.*')
    //             ->whereIn('item__menu__joins.menu_id', $request->menu_ids)  // Filter by menu_ids
    //             ->where('menus.admin_id', $adminId);  // Filter by the authenticated admin's ID
    
    //         // Uncomment when debugging is complete
    //         // $menus = $query->get();
    //     } else {
    //         // Prepare the query for fetching all menus for the admin
    //         $query = DB::table('menus')
    //             ->where('admin_id', $adminId);  // Filter by the authenticated admin's ID
    
    //         // Uncomment when debugging is complete
    //         // $menus = $query->get();
    //     }
    
    //     // Fetch results (Uncomment when debugging is complete)
    //     $menus = $query->get();
    
    //     // Process each menu to get associated items
    //     foreach ($menus as $menu) {
    //         $items = DB::table('item__menu__joins')
    //             ->leftJoin('items', 'item__menu__joins.item_id', '=', 'items.id')
    //             ->select('items.*')
    //             ->where('item__menu__joins.menu_id', $menu->id)
    //             ->get();
            
    //         $returnData[] = [
    //             'id' => $menu->id,
    //             'name' => $menu->name,
    //             'items' => $items
    //         ];
    //     }
    
    //     return response()->json([
    //         'success' => true,
    //         'menus' => $returnData
    //     ], 200);
    // }
    
    public function getMenu(Request $request)
    {
        // Validate the request
        $validateRequest = Validator::make($request->all(), [
            'menu_ids' => 'array',
            'menu_ids.*' => 'integer|exists:menus,id' // Ensure each menu ID is an integer and exists in the menus table
        ]);
    
        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateRequest->errors()
            ], 403);
        }
    
        // $adminId = Auth::user()->admin_id;

        $admin_id = $request->admin_id;
        // dd($admin_id);
    
        // Initialize menus
        $returnData = [];
    
        if (isset($request->menu_ids) && is_array($request->menu_ids)) {
            // Prepare the query to get menus based on menu_ids and admin_id
            $menus = DB::table('item__menu__joins')
                ->leftJoin('menus', 'item__menu__joins.menu_id', '=', 'menus.id')
                ->select('menus.*')
                ->whereIn('item__menu__joins.menu_id', $request->menu_ids)  // Filter by menu_ids
                ->where('menus.admin_id', $admin_id)  // Filter by the authenticated admin's ID
                ->distinct()  // Ensure unique menu records
                ->get();
        } else {
            // Prepare the query for fetching all menus for the admin
            $menus = DB::table(table: 'menus')
                ->where('admin_id', $admin_id)  // Filter by the authenticated admin's ID
                ->get();
        }
    
        // Process each menu to get associated items
        foreach ($menus as $menu) {
            $items = DB::table('item__menu__joins')
                ->leftJoin('items', 'item__menu__joins.item_id', '=', 'items.id')
                ->select('items.*')
                ->where('item__menu__joins.menu_id', $menu->id)
                ->get();
            
                
            $returnData[] = [
                'id' => $menu->id,
                'name' => $menu->name,
                'items' => $items
            ];
        }
    
        return response()->json([
            'success' => true,
            'menus' => $returnData
        ], 200);
    }
    

    public function deleteItem($menuId, $itemId)
    {
        $menu = Menu::findOrFail($menuId);
        $item = $menu->items()->find($itemId);

        if ($item) {
            $menu->items()->detach($itemId);
            return response()->json(['message' => 'Menu item removed successfully'], 200);
        }

        return response()->json(['message' => 'Item not found in the menu'], 404);
    }

}
