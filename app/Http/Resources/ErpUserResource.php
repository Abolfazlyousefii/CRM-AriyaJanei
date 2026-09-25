<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErpUserResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'manager_id' => $this->manager_id === null ? null : (int) $this->manager_id,
            'roles' => $this->roles->pluck('name')->values()->all(),
            'is_active' => $this->isActiveForErp(),
            'can_access_erp' => $this->canAccessErp(),
            'is_seller' => $this->isSellerForErp(),
            'created_at' => $this->created_at?->utc()->toIso8601String(),
            'updated_at' => $this->updated_at?->utc()->toIso8601String(),
        ];
    }
}
