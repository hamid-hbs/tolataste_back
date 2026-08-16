<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Models\ActivityLog;
use App\Models\AppNotification;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function unpaid(): JsonResponse
    {
        $orders = Order::where('status', Order::STATUS_SERVED)
            ->where('paid', false)
            ->with(['table', 'items'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['orders' => $orders]);
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $order = Order::with('table')->findOrFail($request->validated('order_id'));

        if ($order->status !== Order::STATUS_SERVED) {
            return response()->json(['message' => 'La commande doit être servie (served) pour être réglée.'], 409);
        }

        if ($order->paid) {
            return response()->json(['message' => 'La commande est déjà réglée.'], 409);
        }

        $amount = (int) $request->validated('amount');
        if ($amount < $order->total) {
            return response()->json([
                'message' => "Le montant ({$amount} FCFA) est inférieur au total ({$order->total} FCFA).",
            ], 422);
        }

        DB::transaction(function () use ($order, $request) {
            $order->payment()->create([
                'method' => $request->validated('method'),
                'amount' => $request->validated('amount'),
            ]);
            $order->update(['paid' => true]);
            $order->table?->free();
        });

        AppNotification::create([
            'type' => 'success',
            'title' => 'Paiement encaissé',
            'message' => "Commande #{$order->id} réglée ({$amount} FCFA).",
            'read' => false,
            'created_at' => now(),
        ]);

        ActivityLog::record($request->user(), 'payment', [
            'order_id' => $order->id,
            'method' => $request->validated('method'),
            'amount' => $amount,
        ]);

        return response()->json([
            'order' => $order->fresh(['items', 'table', 'payment', 'server:id,name']),
            'change' => $amount - $order->total,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $payments = Payment::query()
            ->with(['order.table', 'order.server:id,name'])
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json($payments);
    }
}
