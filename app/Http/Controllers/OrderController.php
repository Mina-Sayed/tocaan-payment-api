<?php

namespace App\Http\Controllers;

use App\Http\Requests\Orders\StoreOrderRequest;
use App\Http\Requests\Orders\UpdateOrderRequest;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Order::query()
            ->where('user_id', $request->user()->id)
            ->with('items');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return response()->json($query->paginate($this->perPage($request)));
    }

    public function show(Request $request, int $orderId): JsonResponse
    {
        $order = $this->findOrder($request, $orderId)->load('items');

        return response()->json($order);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $items = $validated['items'];

        $order = DB::transaction(function () use ($request, $validated, $items): Order {
            $order = Order::create([
                'user_id' => $request->user()->id,
                'status' => Order::STATUS_PENDING,
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                'customer_address' => $validated['customer_address'] ?? null,
                'total' => 0,
            ]);

            $itemsPayload = $this->buildItemsPayload($items);
            $order->items()->createMany($itemsPayload);
            $order->total = $this->calculateTotal($itemsPayload);
            $order->save();

            return $order->load('items');
        });

        return response()->json($order, 201);
    }

    public function update(UpdateOrderRequest $request, int $orderId): JsonResponse
    {
        $order = $this->findOrder($request, $orderId);
        $validated = $request->validated();

        if (array_key_exists('items', $validated) && $order->payments()->exists()) {
            return response()->json(['message' => 'Order items cannot be modified while payments exist.'], 409);
        }

        $updated = DB::transaction(function () use ($order, $validated): Order {
            $items = $validated['items'] ?? null;

            $order->fill(collect($validated)->except('items')->toArray());

            if (is_array($items)) {
                $itemsPayload = $this->buildItemsPayload($items);
                $order->items()->delete();
                $order->items()->createMany($itemsPayload);
                $order->total = $this->calculateTotal($itemsPayload);
            }

            $order->save();

            return $order->load('items');
        });

        return response()->json($updated);
    }

    public function destroy(Request $request, int $orderId): JsonResponse
    {
        $order = $this->findOrder($request, $orderId);

        if ($order->payments()->exists()) {
            return response()->json(['message' => 'Order cannot be deleted while payments exist.'], 409);
        }

        $order->delete();

        return response()->json(['message' => 'Order deleted successfully.']);
    }

    private function findOrder(Request $request, int $orderId): Order
    {
        return Order::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($orderId);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function buildItemsPayload(array $items): array
    {
        return collect($items)->map(function (array $item): array {
            $lineTotal = round(((float) $item['quantity']) * ((float) $item['unit_price']), 2);

            return [
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'line_total' => $lineTotal,
            ];
        })->all();
    }

    /**
     * @param array<int, array<string, mixed>> $itemsPayload
     */
    private function calculateTotal(array $itemsPayload): float
    {
        return collect($itemsPayload)->sum('line_total');
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 15);

        return min(max($perPage, 1), 100);
    }
}
