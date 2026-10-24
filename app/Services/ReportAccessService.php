<?php

namespace App\Services;

use App\Models\User;

class ReportAccessService
{
    private array $teams = [];

    public function notificationIds(User $author): array
    {
        try{return app(OrganizationHierarchyService::class)->activeManagerChain($author)->pluck('id')->all();}
        catch(\DomainException){return [];}
    }

    public function canUsePersonalReports(User $user): bool
    {
        return $user->isActive() && ! $user->isBlocked()
            && ($user->hasAnyRole(['Admin', 'User', 'Marketer', 'Manager', 'Owner', 'InternalManager', 'internalManager', 'Supervisor'])
                || (config('organization_flow.enabled',false) && $user->department_id!==null)
                || $user->getAllPermissions()->contains(fn ($permission) => in_array($permission->name, ['reports.submit', 'reports.view-own'], true)));
    }

    public function canManageTeam(User $user): bool
    {
        return $user->isActive() && ! $user->isBlocked()
            && ($user->hasAnyRole(['Manager', 'Owner', 'InternalManager', 'internalManager', 'Supervisor'])
                || app(CompanyStructureService::class)->isLeader($user)
                || $user->getAllPermissions()->contains('name', 'reports.view-team'));
    }

    /** Null means authorized global supervision; an empty list means no team. */
    public function visibleTeamIds(User $user): ?array
    {
        if ($user->isActive() && ! $user->isBlocked() && $user->hasRole('Admin')) {
            return null;
        }
        if (! $this->canManageTeam($user)) {
            return [];
        }
        return $this->teams[$user->id] ??= app(OrganizationHierarchyService::class)->subordinateIds($user);
    }

    public function supervises(User $viewer, int $authorId): bool
    {
        $ids = $this->visibleTeamIds($viewer);
        return $ids === null || in_array($authorId, $ids, true);
    }
}
