<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->input('role')))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', "%{$request->input('search')}%"))
            ->orderBy('name')
            ->get();

        return response()->json(['users' => $users]);
    }

    public function staff(): JsonResponse
    {
        $staff = User::where('role', '!=', User::ROLE_CLIENT)->orderBy('name')->get();

        return response()->json(['users' => $staff]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        ActivityLog::record($request->user(), 'user_created', ['user_id' => $user->id, 'name' => $user->name, 'role' => $user->role]);

        return response()->json(['user' => $user], 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user->update($request->validated());

        ActivityLog::record($request->user(), 'user_updated', ['user_id' => $user->id, 'name' => $user->name]);

        return response()->json(['user' => $user->fresh()]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'Impossible de supprimer votre propre compte.'], 409);
        }

        $user->delete();

        ActivityLog::record($request->user(), 'user_deleted', ['user_id' => $user->id, 'name' => $user->name]);

        return response()->json(['message' => 'Utilisateur supprimé.']);
    }
}
