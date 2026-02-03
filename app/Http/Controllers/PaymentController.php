<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payments\ProcessPaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Payment::query()
            ->whereHas('order', function ($builder) use ($request): void {
                $builder->where('user_id', $request->user()->id);
            });

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($method = $request->query('method')) {
            $query->where('method', $method);
        }

        return response()->json($query->paginate($this->perPage($request)));
    }

    public function orderPayments(Request $request, int $orderId): JsonResponse
    {
        $order = $this->findOrder($request, $orderId);
        $payments = $order->payments()->paginate($this->perPage($request));

        return response()->json($payments);
    }

    public function store(ProcessPaymentRequest $request, int $orderId): JsonResponse
    {
        $order = $this->findOrder($request, $orderId);

        if ($order->status !== Order::STATUS_CONFIRMED) {
            return response()->json(['message' => 'Payments can only be processed for confirmed orders.'], 409);
        }

        if ($order->payments()->where('status', Payment::STATUS_SUCCESSFUL)->exists()) {
            return response()->json(['message' => 'Order already has a successful payment.'], 409);
        }

        $payment = $this->paymentService->process($order, $request->validated());

        return response()->json($payment, 201);
    }

    private function findOrder(Request $request, int $orderId): Order
    {
        return Order::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($orderId);
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 15);

        return min(max($perPage, 1), 100);
    }
}
