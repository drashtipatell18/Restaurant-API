<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\OrderDetails;
use App\Models\OrderMaster;
use App\Models\Role;
use App\Models\Sector;
use App\Models\Table;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SectorController extends Controller
{
    public function createSector(Request $request)
    {
        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if ($role != "admin") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 405);
        }

        $validateRequest = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'noOfTables' => 'required|integer|min:1'
        ]);

        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        $sector = Sector::create([
            'name' => $request->input('name')
        ]);

        $tables = [];

        for ($i = 0; $i < $request->input('noOfTables'); $i++) {
            $table = Table::create([
                'user_id' => Auth()->user()->id,
                'sector_id' => $sector->id,
                'name' => 'Table ' . ($i + 1)
            ]);

            array_push($tables, $table);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sector and Tabled added successfully.',
            'sector' => $sector,
            'tables' => $tables
        ], 200);
    }

    public function updateSector(Request $request, $id) {
        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if ($role != "admin") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 405);
        }
    
        $validateRequest = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'noOfTables' => 'required|integer|min:1'
        ]);
    
        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }
    
        $sector = Sector::findOrFail($id);
        $sector->update([
            'name' => $request->input('name')
        ]);
    
        $tables = [];
    
        // Delete existing tables
        Table::where('sector_id', $sector->id)->delete();
    
        // Create new tables
        for ($i = 0; $i < $request->input('noOfTables'); $i++) {
            $table = Table::create([
                'user_id' => Auth::user()->id,
                'sector_id' => $sector->id,
                'name' => 'Table ' . ($i + 1)
            ]);
    
            array_push($tables, $table);
        }
    
        return response()->json([
            'success' => true,
            'message' => 'Sector and Tables updated successfully.',
            'sector' => $sector,
            'tables' => $tables
        ], 200);
    }

    public function deleteSector($id)
    {
        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if ($role != "admin") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 405);
        }

        $tables = Table::all()->where('sector_id', $id);
        foreach ($tables as $table) {
            $table->delete();
        }
        $sector = Sector::find($id);
        $sector->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sector deleted successfully.'
        ], 200);
    }

    public function getSector()
    {
        $sections = Sector::all('id', 'name')->all();
        return response()->json([
            'success' => true,
            'sectors' => $sections
        ], 200);
    }

    public function getSectionWithTable(Request $request)
    {
        // Validate the request
        $validateRequest = Validator::make($request->all(), [
            'sectors' => 'array',
            'sectors.*' => 'integer|exists:sectors,id'
        ]);

        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        // Initialize the data array
        $data = [];

        if (isset($request->sectors) && is_array($request->sectors)) {
            // Get sectors based on provided IDs
            $sectors = Sector::whereIn('id', $request->sectors)->get(['id', 'name']);
        } else {
            // Get all sectors
            $sectors = Sector::all(['id', 'name']);
        }

        // Loop through each sector
        foreach ($sectors as $sector) {
            // Get tables for the sector
            $tables = Table::where('sector_id', $sector->id)->get(['id', 'name', 'status']);
            $tableData = [];

            // Loop through each table
            foreach ($tables as $table) {
                // Prepare table info
                $tableInfo = [
                    'id' => $table->id,
                    'name' => $table->name,
                    'status' => $table->status,
                ];

                // Check if table is busy and get the latest order ID
                if ($table->status === 'busy') {
                    $order = OrderMaster::where('table_id', $table->id)->latest()->first(['id']);
                    if ($order) {
                        $tableInfo['order_id'] = $order->id;
                    }
                }

                // Add table info to table data array
                $tableData[] = $tableInfo;
            }

            // Add sector info to data array
            $data[] = [
                'id' => $sector->id,
                'name' => $sector->name,
                'tables' => $tableData,
            ];
        }

        // Return response
        return response()->json(["success" => true, "data" => $data], 200);
    }

    public function getSectorByTableId($tableId)
    {
        $table = Table::find($tableId);

        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Table not found'
            ], 404);
        }

        $sector = Sector::find($table->sector_id);

        if (!$sector) {
            return response()->json([
                'success' => false,
                'message' => 'Sector not found for this table'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'sector' => [
                'id' => $sector->id,
                'name' => $sector->name
            ]
        ], 200);
    }

    public function updateTableStatus(Request $request)
    {
        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if ($role != "admin") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 405);
        }
        $validateRequest = Validator::make($request->all(), [
            'table_id' => 'required|exists:restauranttables,id',
            'status' => 'required|in:available,busy'
        ]);

        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        $table = Table::find($request->table_id);
        $table->status = $request->status;
        $table->save();

        return response()->json([
            'success' => true,
            'message' => 'Table updated to ' . $table->status
        ], 200);
    }

    public function addTables(Request $request)
    {
        $role = Role::where('id', Auth::user()->role_id)->first()->name;
        if ($role != "admin") {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorised'
            ], 405);
        }

        $validateRequest = Validator::make($request->all(), [
            'sector_id' => 'required|exists:sectors,id',
            'noOfTables' => 'required|integer|min:1'
        ]);

        if ($validateRequest->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation fails',
                'errors' => $validateRequest->errors()
            ], 403);
        }

        $lastTableName = Table::all()->where('sector_id', $request->sector_id)->last();

        $lastTable = explode(' ', $lastTableName->name);
        $lastNo = $lastTable[1];

        $tables = [];

        for ($i = 0; $i < $request->noOfTables; $i++) {
            $table = Table::create([
                'user_id' => Auth()->user()->id,
                'sector_id' => $request->sector_id,
                'name' => 'Table ' . (++$lastNo)
            ]);

            array_push($tables, $table);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tables added successfully',
            'tables' => $tables
        ], 200);
    }

    public function getTableStats(Request $request, $id)
    {
        if (Table::find($id) == null) {
            return response()->json([
                'success' => false,
                'message' => 'Table id invalid'
            ], 403);
        }

        $responseData = [];

        $ordersQuery = OrderMaster::where('table_id', $id);

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

        return response()->json($responseData, 200);
    }

    public function getKds($table_id)
    {
        $table = Table::find($table_id);

        if ($table == null) {
            return response()->json([
                'success' => false,
                'message' => "Table id invalid"
            ], 403);
        }

        $responseData = ["received" => [], "prepared" => [], "finalized" => [], "delivered" => []];

        $orderMaster = OrderMaster::where('table_id', $table->id)->get();
        foreach ($orderMaster as $order) {
            $orderDetails = OrderDetails::where('order_master_id', $order->id)->get();
            $data = [
                "order_id" => $order->id,
                "from" => Carbon::parse($order->created_at)->format('H:i'),
                "items" => [],
                "notes" => $order->notes
            ];
            foreach ($orderDetails as $orderDetail) {
                $item = Item::find($orderDetail->item_id);
                $data["items"][] = $item->name . "(x" . $orderDetail->quantity . " qty)";
            }

            if ($order->status == "received") {
                $responseData["received"][] = $data;
            }
            if ($order->status == "prepared") {
                $responseData["prepared"][] = $data;
            }
            if ($order->status == "finalized") {
                $responseData["finalized"][] = $data;
            }
            if ($order->status == "delivered") {
                $data["finished_at"] = Carbon::parse($order->updated_at)->format('H:i');
                $responseData["delivered"][] = $data;
            }
        }

        return response()->json($responseData, 200);
    }
}
