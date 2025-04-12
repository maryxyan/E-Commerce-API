<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of all orders (admin only).
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.product']);
        
        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }
        
        $orders = $query->paginate(15);
        
        return new OrderCollection($orders);
    }

    /**
     * Display orders for the current customer
     */
    public function customerOrders(Request $request)
    {
        $orders = $request->user()->orders()->with('items.product')->paginate(10);
        
        return new OrderCollection($orders);
    }

    /**
     * Store a newly created order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|string',
            'payment_method' => 'required|string'
        ]);

        try {
            return DB::transaction(function () use ($request, $validated) {
             // Check stock availability
             foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                if ($product->stock < $item['quantity']) {
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }
            }    
                $totalPrice = 0;
                $items = [];

                // Calculate total price and prepare order items
                foreach ($validated['items'] as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    
                    // Check if enough stock is available
                    if ($product->stock_quantity < $item['quantity']) {
                        throw new \Exception("Not enough stock for product: {$product->name}");
                    }
                    
                    // Calculate item price
                    $itemPrice = $product->price * $item['quantity'];
                    $totalPrice += $itemPrice;
                    
                    // Prepare order item
                    $items[] = [
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'price' => $product->price
                    ];
                    
                    // Reduce stock
                    $product->stock_quantity -= $item['quantity'];
                    $product->save();
                }

                // Create order
                $order = Order::create([
                    'user_id' => $request->user()->id,
                    'total_price' => $totalPrice,
                    'status' => Order::STATUS_PENDING,
                    'shipping_address' => $validated['shipping_address'],
                    'payment_method' => $validated['payment_method'],
                    'payment_status' => 'pending'
                ]);
                //Create order items and reduce stock
                foreach($validated['items'] as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $order->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'price' => $product->price
                    ]);
                    // Reduce stock
                    $prduct->decrement('stock_quantity', $item['quantity']);
                }
                $order->total_price = $order->calculateTotal();
                $order->save();
                
                // Save order items
                foreach ($items as $item) {
                    $order->items()->create($item);
                }

                return new OrderResource($order->load('items.product'));
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
    

    /**
     * Display the specified order.
     */
    public function show(Order $order)
    {
        // Authorization is handled by the 'can:view,order' middleware
        return new OrderResource($order->load('items.product', 'user'));
    }

    /**
     * Update the order status (admin only).
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', [
                Order::STATUS_PENDING,
                Order::STATUS_PROCESSING,
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELLED
            ])
        ]);

        $order->status = $validated['status'];
        $order->save();

        return $order;
    }
}

