<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\FlipCourseCase;
use App\Services\FlipCourseWorkflowService;
use Illuminate\Http\Request;
use DB;

class OrderController extends Controller
{
    public function index()
    {
        $courses = Order::with('orderItems')->get();
        return response()->json($courses);
    }

    public function show($id)
    {
        $order = Order::with('orderItems')->findOrFail($id);
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

        DB::beginTransaction();
        try {
            // 創建訂單
            $order = Order::create($validated);

            // 插入訂單項目並檢查翻轉課程
            foreach ($validated['items'] as $item) {
                $orderItem = $order->orderItems()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'options' => isset($item['options']) ? json_encode($item['options']) : null,
                ]);

                // 檢查是否為翻轉課程商品
                $product = Product::with('flipCourseInfo')->find($item['product_id']);

                if ($product && $product->flipCourseInfo) {
                    // 解析 options
                    $options = $item['options'] ?? [];

                    // 自動建立翻轉課程案例
                    FlipCourseCase::create([
                        'flip_course_info_id' => $product->flipCourseInfo->id,
                        'student_id' => $validated['member_id'],
                        'order_id' => $order->id,
                        'planner_id' => $options['planner_id'] ?? null,
                        'counselor_id' => $options['counselor_id'] ?? null,
                        'analyst_id' => $options['analyst_id'] ?? null,
                        'workflow_stage' => 'created',
                        'payment_status' => 'pending',
                        'cycle_count' => 0,
                    ]);
                }
            }

            DB::commit();
            return response()->json($order->load('orderItems'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
                    $order->orderItems()->create($item);
                }
            }
        }

        return response()->json($order->load('orderItems'));
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->orderItems()->delete();
        $order->delete();

        return response()->json(['message' => 'Order deleted']);
    }
}
