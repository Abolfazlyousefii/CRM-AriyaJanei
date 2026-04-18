<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class TaskOverdueNotificationService
{
    public function syncForManager(User $manager): void
    {
        if (!$manager->hasRole('Manager')) {
            return;
        }

        if (!Schema::hasColumn('tasks', 'due_at')) {
            return;
        }

        $overdueTasks = Task::query()
            ->with('user:id,name,manager_id')
            ->where('completed', false)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereHas('user', function ($query) use ($manager) {
                $query->where('manager_id', $manager->id);
            })
            ->get();

        foreach ($overdueTasks as $task) {
            Notification::firstOrCreate(
                [
                    'user_id' => $manager->id,
                    'task_id' => $task->id,
                    'title' => 'تسک انجام نشده',
                ],
                [
                    'message' => "تسک \"{$task->title}\" برای {$task->user?->name} تا زمان تحویل انجام نشده است.",
                    'seen' => false,
                ]
            );
        }
    }
}