<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Item_Menu_Join;
use App\Models\Menu;
use App\Models\OrderDetails;
use App\Models\OrderMaster;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Str;
use App\Models\Notification;
use App\Events\NotificationMessage;

class ItemController extends Controller
{
    public function createItem(Request $request)
    {
        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if ($role != "admin" &&  $role != "cashier" && $role != "waitress" && $role != "kitchen") {
            $errorMessage = 'No se pudo eliminar el artículo. Verifica si el artículo está asociado a otros registros e intenta nuevamente.';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            Notification::create([
                'user_id' => auth()->user()->id,
                'notification_type' => 'notification',
                'notification' => $errorMessage,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorised',
                'notification' => $errorMessage
            ], 405);
        }

        $validateRequest = Validator::make($request->all(), [
            'name' => 'required|string|max:255',

            'production_center_id' => 'required|exists:production_centers,id',
            'cost_price' => 'required|numeric|min:1',
            'sale_price' => 'required|numeric|min:1',
            'family_id' => 'required|exists:families,id',
            'sub_family_id' => 'required|exists:subfamilies,id',
            'image' => 'file|required|between:1,2048|mimes:jpg,png,jpeg,webp'
        ]);

        if ($validateRequest->fails()) {
            if ($role != "admin" &&  $role != "cashier") {
                $errorMessage = 'No se pudo crear el artículo. Verifica la información ingresada e intenta nuevamente.';
                broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
                Notification::create([
                    'user_id' => auth()->user()->id,
                    'notification_type' => 'alert',
                    'notification' => $errorMessage,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Validation fails',
                    'errors' => $validateRequest->errors(),
                    'alert' => $errorMessage,
                ], 403);
            }
        }

        $admin_id = null;
        if ($role == 'admin') {
            // If the user is an admin, store their own ID
            $admin_id = auth()->user()->id;
        } elseif ($role == 'cashier') {
            $admin_id = auth()->user()->admin_id;
        }

        $filename = '';
        if (!$request->hasFile('image')) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => [
                    'image' => [
                        'image is required as file'
                    ]
                ]
            ], 403);
        }
        $image = $request->file('image');
        $filename = time() . '.' . $image->getClientOriginalExtension();
        $image->move(public_path('images'), $filename);

        $code = $this->generateUniqueCode();

        $item = Item::create([
            "name" => $request->name,
            "code" => $code,
            "production_center_id" => $request->production_center_id,
            "cost_price" => $request->cost_price,
            "sale_price" => $request->sale_price,
            "family_id" => $request->family_id,
            "sub_family_id" => $request->sub_family_id,
            "description" => $request->description,
            "image" => $filename,
            'admin_id' => $admin_id
        ]);

        $successMessage = "El artículo {$item->name} ha sido creado exitosamente.";
        broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
        Notification::create([
            'user_id' => auth()->user()->id,
            'notification_type' => 'notification',
            'notification' => $successMessage,
        ]);


        return response()->json([
            'success' => true,
            'message' => 'Item added successfully',
            'item' => $item,
            'notification' => $successMessage
        ]);
    }

    private function generateUniqueCode()
    {
        do {
            $code = mt_rand(10000000, 99999999); // Generates an 8-digit number
        } while (Item::where('code', $code)->exists()); // Ensure it's unique

        return $code;
    }


    public function updateItem(Request $request, $id)
    {
        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if ($role != "admin" &&  $role != "cashier" && $role != "waitress" && $role != "kitchen") {

            $errorMessage = 'No se pudo eliminar el artículo. Verifica si el artículo está asociado a otros registros e intenta nuevamente.';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            Notification::create([
                'user_id' => auth()->user()->id,
                'notification_type' => 'notification',
                'notification' => $errorMessage,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorised',
                'notification' => $errorMessage
            ], 405);
        }

        $validateRequest = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'production_center_id' => 'required|exists:production_centers,id',
            'cost_price' => 'required|numeric|min:1',
            'sale_price' => 'required|numeric|min:1',
            'family_id' => 'required|exists:families,id',
            'sub_family_id' => 'required|exists:subfamilies,id',
            'image' => 'file|between:1,2048|mimes:jpg,png,jpeg,webp'
        ]);

        if ($validateRequest->fails()) {
            if ($role != "admin" &&  $role != "cashier") {
                $errorMessage = 'No se pudo actualizar el artículo. Verifica la información ingresada e intenta nuevamente.';
                broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
                Notification::create([
                    'user_id' => auth()->user()->id,
                    'notification_type' => 'notification',
                    'notification' => $errorMessage,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Validation fails',
                    'errors' => $validateRequest->errors(),
                    'alert' => $errorMessage
                ], 403);
            }
        }

        $item = Item::find($id);
        if ($item == null) {
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

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images'), $filename);

            $item->image = $filename;
        }
        if ($request->has('description')) {
            $item->description = $request->description;
        }
        $item->save();

        $successMessage = "El artículo {$item->name} ha sido actualizado exitosamente.";
        broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
        Notification::create([
            'user_id' => auth()->user()->id,
            'notification_type' => 'notification',
            'notification' => $successMessage,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully',
            'item' => $item,
            'notification' => $successMessage,
        ], 200);
    }

    public function deleteItem($id)
    {
        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if ($role != "admin" &&  $role != "cashier" && $role != "waitress" && $role != "kitchen") {
            $errorMessage = 'No se pudo eliminar el artículo. Verifica si el artículo está asociado a otros registros e intenta nuevamente.';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            Notification::create([
                'user_id' => auth()->user()->id,
                'notification_type' => 'notification',
                'notification' => $errorMessage,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised',
                'notification' => $errorMessage
            ], 405);
        }
        
        $item = Item::find($id);
        // dd($item);
        if ($item === null) {
            if ($role != "admin" &&  $role != "cashier") {
                $errorMessage = 'No se pudo eliminar el artículo. Verifica si el artículo está asociado a otros registros e intenta nuevamente.';
                broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
                Notification::create([
                    'user_id' => auth()->user()->id,
                    'notification_type' => 'notification',
                    'notification' => $errorMessage,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => "Provided id is not found",
                    'alert' =>  $errorMessage
                ], 403);
            }
        }

        $item->delete();
        
        $successMessage = "El artículo {$item->name} ha sido eliminado del sistema.";
        broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
        Notification::create([
            'user_id' => auth()->user()->id,
            'notification_type' => 'notification',
            'notification' => $successMessage,
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully.',
            'notification' => $successMessage
        ], 200);
    }

    public function getSingleItem($id)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "User not authenticated"
            ], 401);
        }

        $admin_id = $user->admin_id;
        if ($admin_id) {
            $item = Item::where('id', $id)->where('admin_id', $admin_id)->get();
        } else {
            $item = Item::where('admin_id', auth()->user()->id)->get();
        }

        if ($item == null) {
            return response()->json([
                'success' => false,
                'message' => "Item not found or you don't have permission"
            ], 403);
        }

        return response()->json([
            'success' => true,
            'item' => $item
        ], 200);
    }


    public function getAll()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "User not authenticated"
            ], 401);
        }

        $admin_id = $user->admin_id;
        if ($admin_id) {
            $item = Item::where('admin_id', $admin_id)->get();
        } else {
            $item = Item::where('admin_id', auth()->user()->id)->get();
        }

        if ($item == null) {
            return response()->json([
                'success' => false,
                'message' => "Item not found or you don't have permission"
            ], 403);
        }

        return response()->json([
            'success' => true,
            'items' => $item
        ], 200);
    }



    public function getSubFamilyWiseItem(Request $request)
    {
        $validateRequest = Validator::make($request->all(), [
            'subfamilies' => 'array',
            'families' => 'array',
            'subfamilies.*' => 'integer|exists:subfamilies,id',
            'families.*' => 'integer|exists:families,id'
        ]);

        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "User not authenticated"
            ], 401);
        }

        $admin_id = $user->admin_id ?? $user->id; // Use admin_id if exists, otherwise user id

        $query = Item::where('admin_id', $admin_id);

        if ($request->has('subfamilies')) {
            $query->whereIn('sub_family_id', $request->subfamilies);
        }

        if ($request->has('families')) {
            $query->whereIn('family_id', $request->families);
        }

        $items = $query->get();

        return response()->json([
            'success' => true,
            'items' => $items
        ], 200);
    }

    public function addToMenu(Request $request)
    {
        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if ($role != "admin" && $role != "cashier") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 405);
        }
    
        $validateRequest = Validator::make($request->all(), [
            'item_ids' => 'required|array',
            'item_ids.*' => 'integer|exists:items,id',
            'menu_id' => 'required|exists:menus,id'
        ]);
    
        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateRequest->errors()
            ], 403);
        }
    
        // Determine admin ID based on user role
        $admin_id = null;
        if ($role == 'admin') {
            $admin_id = auth()->user()->id;
        } elseif ($role == 'cashier') {
            $admin_id = auth()->user()->admin_id;
        }
    
        $menu = Menu::find($request->menu_id);
        $notification = null; // Variable to hold the last success message
        $errorOccurred = false; // Flag to track if any error occurs
    
        foreach ($request->item_ids as $item_id) {
            $item = Item::find($item_id);
    
            try {
                // Create the relationship entry
                Item_Menu_Join::create([
                    'menu_id' => $menu->id,
                    'item_id' => $item->id,
                    'admin_id' => $admin_id
                ]);
    
                // Prepare the success message
                $notification = "El artículo {$item->name} ha sido agregado exitosamente al menú {$menu->name}.";
    
                // Broadcast the notification message
                broadcast(new NotificationMessage('notification', $notification))->toOthers();
    
                // Save the notification to the database
                Notification::create([
                    'user_id' => Auth::id(), // The user who performed the action
                    'notification_type' => 'notification',
                    'notification' => $notification,
                    'admin_id' => $admin_id,
                    'role_id' => Auth::user()->role_id
                ]);
            } catch (\Exception $e) {
                // Log the error for debugging
                \Log::error("Failed to add item {$item->name} to menu: " . $e->getMessage());
    
                // Set the error flag
                $errorOccurred = true;
                // Prepare the error response message
                break; // Exit the loop on error
            }
        }
    
        // If an error occurred during the process
        if ($errorOccurred) {
            return response()->json([
                'success' => false,
                'notification' => "No se pudo agregar el artículo al menú. Verifica la información ingresada e intenta nuevamente."
            ], 500); // Using 500 for internal server error
        }
    
        return response()->json([
            'success' => true,
            'message' => "Items added to menu successfully",
            'notification' => $notification // Return the last notification message
        ], 200);
    }
    


    

    public function getSaleReport(Request $request, $id)
    {
        $order_ids = [];

        if (Item::find($id) == null) {
            return response()->json([
                'success' => false,
                'message' => "Item id invalid"
            ], 403);
        }

        $details = OrderDetails::where('item_id', $id)->get();
        foreach ($details as $value) {
            if (!in_array($value->order_master_id, $order_ids)) {
                $order_ids[] = $value->order_master_id;
            }
        }

        $responseData = [];
        $ordersQuery = OrderMaster::whereIn('id', $order_ids);

        if ($request->has('from_month') && $request->has('to_month')) {
            $startDate = Carbon::create(null, $request->query('from_month'), 1)->startOfMonth();
            $endDate = Carbon::create(null, $request->query('to_month'), 1)->endOfMonth();
            $ordersQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        $orders = $ordersQuery->get();

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
}

