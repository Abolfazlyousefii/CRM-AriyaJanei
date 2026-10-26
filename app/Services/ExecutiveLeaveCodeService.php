<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\User;
use App\Services\Sms\SmsManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class ExecutiveLeaveCodeService
{
    public function __construct(private readonly StagedLeaveService $flow, private readonly CompanyStructureService $company) {}

    public function canDelegate(User $actor, Leave $leave): bool
    {
        if (! $actor->isActive() || $actor->isBlocked() || $leave->approval_route !== 'staged' || $leave->status !== 'pending' || (int) $leave->user_id === (int) $actor->id) {
            return false;
        }
        $settings = $this->company->settings();
        $current = $this->flow->current($leave);

        return $settings && (int) $settings->internal_manager_id === (int) $actor->id
            && $current?->stage === 'executive' && (int) $current->approver_id === (int) $settings->executive_manager_id
            && $leave->steps()->where('stage', 'internal')->where('status', 'approved')->where('approver_id', $actor->id)->exists();
    }

    public function request(Leave $leave, User $actor): void
    {
        abort_unless($this->canDelegate($actor, $leave->fresh()), 403);
        if (config('sms.default') === 'sandbox' && ! app()->environment('testing')) {
            throw ValidationException::withMessages(['code' => 'سامانه پیامک واقعی تنظیم نشده است.']);
        }
        $rate = 'leave-code-send:'.$actor->id;
        if (RateLimiter::tooManyAttempts($rate, 10)) {
            throw ValidationException::withMessages(['code' => 'تعداد درخواست پیامک بیش از حد مجاز است. بعداً تلاش کنید.']);
        }
        RateLimiter::hit($rate, 3600);
        $code = (string) random_int(1000, 9999);
        [$challengeId,$stepId,$mobile] = DB::transaction(function () use ($leave, $actor, $code) {
            $locked = Leave::lockForUpdate()->findOrFail($leave->id);
            abort_unless($this->canDelegate($actor, $locked), 403);
            $step = $this->flow->current($locked);
            $executive = User::findOrFail($step->approver_id);
            abort_unless($executive->isActive() && ! $executive->isBlocked() && $executive->phone, 422);
            $old = DB::table('leave_executive_codes')->where('leave_id', $locked->id)->lockForUpdate()->first();
            if ($old && now()->diffInSeconds(Carbon::parse($old->updated_at), true) < 60) {
                throw ValidationException::withMessages(['code' => 'برای ارسال مجدد یک دقیقه صبر کنید.']);
            }
            DB::table('leave_executive_codes')->updateOrInsert(['leave_id' => $locked->id], [
                'step_id' => $step->id, 'requester_id' => $actor->id, 'executive_id' => $executive->id,
                'code_hash' => Hash::make($code), 'attempts' => 0, 'delivery_status' => 'sending', 'expires_at' => now()->addMinutes(5),
                'consumed_at' => null, 'created_at' => now(), 'updated_at' => now(),
            ]);

            return [DB::table('leave_executive_codes')->where('leave_id', $locked->id)->value('id'), $step->id, $executive->phone];
        });
        $sent = false;
        $invalidSender = false;
        try {
            $context = $leave->fresh()->load('user');
            $pattern = config('organization_flow.executive_sms_pattern_id');
            $result = filled($pattern)
                ? app(SmsManager::class)->sendPattern($mobile, (string) $pattern, [$code])
                : app(SmsManager::class)->send($mobile, 'مرخصی شماره '.$leave->id.'، '.$context->user?->name.'، '.verta($context->start_date)->format('Y/m/d').
                    '؛ کد تأیید نهایی: '.$code.' — اعتبار ۵ دقیقه. فقط برای تأیید همین درخواست.');
            $invalidSender = ($result['error_code'] ?? null) === 'invalid_sender';
            $body = $result['response'] ?? null;
            if (is_string($body)) {
                $body = json_decode($body, true) ?? $body;
            }
            $receipt = is_array($body) ? ($body['recId'] ?? $body['rec_id'] ?? $body['Value'] ?? null) : $body;
            $sent = is_numeric($receipt) && (float) $receipt > 0 && ! in_array($result['status'] ?? '', ['error', 'failed'], true);
        } catch (\Throwable) {
            $sent = false;
        }
        DB::table('leave_executive_codes')->where('id', $challengeId)->where('step_id', $stepId)->where('delivery_status', 'sending')
            ->update(['delivery_status' => $sent ? 'sent' : 'failed', 'expires_at' => now()->addMinutes(5), 'updated_at' => now()]);
        if (! $sent) {
            if ($invalidSender) {
                throw ValidationException::withMessages(['code' => 'خط فرستنده پیامک تنظیم نشده یا معتبر نیست؛ مدیر سیستم باید شماره خط فعال پنل را تنظیم کند. درخواست هنوز تأیید نشده است.']);
            }
            throw ValidationException::withMessages(['code' => 'ارسال پیامک تأیید نشد؛ درخواست هنوز تأیید نشده است. پس از یک دقیقه دوباره تلاش کنید.']);
        }
    }

    public function verify(Leave $leave, User $actor, string $code): void
    {
        abort_unless($this->canDelegate($actor, $leave->fresh()), 403);
        $rate = 'leave-code-verify:'.$actor->id.':'.$leave->id;
        if (RateLimiter::tooManyAttempts($rate, 10)) {
            throw ValidationException::withMessages(['code' => 'تعداد تلاش‌ها بیش از حد مجاز است.']);
        }
        RateLimiter::hit($rate, 600);
        $error = DB::transaction(function () use ($leave, $actor, $code) {
            $locked = Leave::lockForUpdate()->findOrFail($leave->id);
            abort_unless($this->canDelegate($actor, $locked), 403);
            $step = $this->flow->current($locked);
            $challenge = DB::table('leave_executive_codes')->where('leave_id', $locked->id)->lockForUpdate()->first();
            if (! $challenge || $challenge->delivery_status !== 'sent' || $challenge->consumed_at || now()->gte($challenge->expires_at)
                || $challenge->attempts >= config('organization_flow.otp_attempts', 5)
                || (int) $challenge->requester_id !== (int) $actor->id || (int) $challenge->step_id !== (int) $step->id
                || (int) $challenge->executive_id !== (int) $step->approver_id) {
                return 'کد معتبر نیست یا منقضی شده است.';
            }
            DB::table('leave_executive_codes')->where('id', $challenge->id)->increment('attempts');
            if (! Hash::check($code, $challenge->code_hash)) {
                return 'کد واردشده صحیح نیست.';
            }
            $executive = User::findOrFail($challenge->executive_id);
            abort_unless($executive->isActive() && ! $executive->isBlocked(), 422);
            DB::table('leave_executive_codes')->where('id', $challenge->id)->update(['consumed_at' => now(), 'updated_at' => now()]);
            $this->flow->completeLocked($locked, $actor, $executive, true, 'sms_code');

            return null;
        });
        if ($error) {
            throw ValidationException::withMessages(['code' => $error]);
        }
    }
}
