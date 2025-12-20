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
    /**
     * @OA\Get(
     *     path="/orders",
     *     summary="Get all orders",
     *     description="Retrieve a list of all orders with their order items",
     *     operationId="getOrdersList",
     *     tags={"Orders"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="member_id", type="integer", example=1),
     *                 @OA\Property(property="code", type="string", example="ORD-20251204-001"),
     *                 @OA\Property(property="total", type="number", format="float", example=299.99),
     *                 @OA\Property(property="currency", type="string", example="USD"),
     *                 @OA\Property(property="shipping_address", type="string", nullable=true, example="123 Main St"),
     *                 @OA\Property(property="billing_address", type="string", nullable=true, example="123 Main St"),
     *                 @OA\Property(property="note", type="string", nullable=true, example="Please deliver before 5pm"),
     *                 @OA\Property(property="status", type="string", example="pending")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index()
    {
        $courses = Order::with('orderItems')->get();
        return response()->json($courses);
    }

    /**
     * @OA\Get(
     *     path="/orders/{id}",
     *     summary="Get a specific order",
     *     description="Retrieve detailed information about a specific order",
     *     operationId="getOrderById",
     *     tags={"Orders"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="member_id", type="integer", example=1),
     *             @OA\Property(property="code", type="string", example="ORD-20251204-001"),
     *             @OA\Property(property="total", type="number", format="float", example=299.99),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="shipping_address", type="string", nullable=true, example="123 Main St"),
     *             @OA\Property(property="billing_address", type="string", nullable=true, example="123 Main St"),
     *             @OA\Property(property="note", type="string", nullable=true, example="Please deliver before 5pm"),
     *             @OA\Property(property="status", type="string", example="pending")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function show($id)
    {
        $order = Order::with('orderItems')->findOrFail($id);
        return response()->json($order);
    }

    /**
     * @OA\Post(
     *     path="/orders",
     *     summary="Create a new order",
     *     description="Create a new order with order items. Automatically creates flip course cases for flip course products.",
     *     operationId="createOrder",
     *     tags={"Orders"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Order data",
     *         @OA\JsonContent(
     *             required={"member_id", "code", "total", "currency", "status", "items"},
     *             @OA\Property(property="member_id", type="integer", example=1),
     *             @OA\Property(property="code", type="string", example="ORD-20251204-001"),
     *             @OA\Property(property="total", type="number", format="float", example=299.99),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="shipping_address", type="string", nullable=true, example="123 Main St"),
     *             @OA\Property(property="billing_address", type="string", nullable=true, example="123 Main St"),
     *             @OA\Property(property="note", type="string", nullable=true, example="Please deliver before 5pm"),
     *             @OA\Property(property="status", type="string", example="pending"),
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"product_id", "product_name", "quantity", "price"},
     *                     @OA\Property(property="product_id", type="integer", example=1),
     *                     @OA\Property(property="product_name", type="string", example="Advanced English Course"),
     *                     @OA\Property(property="quantity", type="integer", example=1),
     *                     @OA\Property(property="price", type="number", format="float", example=99.99),
     *                     @OA\Property(
     *                         property="options",
     *                         type="object",
     *                         nullable=true,
     *                         @OA\Property(property="planner_id", type="integer", example=2),
     *                         @OA\Property(property="counselor_id", type="integer", example=3),
     *                         @OA\Property(property="analyst_id", type="integer", example=4)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="member_id", type="integer", example=1),
     *             @OA\Property(property="code", type="string", example="ORD-20251204-001"),
     *             @OA\Property(property="total", type="number", format="float", example=299.99),
     *             @OA\Property(property="status", type="string", example="pending")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/orders/{id}",
     *     summary="Update an order",
     *     description="Update an existing order and its items",
     *     operationId="updateOrder",
     *     tags={"Orders"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         description="Order data to update",
     *         @OA\JsonContent(
     *             @OA\Property(property="member_id", type="integer", example=1),
     *             @OA\Property(property="code", type="string", example="ORD-20251204-001"),
     *             @OA\Property(property="total", type="number", format="float", example=299.99),
     *             @OA\Property(property="currency", type="string", example="USD"),
     *             @OA\Property(property="shipping_address", type="string", nullable=true, example="123 Main St"),
     *             @OA\Property(property="billing_address", type="string", nullable=true, example="123 Main St"),
     *             @OA\Property(property="note", type="string", nullable=true, example="Updated note"),
     *             @OA\Property(property="status", type="string", example="confirmed"),
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", nullable=true, example=1),
     *                     @OA\Property(property="product_id", type="integer", example=1),
     *                     @OA\Property(property="product_name", type="string", example="Advanced English Course"),
     *                     @OA\Property(property="quantity", type="integer", example=1),
     *                     @OA\Property(property="price", type="number", format="float", example=99.99),
     *                     @OA\Property(property="options", type="object", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="member_id", type="integer", example=1),
     *             @OA\Property(property="code", type="string", example="ORD-20251204-001"),
     *             @OA\Property(property="total", type="number", format="float", example=299.99),
     *             @OA\Property(property="status", type="string", example="confirmed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/orders/{id}",
     *     summary="Delete an order",
     *     description="Delete a specific order and all its order items",
     *     operationId="deleteOrder",
     *     tags={"Orders"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Order deleted")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->orderItems()->delete();
        $order->delete();

        return response()->json(['message' => 'Order deleted']);
    }
}
