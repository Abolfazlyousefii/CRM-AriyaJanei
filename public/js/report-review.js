(() => {
    'use strict';
    const box = document.getElementById('report-review-inbox');
    if (!box) return;
    const feedback = document.getElementById('report-review-feedback');

    box.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-review-action]');
        if (!button || button.disabled) return;

        const kind = button.dataset.actionKind;
        const id = button.dataset.workflow;
        const comment = box.querySelector('[data-comment-for="' + id + '"]')?.value.trim() || '';
        let payload = { comment: comment || null };
        if (kind === 'reassign') {
            const reviewer = Number(box.querySelector('[data-reviewer-for="' + id + '"]')?.value);
            const reason = box.querySelector('[data-reason-for="' + id + '"]')?.value.trim() || '';
            if (!reviewer || reason.length < 3) {
                show('مسئول جدید را انتخاب کنید و دلیل تغییر را بنویسید.', true);
                return;
            }
            payload = { reviewer_id: reviewer, reason };
        }
        if (kind === 'return' && comment.length < 3) {
            show('برای ارجاع جهت اصلاح، حداقل سه کاراکتر توضیح بنویسید.', true);
            return;
        }
        if (kind === 'resubmit' && !window.confirm('آیا گزارش را اصلاح کرده‌اید و قصد ارسال مجدد دارید؟')) {
            return;
        }

        button.disabled = true;
        try {
            const response = await fetch(button.dataset.reviewAction, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': box.dataset.csrf
                },
                body: JSON.stringify(payload)
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) {
                const messages = result.errors ? Object.values(result.errors).flat().join('\n') : '';
                throw new Error(messages || result.message || 'ثبت تصمیم انجام نشد.');
            }
            window.location.reload();
        } catch (error) {
            show(error.message || 'خطا در ارتباط با سرور.', true);
            button.disabled = false;
        }
    });

    function show(message, error = false) {
        feedback.textContent = message;
        feedback.className = 'alert ' + (error ? 'alert-danger' : 'alert-success');
        feedback.hidden = false;
        feedback.scrollIntoView({ block: 'nearest' });
    }
})();
