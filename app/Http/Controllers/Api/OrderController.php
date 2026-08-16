<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\ActivityLog;
use App\Models\AppNotification;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /**
     * Commandes du client connecté (avec statut + serveur qui a pris la commande).
     */
    public function mine(Request $request): JsonResponse
    {
        $orders = Order::where('client_id', $request->user()->id)
            ->with(['items', 'table', 'server:id,name'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['orders' => $orders]);
    }

    /**
     * Liste filtrée par statut / jour.
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->with(['table', 'server:id,name', 'client:id,name', 'items'])
            ->when($request->filled('status'), function ($q) use ($request) {
                $status = $request->input('status');
                if (is_array($status)) {
                    $q->whereIn('status', array_filter($status));
                } else {
                    $q->where('status', $status);
                }
            })
            ->when($request->filled('date'), fn ($q) => $q->whereDate('created_at', $request->input('date')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('server_id'), fn ($q) => $q->where('server_id', $request->input('server_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json($orders);
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json([
            'order' => $order->load(['items.product', 'table', 'server:id,name', 'client:id,name', 'payment']),
        ]);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->safe()->only(['type', 'note', 'items']);
        $isStaff = (bool) ($user?->isStaff() ?? false);

        if (! $isStaff) {
            $data['table_id'] = null;
        } else {
            $data['table_id'] = $request->validated('table_id');
        }

        // 1. Reconstruction des lignes depuis la BDD (jamais depuis le client).
        $items = $this->buildItems($data['items']);

        $order = DB::transaction(function () use ($data, $items, $user, $isStaff) {
            $order = Order::create([
                'table_id' => $data['table_id'] ?? null,
                'type' => $data['type'] ?? Order::TYPE_TAKEAWAY,
                'note' => $data['note'] ?? null,
                'server_id' => $isStaff ? $user->id : null,
                'client_id' => $isStaff ? null : $user?->id,
                'status' => $isStaff ? Order::STATUS_PREPARING : Order::STATUS_PENDING,
                'paid' => false,
            ]);

            $order->items()->createMany($items);
            $order->recomputeTotal();
            $order->save();

            if ($order->table) {
                $order->table->occupy();
            }

            return $order;
        });

        $order->load(['items', 'server:id,name']);

        OrderStatusChanged::dispatch($order);
        ActivityLog::record($user, 'order_created', ['order_id' => $order->id, 'total' => $order->total]);
        $this->notify('info', 'Nouvelle commande', "Commande #{$order->id} reçue (total {$order->total} FCFA).");

        return response()->json(['order' => $order], 201);
    }

    public function updateStatus(Request $request, UpdateOrderStatusRequest $formRequest, Order $order): JsonResponse
    {
        $user = $request->user();
        $newStatus = $formRequest->validated('status');

        // Séparation des pouvoirs : le chef ne sert pas, le serveur ne cuisine pas.
        // Le serveur peut « prendre en charge » une nouvelle commande (pending → waiting).
        $isKitchen = in_array($user->role, [User::ROLE_CUISINE, User::ROLE_MANAGER, User::ROLE_ADMIN], true);
        $isServer = in_array($user->role, [User::ROLE_SERVEUR, User::ROLE_MANAGER, User::ROLE_ADMIN], true);

        if (in_array($newStatus, [Order::STATUS_PREPARING, Order::STATUS_READY], true) && ! $isKitchen) {
            return response()->json(['message' => 'Ce statut est réservé à la cuisine.'], 403);
        }

        if ($newStatus === Order::STATUS_SERVED && ! $isServer) {
            return response()->json(['message' => 'La livraison (served) est réservée au serveur.'], 403);
        }

        if (! $order->canTransitionTo($newStatus)) {
            return response()->json([
                'message' => "Transition invalide : {$order->status} → {$newStatus}.",
            ], 409);
        }

        $wasCancelled = $newStatus === Order::STATUS_CANCELLED;
        $order->update([
            'status' => $newStatus,
            'paid' => $wasCancelled ? false : $order->paid,
            // Le premier serveur qui prend la commande en charge est enregistré.
            'server_id' => $isServer ? ($order->server_id ?? $user->id) : $order->server_id,
        ]);

        if ($wasCancelled) {
            $order->table?->free();
        }

        OrderStatusChanged::dispatch($order->fresh('table'));
        ActivityLog::record($user, 'order_status_changed', ['order_id' => $order->id, 'status' => $newStatus]);

        $title = $wasCancelled ? 'Commande annulée' : 'Statut mis à jour';
        $this->notify($wasCancelled ? 'danger' : 'success', $title, "Commande #{$order->id} : {$newStatus}.");

        return response()->json(['order' => $order->fresh(['items', 'table'])]);
    }

    /**
     * Reconstruit les lignes depuis les produits actifs.
     *
     * @throws ValidationException
     */
    private function buildItems(array $rawItems): array
    {
        $productIds = collect($rawItems)->pluck('product_id')->unique();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $items = [];
        foreach ($rawItems as $raw) {
            $product = $products->get($raw['product_id']);
            $qty = (int) $raw['qty'];

            if (! $product || ! $product->active) {
                throw ValidationException::withMessages([
                    'items' => "Le produit {$raw['product_id']} n'est pas disponible.",
                ]);
            }

            $items[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'qty' => $qty,
                'total' => $product->price * $qty,
            ];
        }

        return $items;
    }

    private function notify(string $type, string $title, string $message): void
    {
        AppNotification::create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'read' => false,
            'created_at' => now(),
        ]);
    }
}
