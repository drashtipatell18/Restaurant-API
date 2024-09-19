<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\Role;
use App\Models\Subfamily;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Notification;
use App\Events\NotificationMessage;
use App\Models\User;

use function Laravel\Prompts\select;

class FamilyController extends Controller
{
    // Family
    public function createFamily(Request $request)
    {
        $role = Role::where('id', Auth()->user()->role_id)->first()->name;
        if ($role != "admin" &&  $role != "cashier") {
            $errorMessage = 'No se pudo crear la familia. Verifica la información ingresada e intenta nuevamente.';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            Notification::create([
                'user_id' => auth()->user()->id,
                'notification_type' => 'alert',
                'notification' => $errorMessage,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorised',
                'notification' => $errorMessage
            ], 401);
        }


        $validateFamily = Validator::make($request->all(), [
            'name' => 'required|string|max:255'
        ]);

        if ($validateFamily->fails()) {
            $errorMessage = 'No se pudo crear la familia. Verifica la información ingresada e intenta nuevamente.';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            Notification::create([
                'user_id' => auth()->user()->id,
                'notification_type' => 'alert',
                'notification' => $errorMessage,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateFamily->errors(),
                'notification' => $errorMessage,
            ], 403);
        }

        $admin_id = null;
        if ($role == 'admin') {
            // If the user is an admin, store their own ID
            $admin_id = auth()->user()->id;
        } elseif ($role == 'cashier') {
            // If the user is a cashier, find their admin's ID
            $admin = User::where('id', auth()->user()->id)->first();  // Assuming there's a relation between cashier and admin

            // You may need to adjust this logic to fit how the cashier-admin relationship is stored in your system
            if ($admin && $admin->admin_id) {
                $admin_id = $admin->admin_id;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin not found for the cashier'
                ], 404);
            }
        }


        $family = Family::create([
            'name' => $request->input('name'),
            'admin_id' => $admin_id
        ]);

        $successMessage = "La familia {$family->name} ha sido creada exitosamente.";
        broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
        Notification::create([
            'user_id' => auth()->user()->id,
            'notification_type' => 'notification',
            'notification' => $successMessage,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Family added successfully.',
            'notification' => $successMessage
        ], 200);
    }

    public function getFamily()
    {
        $authUser = auth()->user();
        $user_id = $authUser->id;
        $role = $authUser->role_id;

        if ($role == 1) { // Admin
            $families = Family::select('id', 'name', 'admin_id')
                ->where('admin_id', $authUser->id)
                ->get();
        } else if ($role == 2 || $role == 3 || $role == 4) { // Cashier, Role 3, Role 4
            $userRecord = User::find($user_id);

            if (!$userRecord) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $admin_id = $userRecord->admin_id;

            $families = Family::select('id', 'name', 'admin_id')
                ->where('admin_id', $admin_id)
                ->get();
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($families, 200);
    }



    public function updateFamily(Request $request, $id)
    {
        $role = Role::where('id', Auth()->user()->role_id)->first()->name;
        if ($role != "admin" &&  $role != "cashier") {
            $errorMessage = 'No se pudo crear la familia. Verifica la información ingresada e intenta nuevamente.';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            Notification::create([
                'user_id' => auth()->user()->id,
                'notification_type' => 'alert',
                'notification' => $errorMessage,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorised',
                'notification' => $errorMessage
            ], 401);
        }
        $validateFamily = Validator::make($request->all(), [
            'name' => 'required|string|max:255'
        ]);

        if ($validateFamily->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateFamily->errors()
            ], 403);
        }

        $family = Family::where('id', $id)->first();

        $family->name = $request->name;
        $family->save();

        return response()->json([
            'success' => true,
            'family' => $family
        ], 200);
    }

    public function deleteFamily($id)
    {
        $role = Role::where('id', Auth()->user()->role_id)->first()->name;
        if ($role != "admin" &&  $role != "cashier") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        $family = Family::where('id', $id)->first();
        $family->delete();
        return response()->json([
            'success' => true,
            'message' => "Family Deleted Successfully."
        ], 200);
    }

    // Sub Family 
    public function createSubFamily(Request $request)
    {
        $role = Role::where('id', Auth()->user()->role_id)->first()->name;
        if ($role != "admin" &&  $role != "cashier") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        $validateSubFamily = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'family_id' => 'required|integer|exists:families,id'
        ]);

        if ($validateSubFamily->fails()) {
            $errorMessage = 'No se pudo crear la subfamilia. Verifica la información ingresada e intenta nuevamente.';
            broadcast(new NotificationMessage('notification', $errorMessage))->toOthers();
            Notification::create([
                'user_id' => auth()->user()->id,
                'notification_type' => 'alert',
                'notification' => $errorMessage,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation Fails.',
                'errors' => $validateSubFamily->errors(),
                'alert' => $errorMessage,
            ], 403);
        }

        $admin_id = null;
        if ($role == 'admin') {
            $admin_id = auth()->user()->id;
        } elseif ($role == 'cashier') {
            $admin = User::where('id', auth()->user()->id)->first();

            if ($admin && $admin->admin_id) {
                $admin_id = $admin->admin_id;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin not found for the cashier'
                ], 404);
            }
        }

        $subfamily = Subfamily::create([
            'name' => $request->input('name'),
            'family_id' => $request->input('family_id'),
            'admin_id' => $admin_id
        ]);

        $successMessage = "La subfamilia {$subfamily->name} ha sido creada exitosamente.";
        broadcast(new NotificationMessage('notification', $successMessage))->toOthers();
        Notification::create([
            'user_id' => auth()->user()->id,
            'notification_type' => 'notification',
            'notification' => $successMessage,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sub Family Added Successfully.',
            'notification' => $successMessage,
        ], 200);
    }



    public function getSubFamily()
    {
        $authUser = auth()->user();
        $user_id = $authUser->id;
        $role = $authUser->role_id;

        // Handle unauthorized roles early
        if (!in_array($role, [1, 2, 3, 4])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Determine admin_id based on role
        $admin_id = $role == 1 ? $authUser->id : User::find($user_id)?->admin_id;

        // Check if the admin_id is null for non-admin roles
        if (is_null($admin_id)) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Common query for retrieving subfamilies
        $families = DB::table('subfamilies')
            ->leftJoin('families', 'subfamilies.family_id', '=', 'families.id')
            ->whereNull('subfamilies.deleted_at')
            ->where('subfamilies.admin_id', $admin_id)
            ->select('subfamilies.id', 'subfamilies.name', 'families.name as family_name')
            ->get();

        return response()->json($families, 200);
    }

    public function updateSubFamily(Request $request, $id)
    {
        $role = Role::where('id', Auth()->user()->role_id)->first()->name;
        if ($role != "admin" &&  $role != "cashier") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        $validateSubFamily = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'family_id' => 'required|integer|exists:families,id'
        ]);

        if ($validateSubFamily->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Fails.',
                'errors' => $validateSubFamily->errors()
            ], 403);
        }

        $subfamily = Subfamily::find($id);
        $subfamily->name = $request->input('name');
        $subfamily->family_id = $request->input('family_id');

        $subfamily->save();

        return response()->json([
            'success' => true,
            'message' => 'Sub Family Updated Successfully.'
        ], 200);
    }
    public function deleteSubFamily($id)
    {
        $role = Role::where('id', Auth()->user()->role_id)->first()->name;
        if ($role != "admin" &&  $role != "cashier") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 401);
        }

        $family = Subfamily::where('id', $id)->first();
        $family->delete();
        return response()->json([
            'success' => true,
            'message' => "Sub Family Deleted Successfully."
        ], 200);
    }
    public function getMultipleSubFamily(Request $request)
    {
        $validateRequest = Validator::make($request->all(), [
            'families' => 'array',
            'families.*' => 'integer|exists:families,id'
        ]);

        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateRequest->errors()
            ], 403);
        }


        $adminId = $request->admin_id;


        if (is_null($adminId)) {
            return response()->json([
                'success' => false,
                'message' => 'Admin ID is not set for the current user.'
            ], 403);
        }


        $data = [];

        if (isset($request->families) && is_array($request->families)) {
            $families = Family::whereIn('id', $request->families)
                ->with(['subfamily' => function ($query) use ($adminId) {
                    $query->where('admin_id', $adminId);
                }])
                ->get(['id', 'name']);
        } else {
            $families = Family::with(['subfamily' => function ($query) use ($adminId) {
                $query->where('admin_id', $adminId);
            }])
                ->get(['id', 'name']);
        }

        foreach ($families as $family) {
            $data[] = [
                'id' => $family->id,
                'name' => $family->name,
                'sub_family' => $family->subfamily->map(function ($subfamily) {
                    return [
                        'id' => $subfamily->id,
                        'name' => $subfamily->name
                    ];
                })
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }
}
