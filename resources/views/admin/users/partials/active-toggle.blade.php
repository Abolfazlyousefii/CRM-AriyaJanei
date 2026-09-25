@if($user->is_active)
    <form action="{{ route('admin.users.deactivate', $user) }}" method="POST" class="d-inline"
          onsubmit="return confirm('این کاربر غیرفعال شود؟ اطلاعات و سوابق او حذف نخواهند شد.')">
        @csrf
        <button type="submit" class="btn btn-outline-warning btn-sm rounded-pill px-3">
            غیرفعال‌سازی
        </button>
    </form>
@else
    <form action="{{ route('admin.users.activate', $user) }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-success btn-sm rounded-pill px-3">
            فعال‌سازی مجدد
        </button>
    </form>
@endif
