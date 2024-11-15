<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    // public function getAll(Request $request)
    // {
    //     $notifications = Notification::where('admin_id', $request->admin_id)->get();
    //     return response()->json($notifications, 200);
    // }
    public function notification()
    {
        return view('storeuser');
    }
    public function getAll(Request $request)
    {
        // Check for both admin_id and user_id
        $notifications = Notification::where(function ($query) use ($request) {
            $query->where('admin_id', $request->admin_id)
                  ->Where('user_id', $request->user_id);
        })->get();

        return response()->json($notifications, 200);
    }
}
