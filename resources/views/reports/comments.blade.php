@if(config('organization_flow.enabled',false))
<section class="card my-4 w-100" dir="rtl" aria-label="گفت‌وگوی گزارش‌کار">
    <div class="card-body">
        <h3 class="h5">نظر مدیران روی گزارش‌کار</h3>
        @foreach($report->comments()->with('user')->get() as $comment)
            <article class="border rounded p-3 my-2">
                <strong>{{ $comment->user?->name ?? 'کاربر آرشیوشده' }}</strong>
                <small class="text-muted">{{ verta($comment->created_at)->format('Y/m/d H:i') }}</small>
                <p class="mt-2 mb-0" style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $comment->body }}</p>
            </article>
        @endforeach
        @can('feedback',$report)
            <form action="{{ route('reports.comments.store',$report) }}" method="POST" class="mt-3">
                @csrf
                <label for="report-comment">نظر شما برای صاحب گزارش</label>
                <textarea id="report-comment" name="body" class="form-control" required maxlength="4000" rows="3">{{ old('body') }}</textarea>
                @error('body')<p class="text-danger">{{ $message }}</p>@enderror
                <button class="btn btn-primary mt-2" type="submit">ثبت نظر و اطلاع به کارمند</button>
            </form>
        @endcan
    </div>
</section>
@endif
