<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $courses = Order::with('orderItem')->get();
        return response()->json($courses);
    }

    public function show($id)
    {
        $order = Order::with('orderItem')->findOrFail($id);
        return response()->json($order);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|integer',
            'code' => 'required|string|unique:orders,code',
            'total' => 'required|numeric',
            'currency' => 'required|string',
            'shipping_address' => 'nullable|string',
            'billing_address' => 'nullable|string',
            'note' => 'nullable|string',
            'status' => 'required|string',
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer',
            'items.*.price' => 'required|numeric',
            'items.*.options' => 'nullable|array',
        ]);

        // 創建訂單
        $order = Order::create($validated);

        // 插入訂單項目
        foreach ($validated['items'] as $item) {
            $item['options'] = isset($item['options']) ? json_encode($item['options']) : null;
            $order->orderItem()->create($item);
        }

        return response()->json($order->load('orderItem'), 201);
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'member_id' => 'sometimes|integer',
            'code' => 'sometimes|string|unique:orders,code,' . $order->id,
            'total' => 'sometimes|numeric',
            'currency' => 'sometimes|string',
            'shipping_address' => 'nullable|string',
            'billing_address' => 'nullable|string',
            'note' => 'nullable|string',
            'status' => 'sometimes|string',
            'items' => 'sometimes|array',
            'items.*.id' => 'sometimes|integer|exists:order_items,id',
            'items.*.product_id' => 'sometimes|integer',
            'items.*.product_name' => 'sometimes|string',
            'items.*.quantity' => 'sometimes|integer',
            'items.*.price' => 'sometimes|numeric',
            'items.*.options' => 'nullable|array',
        ]);

        // 更新訂單主表
        $order->update($validated);

        // 更新訂單項目
        if (isset($validated['items'])) {
            foreach ($validated['items'] as $item) {
                if (isset($item['id'])) {
                    $orderItem = OrderItem::find($item['id']);
                    if ($orderItem) {
                        $item['options'] = isset($item['options']) ? json_encode($item['options']) : $orderItem->options;
                        $orderItem->update($item);
                    }
                } else {
                    $item['options'] = isset($item['options']) ? json_encode($item['options']) : null;
                    $order->orderItem()->create($item);
                }
            }
        }

        return response()->json($order->load('orderItem'));
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->orderItem()->delete();
        $order->delete();

        return response()->json(['message' => 'Order deleted']);
    }
}
