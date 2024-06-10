<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Item_Menu_Join;
use App\Models\Menu;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    public function createItem(Request $request)
    {
        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 405);
        }

        $validateRequest = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|unique:items,code',
            'production_center_id' => 'required|exists:production_centers,id',
            'cost_price' => 'required|numeric|min:1',
            'sale_price' => 'required|numeric|min:1',
            'family_id' => 'required|exists:families,id',
            'sub_family_id' => 'required|exists:subfamilies,id',
            'photo' => 'file|required|between:10,2048|mimes:jpg,png,jpeg,webp'
        ]);

        if($validateRequest->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        $filename = '';
        if (!$request->hasFile('photo')) 
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => [
                    'photo' => [
                        'photo is required as file'
                    ]
                ]
            ], 403);
        }
        $image = $request->file('photo');
        $filename = time() . '.' . $image->getClientOriginalExtension();
        $image->move(public_path('images'), $filename);

        $item = Item::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Item added successfully',
            'item' => $item
        ]);
    }

    public function updateItem(Request $request, $id)
    {
        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 405);
        }

        $validateRequest = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'production_center_id' => 'required|exists:production_centers,id',
            'cost_price' => 'required|numeric|min:1',
            'sale_price' => 'required|numeric|min:1',
            'family_id' => 'required|exists:families,id',
            'sub_family_id' => 'required|exists:subfamilies,id',
            'photo' => 'file|between:10,2048|mimes:jpg,png,jpeg,webp'
        ]);

        if($validateRequest->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        $item = Item::find($id);
        if($item == null)
        {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect item id'
            ], 403);
        }

        $item->name = $request->name;
        $item->production_center_id = $request->production_center_id;
        $item->cost_price = $request->cost_price;
        $item->sale_price = $request->sale_price;
        $item->family_id = $request->family_id;
        $item->sub_family_id = $request->sub_family_id;

        if ($request->hasFile('photo'))
        {
            $image = $request->file('photo');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images'), $filename);

            $item->image = $filename;
        }
        if(isset($request->description) && !empty($request->description))
        {
            $item->description = $request->description;
        }

        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully',
            'item' => $item
        ], 200);
    }

    public function deleteItem($id)
    {
        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 405);
        }

        $item = Item::find($id);

        if($item == null)
        {
            return response()->json([
                'success' => false,
                'message' => "Provided id is not found"
            ], 403);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully.'
        ], 200);
    }

    public function getSingleItem($id)
    {
        $item = Item::find($id);

        if($item == null)
        {
            return response()->json([
                'success' => false,
                'message' => "Provided id is not found"
            ], 403);
        }

        return response()->json([
            'success' => true,
            'item' => $item
        ], 200);
    }

    public function getAll()
    {
        $items = Item::all();
        return response()->json(['success' => true, 'items' => $items]);
    }

    public function getSubFamilyWiseItem(Request $request)
    {
        $validateRequest = Validator::make($request->all(), [
            'subfamilies' => 'array',
            'subfamilies.*' => 'integer|exists:subfamilies,id' // Ensure each family ID is an integer and exists in the families table
        ]);

        if($validateRequest->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        $items = Item::all()->whereIn('sub_family_id', $request->subfamilies);
        return response()->json([
            'success' => true,
            'items' => $items
        ], 200);
    }

    public function addToMenu(Request $request)
    {
        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if($role != "admin")
        {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 405);
        }

        $validateRequest = Validator::make($request->all(), [
            'item_ids' => 'required|array',
            'item_ids.*' => 'integer|exists:items,id',
            'menu_id' => 'required|exists:menus,id'
        ]);

        if($validateRequest->fails())
        {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateRequest->errors()
            ], 403);
        }
        $menu = Menu::find($request->menu_id);
        for($i = 0; $i < count($request->item_ids); $i++)
        {
            $item = Item::find($request->item_ids[$i]);

            Item_Menu_Join::create([
                'menu_id' => $menu->id,
                'item_id' => $item->id
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Items added to menu successfully"
        ], 200);
    }
}
