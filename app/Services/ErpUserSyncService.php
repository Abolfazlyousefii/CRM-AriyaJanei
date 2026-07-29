<?php

namespace App\Services;

use App\Http\Resources\ErpUserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ErpUserSyncService
{
    public function cursorPage(array $filters, Request $request): array
    {
        $cursor = (int) ($filters['cursor'] ?? 0);
        $limit = (int) ($filters['limit'] ?? 100);
        $query = $this->query($filters)->where('id', '>', $cursor)->orderBy('id');
        $users = $query->limit($limit + 1)->get();
        $hasMore = $users->count() > $limit;
        $users = $users->take($limit)->values();

        return [
            'data' => $users->map(fn (User $user) => (new ErpUserResource($user))->toArray($request))->all(),
            'next_cursor' => (int) ($users->last()?->id ?? $cursor),
            'has_more' => $hasMore,
            'meta' => ['schema_version' => 1],
        ];
    }

    public function legacyPage(array $filters, Request $request): array
    {
        $users = $this->query($filters)
            ->orderBy('id')
            ->paginate((int) ($filters['per_page'] ?? 100))
            ->through(fn (User $user) => (new ErpUserResource($user))->toArray($request));

        return ['message' => 'Users synced successfully.', 'users' => $users];
    }

    private function query(array $filters): Builder
    {
        return User::query()
            ->with('roles:id,name')
            ->when(! empty($filters['updated_since']), fn (Builder $query) => $query->where('updated_at', '>=', $filters['updated_since']))
            ->when(($filters['include_inactive'] ?? true) === false, function (Builder $query): void {
                $query->where(fn (Builder $active) => $active
                    ->whereNull('blocked_until')
                    ->orWhere('blocked_until', '<=', now()));
            });
    }
}
