<?php

namespace App\Console\Commands;

use App\Models\Leave;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MoveManagerOneLeavesToInternalApproval extends Command
{
    protected $signature = 'leaves:move-manager-one {--dry-run : فقط تعداد را نمایش می‌دهد و چیزی تغییر نمی‌دهد}';

    protected $description = 'Move leaves with manager_id = 1 from manager_approved to internal_approved';

    public function handle(): int
    {
        $query = Leave::query()
            ->with('user')
            ->where('manager_id', 1)
            ->where('status', 'manager_approved');

        $count = (clone $query)->count();

        if ($count === 0) {
            $this->info('هیچ مرخصی نیازمند انتقال به مرحله مدیر داخلی پیدا نشد.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("تعداد {$count} مرخصی پیدا شد که باید به مرحله تایید مدیر داخلی منتقل شود.");
            return self::SUCCESS;
        }

        $internalManagerIds = User::role(['Admin', 'internalManager', 'InternalManager'])
            ->pluck('id')
            ->unique()
            ->values();

        DB::transaction(function () use ($query, $internalManagerIds) {
            $query->chunkById(100, function ($leaves) use ($internalManagerIds) {
                foreach ($leaves as $leave) {
                    $leave->update([
                        'status' => 'internal_approved',
                    ]);

                    foreach ($internalManagerIds as $internalManagerId) {
                        Notification::create([
                            'user_id'  => $internalManagerId,
                            'leave_id' => $leave->id,
                            'title'    => 'مرخصی آماده تایید مدیر داخلی',
                            'message'  => "مرخصی {$leave->user?->name} به صورت خودکار وارد مرحله تایید مدیر داخلی شد.",
                            'seen'     => false,
                        ]);
                    }

                    Notification::create([
                        'user_id'  => $leave->user_id,
                        'leave_id' => $leave->id,
                        'title'    => 'مرخصی شما وارد مرحله مدیر داخلی شد',
                        'message'  => 'درخواست مرخصی شما به صورت خودکار وارد مرحله تایید مدیر داخلی شد.',
                        'seen'     => false,
                    ]);
                }
            });
        });

        $this->info("تعداد {$count} مرخصی با موفقیت به مرحله تایید مدیر داخلی منتقل شد.");

        return self::SUCCESS;
    }
}