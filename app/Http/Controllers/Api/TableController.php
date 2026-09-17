<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTableRequest;
use App\Http\Requests\UpdateTableRequest;
use App\Http\Requests\UpdateTableStatusRequest;
use App\Models\ActivityLog;
use App\Models\Table;
use Illuminate\Http\JsonResponse;

class TableController extends Controller
{
    public function index(): JsonResponse
    {
        $tables = Table::withCount(['orders as active_orders' => fn ($q) => $q->whereIn('status', ['pending', 'waiting', 'preparing', 'ready'])])
            ->orderBy('number')
            ->get();

        return response()->json(['tables' => $tables]);
    }

    /**
     * Tables libres visibles par les clients (sans auth).
     */
    public function available(): JsonResponse
    {
        $tables = Table::query()
            ->where('status', Table::STATUS_FREE)
            ->orderBy('number')
            ->get(['id', 'number', 'zone', 'status']);

        return response()->json(['tables' => $tables]);
    }

    public function store(StoreTableRequest $request): JsonResponse
    {
        $table = Table::create($request->validated());

        ActivityLog::record($request->user(), 'table_created', ['table_id' => $table->id, 'number' => $table->number]);

        return response()->json(['table' => $table], 201);
    }

    public function update(UpdateTableRequest $request, Table $table): JsonResponse
    {
        $table->update($request->validated());

        ActivityLog::record($request->user(), 'table_updated', ['table_id' => $table->id, 'number' => $table->number]);

        return response()->json(['table' => $table->fresh()]);
    }

    public function updateStatus(UpdateTableStatusRequest $request, Table $table): JsonResponse
    {
        $table->update(['status' => $request->validated('status')]);

        ActivityLog::record($request->user(), 'table_status_changed', ['table_id' => $table->id, 'status' => $table->status]);

        return response()->json(['table' => $table->fresh()]);
    }

    public function destroy(Table $table): JsonResponse
    {
        if ($table->status === Table::STATUS_OCCUPIED) {
            return response()->json([
                'message' => 'Impossible de supprimer : la table est occupée par une commande en cours.',
            ], 409);
        }

        $table->delete();

        ActivityLog::record(request()->user(), 'table_deleted', ['table_id' => $table->id, 'number' => $table->number]);

        return response()->json(['message' => 'Table supprimée.']);
    }
}
