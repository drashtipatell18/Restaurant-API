<?php

namespace App\Http\Controllers;

use App\Events\NotificationMessage;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Menu;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class MenuController extends Controller
{
    
    public function createMenu(Request $request)
    {
        try {
            // Step 1: Role Validation
            $role = Role::where('id', Auth()->user()->role_id)->first()->name;
            if (!in_array($role, ["admin", "cashier", "waitress", "kitchen"])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
    
            // Step 2: Validate Menu Input
            $validateMenu = Validator::make($request->all(), [
                'name' => 'required|string|max:255'
            ]);
            
        $admin_id = ($role == 'admin') ? auth()->user()->id : auth()->user()->admin_id;
        $users = User::where('admin_id', $admin_id)->orWhere('id', $admin_id)->get();
        // dd($users);
        $usersRoles = User::where('admin_id', $admin_id)
        ->whereIn('role_id', [1, 2])
        ->orWhere('id', $admin_id)
        ->get();
            if ($validateMenu->fails()) {
                // ** Create error alert and save notification **
                if ($role != "admin" &&  $role != "cashier") {
                $errorMessage = 'No se pudo crear el menú. Verifica la información ingresada e intenta nuevamente.';
    
                broadcast(new NotificationMessage('alert', $errorMessage))->toOthers();
                foreach ($usersRoles as $recipient) {
                Notification::create([
                    'user_id' => $recipient->id,
                    'notification_type' => 'alert',
                    'notification' => $errorMessage,
                    'admin_id' => $admin_id,
                    'role_id' => $recipient->role_id,
                    'path'=> '/digitalmenu'
                ]);
            }
    
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validateMenu->errors(),
                    'alert' => $errorMessage,
                ], 401);
            }
            }
    
            // Step 3: Get Admin ID based on Role
            $admin_id = null;
            if ($role == 'admin') {
                $admin_id = auth()->user()->id;
            } elseif (in_array($role, ['cashier', 'waitress', 'kitchen'])) {
                $admin_id = auth()->user()->admin_id;
            }
    
            // Step 4: Create Menu
            $menu = Menu::create([
                'name' => $request->input('name'),
                'admin_id' => $admin_id
            ]);
    
            // Step 5: Success Notification
            $menuName = $menu->name;
            $successMessage = " El menú '{$menuName}' ha sido creado exitosamente.";

            // ** Broadcast the success notification **
            broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
    
            // ** Save the success notification to the database **
            foreach($users as $recipient){

                Notification::create([
                    'user_id' => $recipient->id,
                    'notification_type' => 'notification',
                    'notification' => $successMessage,
                    'admin_id' => $admin_id,
                    'role_id' =>$recipient->role_id,
                    'path'=> '/digitalmenu'
                ]);
            }
    
            // Step 6: Return Success Response
            return response()->json([
                'success' => true,
                'message' => 'Menu added successfully.',
                'admin_id' => $admin_id,
                'notification' => $successMessage
            ], 200);
    
        } catch (\Exception $e) {
            // Step 7: Handle any connection or server errors
            $errorMessage = 'No se pudo crear el menú. Verifica la información ingresada e intenta nuevamente.';
    
            // ** Broadcast and save the error notification **
            broadcast(new NotificationMessage('alert', $errorMessage))->toOthers();
            foreach ($usersRoles as $recipient) {
                Notification::create([
                    'user_id' => $recipient->id,
                    'notification_type' => 'alert',
                    'notification' => $errorMessage,
                    'admin_id' => $admin_id,
                    'role_id' => $recipient->role_id,
                    'path'=> '/digitalmenu'
                ]);
            }
    
            return response()->json([
                'success' => false,
                'message' => $errorMessage
            ], 500);
        }
    }
    

    public function updateMenu(Request $request, $id)
    {
        // dd('ishu');
        // Step 1: Role Validation
        $role = Role::where('id', Auth()->user()->role_id)->first()->name;
        if ($role != "admin" && $role != "cashier" && $role != "waitress" &&  $role != "kitchen") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }
    
        // Step 2: Validate Input
        $validateMenu = Validator::make($request->all(), [
            'name' => 'required|string|max:255'
        ]);
        $admin_id = ($role == 'admin') ? auth()->user()->id : auth()->user()->admin_id;
        $users = User::where('admin_id', $admin_id)->orWhere('id', $admin_id)->get();
        // dd($users);
        $usersRoles = User::where('admin_id', $admin_id)
        ->whereIn('role_id', [1, 2])
        ->orWhere('id', $admin_id)
        ->get();
    
        if ($validateMenu->fails()) {
            if (  $role != "cashier") {
            $errorMessage = 'No se pudo actualizar el menú. Verifica la información ingresada e intenta nuevamente.';
    
            // ** Broadcast the error notification **
            broadcast(new NotificationMessage('alert', $errorMessage))->toOthers();
    
            // ** Save the error notification to the database **
            foreach ($usersRoles as $recipient) {
            Notification::create([
              'user_id' => $recipient->id,
                    'notification_type' => 'alert',
                    'notification' => $errorMessage,
                    'admin_id' => $admin_id,
                    'role_id' => $recipient->role_id,
                    'path'=> '/digitalmenu'
            ]);
        }
        }
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateMenu->errors(),
                'alert' => $errorMessage
            ], 401);
        }
    
        // Step 3: Find and Update Menu
        $menu = Menu::find($id);
        if (!$menu) {
            $errorMessage = 'No se pudo encontrar el menú para actualizar.';
    
            // ** Broadcast the error notification **
            broadcast(new NotificationMessage('alert', $errorMessage))->toOthers();
    
            // ** Save the error notification to the database **

            // Notification::create([
            //     'user_id' => auth()->user()->id,
            //     'notification_type' => 'alert',
            //     'notification' => $errorMessage,
            //     'admin_id' => auth()->user()->id,
            //     'role_id' => auth()->user()->role_id
            // ]);
            foreach ($usersRoles as $recipient) {
                Notification::create([
                    'user_id' => $recipient->id,
                    'notification_type' => 'alert',
                    'notification' => $errorMessage,
                    'admin_id' => $admin_id,
                    'role_id' => $recipient->role_id,
                    'path'=> '/digitalmenu'
                ]);
            }
    
            return response()->json([
                'success' => false,
                'message' => $errorMessage,  // Corrected this to return the errorMessage
            ], 404);
        }
    
        $menu->update([
            'name' => $request->input('name')
        ]);
    
        // Step 4: Prepare and Send Success Notification
        $menuName = $menu->name;
        $successMessage = "El menú '{$menuName}' ha sido actualizado exitosamente.";
    
        // ** Broadcast the success notification **
        broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
    
        // ** Save the success notification to the database **
        foreach ($users as $recipient) {
            Notification::create([
                'user_id' => $recipient->id,
                'notification_type' => 'notification',
                'notification' => $successMessage,
                'admin_id' => $admin_id,
                'role_id' => $recipient->role_id,
                'path'=> '/digitalmenu'
            ]);
        }
        // Notification::create([
        //     'user_id' => auth()->user()->id,
        //     'notification_type' => 'notification',
        //     'notification' => $successMessage,
        //     'admin_id' => auth()->user()->id,
        //     'role_id' => auth()->user()->role_id
        // ]);
    
        // Step 5: Return Success Response
        return response()->json([
            'success' => true,
            'message' => 'Menu updated successfully.',
            'notification' => $successMessage
        ], 200);
    }
    

    public function deleteMenu($id)
    {
        $role = Role::where('id', Auth()->user()->role_id)->first()->name;
        if ($role != "admin" && $role != "cashier" && $role != "waitress" &&  $role != "kitchen") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }
    
        // Step 1: Find the Menu
        $menu = Menu::find($id);
                
        $admin_id = null;
        if ($role == 'admin') {
            $admin_id = auth()->user()->id;
        } elseif (in_array($role, ['cashier', 'waitress', 'kitchen'])) {
            $admin_id = auth()->user()->admin_id;
        }
        $users = User::where('admin_id', $admin_id)->orWhere('id', $admin_id)->get();
        // dd($users);
        $usersRoles = User::where('admin_id', $admin_id)
        ->whereIn('role_id', [1, 2])
        ->orWhere('id', $admin_id)
        ->get();
        if (is_null($menu)) {
            if ($role != "admin" &&  $role != "cashier") {
            $errorMessage = 'No se pudo eliminar el menú. Verifica si el menú está asociado a otros registros e intenta nuevamente.';
    
            // ** Broadcast the error notification **
            broadcast(new NotificationMessage('alert', $errorMessage))->toOthers();
    
            // ** Save the error notification to the database **
            // Notification::create([
            //     'user_id' => auth()->user()->id,
            //     'notification_type' => 'alert',
            //     'notification' => $errorMessage,
            //     'admin_id' => auth()->user()->id,
            //     'role_id' => auth()->user()->role_id
            // ]);
            foreach ($usersRoles as $recipient) {
                Notification::create([
                    'user_id' => $recipient->id,
                    'notification_type' => 'alert',
                    'notification' => $errorMessage,
                    'admin_id' => $admin_id,
                    'role_id' => $recipient->role_id,
                     'path'=> '/digitalmenu'
                ]);
            }
            return response()->json([
                'message' => 'Menu not found',
                'alert' => $errorMessage
            ], 404);
        }
        }
    
        // Step 2: Try to Delete the Menu

        try {
            $menuName = $menu->name;  // Capture menu name before deletion
            $menu->delete();
    
            $successMessage = "El menú '{$menuName}' ha sido eliminado del sistema.";
    
            // ** Broadcast the success notification **
            broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
    
            // ** Save the success notification to the database **
            // Notification::create([
            //     'user_id' => auth()->user()->id,
            //     'notification_type' => 'notification',
            //     'notification' => $successMessage,
            //     'admin_id' => auth()->user()->id,
            //     'role_id' => auth()->user()->role_id
            // ]);
            foreach($users as $recipient){
                Notification::create([
                    'user_id' => $recipient->id,
                    'notification_type' => 'notification',
                    'notification' => $successMessage,
                    'admin_id' => $admin_id,
                    'role_id' =>$recipient->role_id,
                     'path'=> '/digitalmenu'
                ]);
            }
    
            return response()->json([
                'message' => 'Menu deleted successfully',
                'notification' => $successMessage
            ], 200);
    
        } catch (\Exception $e) {
            // Handle exceptions like foreign key constraints or other integrity issues
            $errorMessage = 'No se pudo eliminar el menú. Verifica si el menú está asociado a otros registros e intenta nuevamente.';

            // ** Broadcast the error notification **
            broadcast(new NotificationMessage('alert', $errorMessage))->toOthers();
    
            // ** Save the error notification to the database **
            // Notification::create([
            //     'user_id' => auth()->user()->id,
            //     'notification_type' => 'alert',
            //     'notification' => $errorMessage,
            //     'admin_id' => auth()->user()->id,
            //     'role_id' => auth()->user()->role_id
            // ]);
            foreach ($usersRoles as $recipient) {
                Notification::create([
                    'user_id' => $recipient->id,
                    'notification_type' => 'alert',
                    'notification' => $errorMessage,
                    'admin_id' => $admin_id,
                    'role_id' => $recipient->role_id,
                     'path'=> '/digitalmenu'
                ]);
            }
    
            return response()->json([
                'message' => 'Menu deletion failed',
                'alert' => $errorMessage
            ], 500);
        }
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
                ->where('menus.admin_id', $admin_id)// Filter by the authenticated admin's ID
                ->whereNull('menus.deleted_at')
                ->distinct()  // Ensure unique menu records
                ->get();
                dd($menus);
        } else {
            // Prepare the query for fetching all menus for the admin
            $menus = DB::table(table: 'menus')
                ->where('admin_id', $admin_id)  // Filter by the authenticated admin's ID
                    ->whereNull('deleted_at')  // Only get records where deleted_at is null
                ->get();
        }
    
        // Process each menu to get associated items
        foreach ($menus as $menu) {
            $items = DB::table('item__menu__joins')
                ->leftJoin('items', 'item__menu__joins.item_id', '=', 'items.id')
                ->select('items.*')
                ->where('item__menu__joins.menu_id', $menu->id)
                 ->whereNull('items.deleted_at')
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
        $role = Role::where('id', Auth()->user()->role_id)->first()->name;
        if ($role != "admin" && $role != "cashier" && $role != "waitress" &&  $role != "kitchen") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }
        
        $menu = Menu::findOrFail($menuId);
        $item = $menu->items()->find($itemId);
        $admin_id = null;
        if ($role == 'admin') {
            $admin_id = auth()->user()->id;
        } elseif (in_array($role, ['cashier', 'waitress', 'kitchen'])) {
            $admin_id = auth()->user()->admin_id;
        }
        $users = User::where('admin_id', $admin_id)->orWhere('id', $admin_id)->get();
        // dd($users);
        $usersRoles = User::where('admin_id', $admin_id)
        ->whereIn('role_id', [1, 2])
        ->orWhere('id', $admin_id)
        ->get();
        if (is_null($menu)) {
            if ($role != "admin" &&  $role != "cashier") {
            $errorMessage = 'No se pudo eliminar el artículo del menú. Verifica la información e intenta nuevamente';
    
            // ** Broadcast the error notification **
            broadcast(new NotificationMessage('alert', $errorMessage))->toOthers();
    
            // ** Save the error notification to the database **
            // Notification::create([
            //     'user_id' => auth()->user()->id,
            //     'notification_type' => 'alert',
            //     'notification' => $errorMessage,
            //     'admin_id' => auth()->user()->id,
            //     'role_id' => auth()->user()->role_id
            // ]);
            foreach ($usersRoles as $recipient) {
                Notification::create([
                    'user_id' => $recipient->id,
                    'notification_type' => 'alert',
                    'notification' => $errorMessage,
                    'admin_id' => $admin_id,
                    'role_id' => $recipient->role_id,
                    'path'=>'/digitalmenu'
                ]);
            }
            return response()->json([
                'message' => 'Menu not found',
                'alert' => $errorMessage
            ], 404);
        }
        }

        if ($item) {
            $menu->items()->detach($itemId);
            $successMessage = "El artículo {$item->name} ha sido eliminado exitosamente del menú {$menu->name}";
    
            // ** Broadcast the success notification **
            broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
    
            // ** Save the success notification to the database **
            // Notification::create([
            //     'user_id' => auth()->user()->id,
            //     'notification_type' => 'notification',
            //     'notification' => $successMessage,
            //     'admin_id' => auth()->user()->id,
            //     'role_id' => auth()->user()->role_id
            // ]);
            foreach($users as $recipient){
                Notification::create([
                    'user_id' => $recipient->id,
                    'notification_type' => 'notification',
                    'notification' => $successMessage,
                    'admin_id' => $admin_id,
                    'role_id' =>$recipient->role_id,
                     'path'=>'/digitalmenu'
                ]);
            }
            return response()->json(['message' => 'Menu item removed successfully', 'notification' => $successMessage], 200);
        }

        return response()->json(['message' => 'Item not found in the menu'], 404);
    }

    // public function deleteItem($menuId, $itemId)
    // {
    //     $menu = Menu::findOrFail($menuId);
    //     $item = $menu->items()->find($itemId);

    //     if ($item) {
    //         $menu->items()->detach($itemId);
    //         return response()->json(['message' => 'Menu item removed successfully'], 200);
    //     }

    //     return response()->json(['message' => 'Item not found in the menu'], 404);
    // }

}
