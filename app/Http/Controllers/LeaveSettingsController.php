<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\User;
use App\Services\LeaveApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeaveSettingsController extends Controller
{
    public function edit()
    {
        return view('Leaves.settings', [
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedIds' => DB::table('leave_approvers')->pluck('user_id')->all(),
        ]);
    }

    public function update(Request $request, LeaveApprovalService $approval)
    {
        $data = $request->validate([
            'approver_ids' => ['required', 'array', 'min:1'],
            'approver_ids.*' => ['required', 'integer', 'distinct', Rule::exists('users', 'id')->where('is_active', true)->whereNull('deleted_at')],
        ]);

        DB::transaction(function () use ($data, $approval) {
            DB::table('leave_approvers')->delete();
            DB::table('leave_approvers')->insert(array_map(fn ($id) => ['user_id' => $id], $data['approver_ids']));
            Leave::query()->whereIn('status', LeaveApprovalService::PENDING_STATUSES)
                ->with('user')->chunkById(100, function ($leaves) use ($approval) {
                    foreach ($leaves as $leave) {
                        $approval->notifyApprovers($leave);
                    }
                });
        });

        return back()->with('success', 'تأییدکنندگان ذخیره شدند و درخواست‌های معلق به آنان ارجاع داده شد.');
    }
}
