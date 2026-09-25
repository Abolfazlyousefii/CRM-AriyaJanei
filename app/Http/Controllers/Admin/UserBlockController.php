<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserBlockController extends Controller
{
    public function block(Request $request, User $user)
    {
        abort_if($user->is(auth()->user()), 422, 'امکان مسدودکردن حساب خودتان وجود ندارد.');

        $request->validate([
            'hours' => 'required|integer|min:1|max:8760',
        ]);

        $hours = (int) $request->hours;
        $blockedUntil = now()->addHours($hours);

        $user->blocked_until = $blockedUntil;
        $user->save();

        // ثبت لاگ
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'blocked_until' => $blockedUntil->toDateTimeString(),
            ])
            ->log("کاربر مسدود شد برای {$hours} ساعت");

        return redirect()->back()->with('success', "کاربر برای {$hours} ساعت مسدود شد.");
    }

    public function unblock(User $user)
    {
        $user->blocked_until = null;
        $user->save();

        // ثبت لاگ
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log('کاربر آزاد شد');

        return redirect()->back()->with('success', 'کاربر آزاد شد.');
    }

    public function deactivate(Request $request, User $user)
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_if($user->is(auth()->user()), 422, 'امکان غیرفعال‌کردن حساب خودتان وجود ندارد.');
        abort_if(! $user->is_active, 422, 'این حساب قبلاً غیرفعال شده است.');

        DB::transaction(function () use ($request, $user): void {
            $user->forceFill([
                'is_active' => false,
                'deactivated_at' => now(),
                'deactivation_reason' => $request->string('reason')->trim()->value() ?: null,
                'deactivated_by' => auth()->id(),
            ])->save();

            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties(['reason' => $user->deactivation_reason])
                ->log('حساب کاربر غیرفعال شد');
        });

        return back()->with('success', 'حساب کاربر غیرفعال شد و اطلاعات او محفوظ ماند.');
    }

    public function activate(User $user)
    {
        abort_if($user->is_active, 422, 'این حساب در حال حاضر فعال است.');

        $user->forceFill([
            'is_active' => true,
            'deactivated_at' => null,
            'deactivation_reason' => null,
            'deactivated_by' => null,
        ])->save();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log('حساب کاربر فعال شد');

        return back()->with('success', 'حساب کاربر دوباره فعال شد.');
    }
}
