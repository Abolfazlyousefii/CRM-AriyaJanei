<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExternalUserSyncController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'updated_since' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $query = User::query()->with('roles:id,name');

        if (! empty($validated['updated_since'])) {
            $query->where('updated_at', '>=', $validated['updated_since']);
        }

        $perPage = $validated['per_page'] ?? 100;

        $users = $query
            ->orderBy('id')
            ->paginate($perPage)
            ->through(function (User $user): array {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'manager_id' => $user->manager_id,
                    'roles' => $user->roles->pluck('name')->values(),
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];
            });

        return response()->json([
            'message' => 'Users synced successfully.',
            'users' => $users,
        ]);
    }
}
