/* Refresh only the login form's CSRF token; credentials still use the normal protected POST. */
(() => {
  const form = document.querySelector('[data-login-form]');
  if (!form) return;
  let refreshing = false;
  let ready = false;
  form.addEventListener('submit', async (event) => {
    if (ready) { ready = false; return; }
    event.preventDefault();
    if (refreshing) return;
    refreshing = true;
    const button = form.querySelector('[type=submit]');
    const error = form.querySelector('[data-login-session-error]');
    button.disabled = true;
    error.hidden = true;
    try {
      const response = await fetch(form.action, {
        credentials: 'same-origin', cache: 'no-store',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      });
      if (response.redirected && new URL(response.url).origin === location.origin) {
        location.assign(response.url);
        return;
      }
      if (!response.ok) throw new Error('Session refresh failed');
      const data = await response.json();
      if (typeof data.token !== 'string' || !data.token) throw new Error('Missing token');
      form.querySelector('[name=_token]').value = data.token;
      button.disabled = false;
      ready = true;
      form.requestSubmit(button);
    } catch {
      error.textContent = 'ارتباط با سرور برقرار نشد. دوباره دکمه ورود را بزنید.';
      error.hidden = false;
      button.disabled = false;
    } finally {
      refreshing = false;
    }
  });
})();
