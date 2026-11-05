<?php

namespace App\Http\Controllers;

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Models\Order;
use Database\Seeders\OrderSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Display the order dashboard with current orders and statistics.
     */
    public function index(Request $request): View|JsonResponse
    {
        $orders = Order::orderBy('created_at', 'desc')->get();

        $stats = [
            'total' => Order::count(),
            'pending' => Order::where('status', Order::STATUS_PENDING)->count(),
            'preparing' => Order::where('status', Order::STATUS_PREPARING)->count(),
            'ready' => Order::where('status', Order::STATUS_READY)->count(),
            'completed' => Order::where('status', Order::STATUS_COMPLETED)->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'orders' => $orders,
                'stats' => $stats,
            ]);
        }

        return view('orders.index', [
            'orders' => $orders,
            'stats' => $stats,
            'validStatuses' => Order::VALID_STATUSES,
        ]);
    }

    /**
     * Store a newly created order with initial 'Pending' status.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $order = Order::create([
            'customer_name' => trim($validated['customer_name']),
            'status' => Order::STATUS_PENDING,
        ]);

        // Broadcast the real-time order creation event
        broadcast(new OrderCreated($order));

        $stats = [
            'total' => Order::count(),
            'pending' => Order::where('status', Order::STATUS_PENDING)->count(),
            'preparing' => Order::where('status', Order::STATUS_PREPARING)->count(),
            'ready' => Order::where('status', Order::STATUS_READY)->count(),
            'completed' => Order::where('status', Order::STATUS_COMPLETED)->count(),
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Order #{$order->id} for '{$order->customer_name}' created successfully.",
                'order' => [
                    'id' => $order->id,
                    'customer_name' => $order->customer_name,
                    'status' => $order->status,
                    'created_at' => $order->created_at->toIso8601String(),
                    'created_at_formatted' => $order->created_at->format('M d, Y h:i A'),
                    'updated_at' => $order->updated_at->toIso8601String(),
                    'updated_at_formatted' => $order->updated_at->format('M d, Y h:i A'),
                ],
                'stats' => $stats,
            ], 201);
        }

        return redirect()->route('orders.index')->with('success', "Order #{$order->id} created successfully.");
    }

    /**
     * Update the status of an existing order and broadcast the event.
     */
    public function update(Request $request, Order $order): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', Order::VALID_STATUSES)],
        ]);

        $newStatus = $validated['status'];
        $oldStatus = $order->status;

        // Prevent redundant broadcasts if the status has not changed
        if ($newStatus === $oldStatus) {
            $message = "Order #{$order->id} is already marked as {$newStatus}.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'unchanged' => true,
                    'order' => $order,
                ]);
            }

            return redirect()->route('orders.index')->with('info', $message);
        }

        // Update database record
        $order->status = $newStatus;
        $order->save();

        // Broadcast OrderStatusUpdated event only after database update succeeds
        broadcast(new OrderStatusUpdated($order));

        $stats = [
            'total' => Order::count(),
            'pending' => Order::where('status', Order::STATUS_PENDING)->count(),
            'preparing' => Order::where('status', Order::STATUS_PREPARING)->count(),
            'ready' => Order::where('status', Order::STATUS_READY)->count(),
            'completed' => Order::where('status', Order::STATUS_COMPLETED)->count(),
        ];

        $successMessage = "Order #{$order->id} status updated to {$newStatus}.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'order' => [
                    'id' => $order->id,
                    'customer_name' => $order->customer_name,
                    'status' => $order->status,
                    'created_at' => $order->created_at->toIso8601String(),
                    'created_at_formatted' => $order->created_at->format('M d, Y h:i A'),
                    'updated_at' => $order->updated_at->toIso8601String(),
                    'updated_at_formatted' => $order->updated_at->format('M d, Y h:i A'),
                ],
                'stats' => $stats,
            ]);
        }

        return redirect()->route('orders.index')->with('success', $successMessage);
    }

    /**
     * Reset sample orders for demonstration purposes.
     */
    public function reset(): RedirectResponse
    {
        Order::truncate();
        (new OrderSeeder())->run();

        return redirect()->route('orders.index')->with('success', 'Sample orders reset to initial state.');
    }
}
