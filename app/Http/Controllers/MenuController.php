<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Menu;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    public function createMenu(Request $request)
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

        Menu::create([
            'name' => $request->input('name')
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Menu added successfully.'
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

    public function getMenu(Request $request)
    {
        $validateRequest = Validator::make($request->all(), [
            'menu_ids' => 'array',
            'menu_ids.*' => 'integer|exists:menus,id' // Ensure each family ID is an integer and exists in the families table
        ]);

        if($validateRequest->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        $menus = [];
        $returnData = [];

        if(isset($request->menu_ids) && is_array($request->menu_ids))
        {
            $menus = DB::table('item__menu__joins')
                ->leftJoin('menus', 'item__menu__joins.menu_id', '=', 'menus.id')
                ->select('menus.*')
                ->whereIn('menu_id', $request->menu_ids)
                ->get();
        }
        else
        {
            $menus = Menu::all();
        }

        foreach ($menus as $menu) {
            $items = DB::table('item__menu__joins')
                ->leftJoin('items', 'item__menu__joins.item_id', '=', 'items.id')
                ->select('items.*')
                ->where('item__menu__joins.menu_id', $menu->id)
                ->get();

            array_push($returnData, [
                'id' => $menu->id,
                'name' => $menu->name,
                'items' => $items
            ]);
        }

        return response()->json([
            'success' => true,
            'menus' => $returnData
        ], 200);
    }

    public function deleteItem($menuId)
    {
        $menu = Menu::findOrFail($menuId);
        $menu->items()->detach();
        return response()->json(['message' => 'Menu item remove successfully'], 200);
    }
}
