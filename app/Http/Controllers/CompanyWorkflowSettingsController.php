<?php

namespace App\Http\Controllers;

use App\Models\CompanyWorkflowSetting;
use App\Models\User;
use App\Services\CompanyStructureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CompanyWorkflowSettingsController extends Controller
{
    public function edit()
    {
        return view('admin.organization.workflow-settings', [
            'setting' => CompanyWorkflowSetting::find(1), 'people' => User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'blocked_until']),
        ]);
    }

    public function update(Request $request, CompanyStructureService $company)
    {
        $data = $request->validate([
            'internal_manager_id' => ['required', 'integer', Rule::exists('users', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'executive_manager_id' => ['required', 'integer', 'different:internal_manager_id', Rule::exists('users', 'id')->where('is_active', true)->whereNull('deleted_at')],
        ]);
        foreach ($data as $id) {
            abort_unless(! User::findOrFail($id)->isBlocked(), 422);
        }
        DB::transaction(function () use ($data) {
            $setting = CompanyWorkflowSetting::lockForUpdate()->find(1);
            if ($setting) {
                $setting->update($data);
            } else {
                CompanyWorkflowSetting::create(['id' => 1] + $data);
            }
            activity()->causedBy(auth()->user())->withProperties($data)->log('تنظیم مدیران اصلی گردش مرخصی و چارت گزارش‌کار');
        });
        $company->reset();

        return back()->with('success', 'مدیران اصلی چارت ذخیره شدند؛ مراحل درخواست‌های قبلی حفظ می‌شوند.');
    }
}
