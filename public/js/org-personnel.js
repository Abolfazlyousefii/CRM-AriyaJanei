/* CRM Personnel Board / Phase 2. Data and writes live in Laravel; never localStorage. */
(() => {
  'use strict';

  const root = document.getElementById('org-board');
  if (!root) return;
  const $ = (id) => document.getElementById(id);
  const html = (value) => String(value ?? '').replace(/[&<>"']/g, (ch) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch]);
  const nameOf = (user) => user ? user.name : '—';
  const idOf = (v) => v == null || v === '' ? null : Number(v);
  const positionNames = {
    staff: 'کارشناس / کارمند', supervisor: 'سرپرست', department_manager: 'مدیر واحد',
    internal_manager: 'مدیر داخلی', executive_manager: 'مدیر اجرایی', ceo: 'مدیرعامل'
  };
  const state = {
    people: [], departments: [], selected: null, view: 'tree', search: '',
    department: '', role: '', collapsed: new Set(), dialog: '', editId: null,
    lastFocus: null, members: new Set(), initialMembers: [], bodyOverflow: '', company: null
  };

  const userById = (id) => state.people.find((p) => Number(p.id) === Number(id));
  const deptById = (id) => state.departments.find((d) => Number(d.id) === Number(id));
  const rolesOf = (p) => (p.roles || []).map((r) => r.name);
  const isManagerial = (p) => rolesOf(p).some((r) => r === 'Owner' || r.endsWith('Manager'));
  const enabled = (p) => p && Number(p.is_active) !== 0 && p.is_active !== false;
  const isBlocked = (p) => Boolean(p.blocked_until && new Date(p.blocked_until) > new Date());
  const active = (p) => enabled(p) && !isBlocked(p);
  const shortName = (p) => (p.name || '؟').trim().charAt(0) || '؟';
  const personDept = (p) => deptById(p.department_id);
  const position = (p) => positionNames[p.org_position] || 'بدون سمت سازمانی';

  // Phase 1 contract: explicit manager wins; department routing is fallback.
  function managerOf(p) {
    if(Object.prototype.hasOwnProperty.call(p,'effective_manager_id'))return userById(p.effective_manager_id)||null;
    if (p.manager_id != null) return userById(p.manager_id) || null;
    const d = personDept(p);
    if (!d || ['ceo', 'executive_manager', 'internal_manager'].includes(p.org_position) ||
        Number(d.parent_manager_id) === Number(p.id)) return null;
    let ids;
    if (Number(d.manager_id) === Number(p.id) || p.org_position === 'department_manager') {
      ids = [d.parent_manager_id];
    } else if (Number(d.supervisor_id) === Number(p.id) || p.org_position === 'supervisor') {
      ids = [d.manager_id, d.parent_manager_id];
    } else {
      ids = [d.supervisor_id, d.manager_id, d.parent_manager_id];
    }
    for (const id of ids) {
      if (id == null || Number(id) === Number(p.id)) continue;
      const candidate = userById(id);
      if (active(candidate)) return candidate;
    }
    return null;
  }

  function chainOf(person) {
    const path = [];
    const visited = new Set([Number(person.id)]);
    let p = person;
    for (let i = 0; i < 200; i++) {
      const manager = managerOf(p);
      if (!manager) return { path, cyclic: false };
      if (visited.has(Number(manager.id))) return { path, cyclic: true };
      visited.add(Number(manager.id));
      path.push(manager);
      p = manager;
    }
    return { path, cyclic: true };
  }

  function alertMessage(message, error = false) {
    const e = $('org2-alert');
    e.textContent = message;
    e.classList.toggle('is-error', error);
    e.hidden = false;
    e.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
  }

  async function request(url, method = 'GET', data = null) {
    const opts = {
      method,
      credentials: 'same-origin',
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    };
    if (method !== 'GET') {
      opts.headers['Content-Type'] = 'application/json';
      opts.headers['X-CSRF-TOKEN'] = root.dataset.csrf;
      opts.body = JSON.stringify(data);
    }
    let response;
    try { response = await fetch(url, opts); }
    catch (_) { throw new Error('اتصال به سرور برقرار نشد. لطفاً دوباره تلاش کنید.'); }
    if (response.status === 401 || response.status === 419) {
      throw new Error('نشست ورود منقضی شده است؛ صفحه را تازه‌سازی کنید و دوباره وارد شوید.');
    }
    const json = await response.json().catch(() => ({}));
    if (!response.ok) {
      const errors = json.errors ? Object.values(json.errors).flat().join('\n') : '';
      throw new Error(errors || json.message || 'ذخیره اطلاعات انجام نشد.');
    }
    return json;
  }

  async function load() {
    const result = await request(root.dataset.api);
    state.people = result.personnel || [];
    state.departments = result.departments || [];
    state.company = result.company || null;
    if (state.selected && !userById(state.selected)) state.selected = null;
    fillFilters();
    render();
  }

  function fillFilters() {
    const deptSelect = $('org2-dept-filter');
    const roleSelect = $('org2-role-filter');
    deptSelect.innerHTML = '<option value="">همه واحدها</option>' +
      state.departments.map((d) => '<option value="' + Number(d.id) + '">' + html(d.name) + '</option>').join('') +
      '<option value="none">بدون واحد</option>';
    const roles = [...new Set(state.people.flatMap(rolesOf))].sort();
    roleSelect.innerHTML = '<option value="">همه دسترسی‌ها</option>' +
      roles.map((name) => '<option value="' + html(name) + '">' + html(name) + '</option>').join('');
    deptSelect.value = state.department;
    roleSelect.value = state.role;
  }

  function matches(p) {
    if (state.department === 'none' && p.department_id != null) return false;
    if (state.department && state.department !== 'none' && String(p.department_id) !== state.department) return false;
    if (state.role && !rolesOf(p).includes(state.role)) return false;
    const text = [p.name, p.phone, position(p), nameOf(personDept(p)), rolesOf(p).join(' ')].join(' ').toLowerCase();
    return !state.search || text.includes(state.search);
  }

  function renderStats() {
    $('org2-stat-total').textContent = String(state.people.length);
    $('org2-stat-active').textContent = String(state.people.filter(active).length);
    $('org2-stat-depts').textContent = String(state.departments.length);
    $('org2-stat-unassigned').textContent = String(state.people.filter((p) => p.department_id == null).length);
    renderHealth();
  }

  function renderHealth() {
    const issues = state.people.map((person) => {
      const reasons = [];
      if (person.department_id == null) reasons.push('بدون واحد');
      if (!person.org_position) reasons.push('بدون سمت');
      if (person.manager_id != null && !active(userById(person.manager_id))) {
        reasons.push('مدیر مستقیم نامعتبر یا غیرفعال');
      }
      if (chainOf(person).cyclic) reasons.push('حلقه در مسیر مدیران');
      return { person, reasons };
    }).filter((item) => item.reasons.length);
    $('org2-health').hidden = !issues.length;
    $('org2-health-summary').textContent = 'بررسی ساختار سازمانی: ' + issues.length + ' نفر نیازمند بررسی';
    $('org2-health-items').innerHTML = issues.map(({ person, reasons }) =>
      '<button type="button" class="org2-chip" data-edit-person="' + Number(person.id) + '">' +
      html(person.name) + ' — ' + html(reasons.join('، ')) + '</button>'
    ).join('');
  }

  function renderUnits() {
    const hierarchy=$('org2-company-hierarchy');
    hierarchy.hidden=!state.company;
    if(state.company){
      const executive=userById(state.company.executive_manager_id),internal=userById(state.company.internal_manager_id);
      hierarchy.innerHTML='<button type="button" class="org2-lead-node" data-edit-person="'+Number(executive?.id)+'"><small>مدیر اجرایی</small><strong>'+html(executive?.name)+'</strong></button><span class="org2-connector" aria-hidden="true">↓</span><button type="button" class="org2-lead-node org2-lead-internal" data-edit-person="'+Number(internal?.id)+'"><small>مدیر داخلی</small><strong>'+html(internal?.name)+'</strong></button><span class="org2-connector" aria-hidden="true">↓ واحدهای سازمان</span>';
    }
    const company = state.people.filter((p) => ['ceo', 'executive_manager', 'internal_manager'].includes(p.org_position));
    $('org2-company-managers').innerHTML = company.length ? company.map((p) =>
      '<button type="button" class="org2-chip" data-edit-person="' + Number(p.id) + '">' +
      html(p.name) + ' · ' + html(position(p)) + (active(p) ? '' : ' · غیرفعال') + '</button>').join('') :
      '<p class="org2-help">هنوز مدیر شرکت تعیین نشده است.</p>';
    $('org2-dept-cards').innerHTML = state.departments.length ? state.departments.map((d) => {
      const people = state.people.filter((p) => Number(p.department_id) === Number(d.id));
      const leader = userById(d.supervisor_id) || userById(d.manager_id) || userById(d.parent_manager_id);
      return '<div class="org2-unit ' +
        (state.department === String(d.id) ? 'is-active' : '') + '">' +
        '<button type="button" class="org2-unit-select" data-dept-card="' + Number(d.id) + '">' +
        '<span class="org2-unit-top"><span class="org2-unit-icon">▦</span><span class="org2-unit-count">' +
        people.length + ' نفر</span></span><span><span>' + html(d.name) +
        '</span><br><small>' + (leader ? 'مسئول: ' + html(leader.name) : 'هنوز مسئول مشخص نشده') +
        '</small></span></button>' + unitMembers(d,people) +
        '<button type="button" class="org2-unit-edit" data-edit-dept="' + Number(d.id) +
        '">اعضا و مسئولیت‌های واحد ←</button></div>';
    }).join('') : '<p class="org2-empty">هنوز واحدی تعریف نشده است. از دکمه «ایجاد واحد» استفاده کنید.</p>';
  }

  function unitMembers(department,people){
    if(!people.length)return '<p class="org2-help">با انتخاب این واحد، اعضا را اضافه کنید.</p>';
    const ids=new Set(people.map((p)=>Number(p.id))),seen=new Set();
    function node(person,path=new Set()){
      if(path.has(Number(person.id)))return '';
      seen.add(Number(person.id));const next=new Set(path);next.add(Number(person.id));
      const children=people.filter((p)=>Number(managerOf(p)?.id)===Number(person.id));
      const label=Number(department.manager_id)===Number(person.id)?'مدیر واحد':Number(department.supervisor_id)===Number(person.id)?'سرپرست':'پرسنل';
      return '<li><button type="button" data-edit-person="'+Number(person.id)+'"><span>'+html(person.name)+'</span><small>'+label+'</small></button>'+
        (children.length?'<ul>'+children.map((p)=>node(p,next)).join('')+'</ul>':'')+'</li>';
    }
    let content=people.filter((p)=>!ids.has(Number(managerOf(p)?.id))).map((p)=>node(p)).join('');
    content+=people.filter((p)=>!seen.has(Number(p.id))).map((p)=>node(p)).join('');
    return '<ul class="org2-unit-people">'+content+'</ul>';
  }

  function personCard(p, childCount = 0) {
    const selected = Number(state.selected) === Number(p.id);
    return '<div class="org2-person ' + (selected ? 'is-selected' : '') + '">' +
      '<button class="org2-person-main org2-name-btn" type="button" data-person-id="' + Number(p.id) + '">' +
      '<span class="org2-avatar">' + html(shortName(p)) + '</span>' +
      '<span class="org2-person-copy"><strong>' + html(p.name) + '</strong><small>' +
      html(position(p)) + ' · ' + html(personDept(p)?.name || 'بدون واحد') +
      '</small></span></button><div class="org2-person-side"><span class="org2-indicator ' +
      (active(p) ? '' : 'off') + '">' + (active(p) ? 'فعال' : 'غیرفعال') + '</span>' +
      (childCount ? '<button type="button" class="org2-disclosure" aria-label="باز و بستن زیرمجموعه" aria-expanded="' +
        (!state.collapsed.has(Number(p.id))) + '" data-expand-id="' + Number(p.id) + '">' +
        (state.collapsed.has(Number(p.id)) ? '+' : '⌄') + '</button>' : '') +
      '</div></div>';
  }

  function renderTree() {
    const children = new Map();
    const roots = [];
    state.people.forEach((person) => {
      const manager = managerOf(person);
      if (manager && Number(manager.id) !== Number(person.id)) {
        const key = Number(manager.id);
        if (!children.has(key)) children.set(key, []);
        children.get(key).push(person);
      } else roots.push(person);
    });
    const visited = new Set();
    function node(p, recursion = new Set()) {
      if (recursion.has(Number(p.id))) return '';
      const descendants = children.get(Number(p.id)) || [];
      const next = new Set(recursion);
      next.add(Number(p.id));
      const descendantsHtml = descendants.map((child) => node(child, next)).filter(Boolean).join('');
      if (!matches(p) && !descendantsHtml) return '';
      visited.add(Number(p.id));
      const visibleChildren = state.collapsed.has(Number(p.id)) ? '' : descendantsHtml;
      return '<li class="org2-tree-item">' + personCard(p, descendants.length) +
        (visibleChildren ? '<ul class="org2-tree-list">' + visibleChildren + '</ul>' : '') + '</li>';
    }
    let tree = roots.map((p) => node(p)).filter(Boolean).join('');
    // Cycles in existing legacy data must not hide personnel silently.
    const orphans = state.people.filter((p) => !visited.has(Number(p.id)) && matches(p));
    if (orphans.length) {
      tree += '<li class="org2-tree-item"><p class="org2-empty">موارد مستقل یا نیازمند بازبینی ارتباط مدیر</p>' +
        orphans.map((p) => personCard(p)).join('') + '</li>';
    }
    $('org2-tree').innerHTML = tree
      ? '<ul class="org2-tree-list">' + tree + '</ul>'
      : '<p class="org2-empty">شخصی با این فیلترها پیدا نشد.</p>';
  }

  function renderList() {
    const people = state.people.filter(matches);
    $('org2-list').innerHTML = people.length
      ? '<table class="org2-listing"><thead><tr><th>نام</th><th>واحد</th><th>سمت سازمانی</th><th>دسترسی نرم‌افزار</th><th>وضعیت</th></tr></thead><tbody>' +
        people.map((p) => '<tr><td><button type="button" class="org2-name-btn" data-person-id="' +
          Number(p.id) + '">' + html(p.name) + '</button></td><td>' + html(personDept(p)?.name || '—') +
          '</td><td>' + html(position(p)) + '</td><td>' + html(rolesOf(p).join('، ') || '—') +
          '</td><td>' + (active(p) ? 'فعال' : isBlocked(p) ? 'مسدود' : 'غیرفعال') + '</td></tr>').join('') +
        '</tbody></table>'
      : '<p class="org2-empty">شخصی با این فیلترها پیدا نشد.</p>';
  }

  function postForm(action, label, danger = false, method = 'POST', extras = {}) {
    const fields = { _token: root.dataset.csrf, ...extras };
    const h = Object.entries(fields).map(([k, v]) => '<input type="hidden" name="' + html(k) +
      '" value="' + html(v) + '">').join('');
    return '<form action="' + html(action) + '" method="POST" data-confirm="' +
      (danger ? 'آیا از این عملیات مطمئن هستید؟' : '') + '">' +
      h + (method !== 'POST' ? '<input type="hidden" name="_method" value="' + html(method) + '">' : '') +
      '<button type="submit" class="org2-btn ' + (danger ? 'org2-btn-danger' : 'org2-btn-muted') +
      '">' + html(label) + '</button></form>';
  }

  function renderDetail() {
    const p = userById(state.selected);
    if (!p) {
      $('org2-detail-body').innerHTML = '<p class="org2-empty">برای مشاهده مشخصات، یک نفر را از چارت یا فهرست انتخاب کنید.</p>';
      return;
    }
    const chain = chainOf(p);
    const r = rolesOf(p);
    const userBase = root.dataset.editManagerUrl + '/' + Number(p.id);
    const editHref = userBase + (isManagerial(p) ? '/edit-manager' : '/edit-employee');
    const isSelf = Number(p.id) === Number(root.dataset.currentUser);
    const status = active(p) ? 'فعال' : isBlocked(p) ? 'مسدود' : 'غیرفعال';
    const companyPath = chain.path.map((manager) => '<li>' + html(manager.name) + '</li>').join('');
    const actionUrl = root.dataset.activateUrl + '/' + Number(p.id);
    const buttons =
      '<button class="org2-btn org2-btn-primary" type="button" data-edit-person="' + Number(p.id) +
      '">ویرایش اطلاعات و جایگاه سازمانی</button>' +
      '<a class="org2-btn org2-btn-muted" href="' + html(editHref) + '">ویرایش اطلاعات ورود و Role</a>' +
      (!isSelf ? (enabled(p)
        ? postForm(actionUrl + '/deactivate', 'غیرفعال‌کردن حساب', true)
        : postForm(actionUrl + '/activate', 'فعال‌کردن حساب')) +
        (isBlocked(p)
          ? postForm(actionUrl + '/unblock', 'رفع مسدودیت')
          : postForm(actionUrl + '/block', 'مسدودسازی ۲۴ ساعته', true, 'POST', { hours: 24 })) +
        postForm(userBase + (isManagerial(p) ? '/delete-manager' : '/delete-employee'),
          'آرشیو حساب و حفظ سابقه', true, 'DELETE')
        : '');
    $('org2-detail-body').innerHTML =
      '<div class="org2-profile"><span class="org2-avatar">' + html(shortName(p)) +
      '</span><div><h4>' + html(p.name) + '</h4><small>' + html(position(p)) + '</small></div></div>' +
      '<dl class="org2-meta">' +
      '<div><dt>وضعیت</dt><dd>' + status + '</dd></div>' +
      '<div><dt>شماره تماس</dt><dd dir="ltr">' + html(p.phone || '—') + '</dd></div>' +
      '<div><dt>واحد</dt><dd>' + html(personDept(p)?.name || 'نامشخص') + '</dd></div>' +
      '<div><dt>مدیر مستقیم</dt><dd>' + html(nameOf(managerOf(p))) +
      (p.manager_id == null ? ' <small>(خودکار)</small>' : ' <small>(صریح)</small>') + '</dd></div>' +
      '<div><dt>Role</dt><dd class="org2-roles">' + (r.length ? r.map((role) => '<span class="org2-tag">' +
      html(role) + '</span>').join('') : '—') + '</dd></div></dl>' +
      '<h4 class="org2-track-title">مسیر گزارش‌دهی بالادستی</h4>' +
      (chain.path.length ? '<ul class="org2-track">' + companyPath + '</ul>' :
        '<p class="org2-help">برای این شخص مسیر بالادستی تعیین نشده است.</p>') +
      (chain.cyclic ? '<p class="org2-form-error">در ارتباط مدیران این شخص حلقه شناسایی شد. لطفاً ساختار را اصلاح کنید.</p>' : '') +
      '<div class="org2-detail-buttons">' + buttons + '</div>';
  }

  function render() {
    renderStats();
    renderUnits();
    $('org2-tree').hidden = state.view !== 'tree';
    $('org2-list').hidden = state.view !== 'list';
    root.querySelectorAll('[data-view]').forEach((b) => {
      const chosen = b.dataset.view === state.view;
      b.classList.toggle('is-current', chosen);
      b.setAttribute('aria-pressed', String(chosen));
    });
    renderTree();
    renderList();
    renderDetail();
  }

  function selectPerson(id) {
    state.selected = Number(id);
    renderDetail();
    root.querySelectorAll('.org2-person').forEach((node) => {
      const button = node.querySelector('[data-person-id]');
      node.classList.toggle('is-selected', button && Number(button.dataset.personId) === state.selected);
    });
  }

  function options(people, selected, firstLabel) {
    return '<option value="">' + html(firstLabel) + '</option>' +
      people.map((p) => '<option value="' + Number(p.id) + '"' +
        (idOf(selected) === Number(p.id) ? ' selected' : '') + '>' + html(p.name) +
        (p.org_position ? ' — ' + html(position(p)) : '') + '</option>').join('');
  }

  function openDialog(type, id = null) {
    state.lastFocus = document.activeElement;
    state.bodyOverflow = document.body.style.overflow;
    state.dialog = type;
    state.editId = id;
    const deptForm = $('org2-edit-dept');
    const personForm = $('org2-edit-person');
    $('org2-form-error').hidden = true;
    deptForm.hidden = type !== 'department';
    personForm.hidden = type !== 'person';
    $('org2-edit-company').hidden = type !== 'company';

    if (type === 'company') {
      $('org2-dialog-title').textContent = 'تعیین مدیر در سطح کل شرکت';
      $('org2-company-person').innerHTML = options(state.people.filter(active), null, 'انتخاب شخص');
      fillCompanyManagers();
    } else if (type === 'person') {
      const p = userById(id);
      if (!p) return;
      $('org2-dialog-title').textContent = 'ویرایش جایگاه: ' + p.name;
      $('org2-person-name').value = p.name;
      $('org2-person-dept').innerHTML = '<option value="">بدون واحد</option>' +
        state.departments.map((d) => '<option value="' + Number(d.id) + '">' + html(d.name) + '</option>').join('');
      $('org2-person-dept').value = String(p.department_id ?? '');
      $('org2-person-position').value = p.org_position || '';
      const eligible = state.people.filter((u) => u.id !== p.id && active(u) &&
        (isManagerial(u) || ['supervisor', 'department_manager', 'internal_manager', 'executive_manager', 'ceo'].includes(u.org_position)));
      // Keep an existing legacy direct manager selectable even if roles changed.
      if (p.manager_id && !eligible.some((e) => Number(e.id) === Number(p.manager_id))) {
        const prev = userById(p.manager_id);
        if (prev) eligible.push(prev);
      }
      $('org2-person-manager').innerHTML = options(eligible, p.manager_id, 'تبعیت از ساختار واحد (خودکار)');
      $('org2-person-manager').value = String(p.manager_id ?? '');
    } else {
      const d = deptById(id);
      $('org2-dialog-title').textContent = d ? 'ویرایش واحد: ' + d.name : 'ایجاد واحد سازمانی';
      $('org2-dept-id').value = d?.id || '';
      $('org2-dept-name').value = d?.name || '';
      state.initialMembers = state.people.filter((p) => d && Number(p.department_id) === Number(d.id)).map((p) => Number(p.id));
      state.members = new Set(state.initialMembers);
      $('org2-member-search').value = '';
      $('org2-unit-routing').checked = false;
      for (const [field, selected] of [
        ['supervisor', d?.supervisor_id], ['manager', d?.manager_id]
      ]) {
        $( 'org2-dept-' + field ).innerHTML = options(state.people.filter((p) => state.members.has(Number(p.id)) && active(p)), selected, 'بدون ' + (field === 'manager' ? 'مدیر واحد' : 'سرپرست'));
      }
      renderMembers();
    }
    $('org2-dialog-backdrop').hidden = false;
    document.body.style.overflow = 'hidden';
    const target = type === 'department' ? $('org2-dept-name') : type === 'company' ? $('org2-company-person') : $('org2-person-dept');
    target.focus();
  }

  function renderMembers() {
    const query = $('org2-member-search').value.trim().toLowerCase();
    const people = state.people.filter((p) => (p.name + ' ' + p.phone).toLowerCase().includes(query));
    $('org2-members').innerHTML = people.length ? people.map((p) =>
      '<label class="org2-member"><input type="checkbox" data-member-id="' + Number(p.id) + '"' +
      (state.members.has(Number(p.id)) ? ' checked' : '') + '><span><strong>' + html(p.name) +
      '</strong><small>' + html(personDept(p)?.name || 'بدون واحد') + (active(p) ? '' : ' · غیرفعال') +
      '</small></span></label>').join('') : '<p class="org2-help">کاربری پیدا نشد.</p>';
    $('org2-member-count').textContent = state.members.size + ' عضو انتخاب شده';
    const added = state.people.filter((p) => state.members.has(Number(p.id)) && !state.initialMembers.includes(Number(p.id)));
    const removed = state.people.filter((p) => !state.members.has(Number(p.id)) && state.initialMembers.includes(Number(p.id)));
    $('org2-members-preview').textContent = [
      added.length ? 'افزودن / انتقال به واحد: ' + added.map((p) => p.name).join('، ') : '',
      removed.length ? 'خروج از واحد: ' + removed.map((p) => p.name).join('، ') : ''
    ].filter(Boolean).join(' | ') || 'عضویت افراد تغییری نمی‌کند.';
    for (const field of ['supervisor', 'manager']) {
      const select = $('org2-dept-' + field);
      const selected = select.value;
      select.innerHTML = options(state.people.filter((p) => state.members.has(Number(p.id)) && active(p)), selected,
        field === 'manager' ? 'بدون مدیر واحد' : 'بدون سرپرست');
    }
  }

  function fillCompanyManagers() {
    const subject = idOf($('org2-company-person').value);
    $('org2-company-manager').innerHTML = options(state.people.filter((p) => Number(p.id) !== subject && active(p) &&
      ['ceo', 'executive_manager', 'internal_manager'].includes(p.org_position)), null, 'بدون مدیر بالادستی (رأس چارت)');
  }

  function closeDialog() {
    $('org2-dialog-backdrop').hidden = true;
    document.body.style.overflow = state.bodyOverflow;
    state.dialog = '';
    state.editId = null;
    state.lastFocus?.focus?.();
  }

  function formError(error) {
    $('org2-form-error').textContent = error.message || String(error);
    $('org2-form-error').hidden = false;
  }

  async function saveForm(form, url, method, payload) {
    const button = form.querySelector('button[type=submit]');
    button.disabled = true;
    try {
      await request(url, method, payload);
      closeDialog();
      await load();
      alertMessage('تغییرات با موفقیت ذخیره شدند.');
    } catch (err) { formError(err); }
    finally { button.disabled = false; }
  }

  $('org2-add-dept').addEventListener('click', () => openDialog('department'));
  $('org2-add-company-manager').addEventListener('click', () => openDialog('company'));
  $('org2-company-person').addEventListener('change', fillCompanyManagers);
  $('org2-member-search').addEventListener('input', renderMembers);
  $('org2-members').addEventListener('change', (event) => {
    const input = event.target.closest('[data-member-id]');
    if (!input) return;
    const id = Number(input.dataset.memberId);
    if (input.checked) state.members.add(id); else state.members.delete(id);
    renderMembers();
    $('org2-members').querySelector('[data-member-id="' + id + '"]')?.focus();
  });
  $('org2-select-unassigned').addEventListener('click', () => {
    const query = $('org2-member-search').value.trim().toLowerCase();
    state.people.filter((p) => p.department_id == null && (p.name + ' ' + p.phone).toLowerCase().includes(query))
      .forEach((p) => state.members.add(Number(p.id)));
    renderMembers();
  });
  $('org2-manage-depts').addEventListener('click', () => openDialog('department', state.department && state.department !== 'none' ? Number(state.department) : null));
  $('org2-close').addEventListener('click', closeDialog);
  $('org2-dialog-backdrop').addEventListener('click', (event) => {
    if (event.target.id === 'org2-dialog-backdrop') closeDialog();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && state.dialog) closeDialog();
    if (event.key === 'Tab' && state.dialog) {
      const fields = [...$('org2-dialog-backdrop').querySelectorAll('button, input, select, a[href]')]
        .filter((node) => !node.disabled && node.type !== 'hidden' && node.getClientRects().length);
      const first = fields[0], last = fields[fields.length - 1];
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
    }
  });
  root.addEventListener('click', (event) => {
    const edit = event.target.closest('[data-edit-person]');
    if (edit) return openDialog('person', Number(edit.dataset.editPerson));
    const person = event.target.closest('[data-person-id]');
    if (person) return selectPerson(person.dataset.personId);
    const editUnit = event.target.closest('[data-edit-dept]');
    if (editUnit) return openDialog('department', Number(editUnit.dataset.editDept));
    const dept = event.target.closest('[data-dept-card]');
    if (dept) {
      return openDialog('department', Number(dept.dataset.deptCard));
    }
    const expand = event.target.closest('[data-expand-id]');
    if (expand) {
      const id = Number(expand.dataset.expandId);
      if (state.collapsed.has(id)) state.collapsed.delete(id);
      else state.collapsed.add(id);
      return renderTree();
    }
    const tab = event.target.closest('[data-view]');
    if (tab) {
      state.view = tab.dataset.view;
      render();
    }
  });
  $('org2-search').addEventListener('input', (e) => {
    state.search = e.target.value.trim().toLowerCase();
    render();
  });
  $('org2-dept-filter').addEventListener('change', (e) => {
    state.department = e.target.value;
    render();
  });
  $('org2-role-filter').addEventListener('change', (e) => {
    state.role = e.target.value;
    render();
  });
  $('org2-filter-form').addEventListener('reset', (e) => {
    e.preventDefault();
    state.search = '';
    state.department = '';
    state.role = '';
    $('org2-search').value = '';
    $('org2-dept-filter').value = '';
    $('org2-role-filter').value = '';
    render();
  });
  $('org2-edit-person').addEventListener('submit', (event) => {
    event.preventDefault();
    if (!state.editId) return;
    const id = Number(state.editId);
    saveForm(event.currentTarget, root.dataset.peopleUrl + '/' + id, 'PATCH', {
      department_id: idOf($('org2-person-dept').value),
      org_position: $('org2-person-position').value || null,
      manager_id: idOf($('org2-person-manager').value)
    });
  });
  $('org2-edit-dept').addEventListener('submit', (event) => {
    event.preventDefault();
    const id = idOf($('org2-dept-id').value);
    if (!window.confirm($('org2-members-preview').textContent +
      ($('org2-unit-routing').checked ? '\nمدیر مستقیم اعضای انتخاب‌شده پاک می‌شود تا از ساختار واحد تبعیت کنند.' : '') + '\nتغییرات ذخیره شود؟')) return;
    saveForm(event.currentTarget, root.dataset.departmentsUrl + (id ? '/' + id : ''),
      id ? 'PATCH' : 'POST', {
        name: $('org2-dept-name').value.trim(),
        supervisor_id: idOf($('org2-dept-supervisor').value),
        manager_id: idOf($('org2-dept-manager').value),
        member_ids: [...state.members],
        expected_member_ids: state.initialMembers,
        use_unit_routing: $('org2-unit-routing').checked
      });
  });
  $('org2-edit-company').addEventListener('submit', (event) => {
    event.preventDefault();
    const id = idOf($('org2-company-person').value);
    if (!id) return;
    saveForm(event.currentTarget, root.dataset.peopleUrl + '/' + id, 'PATCH', {
      org_position: $('org2-company-position').value,
      manager_id: idOf($('org2-company-manager').value)
    });
  });
  root.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-confirm]');
    if (form && form.dataset.confirm && !window.confirm(form.dataset.confirm)) event.preventDefault();
  });
  // Initial state always comes from Laravel, never the sample prototype storage.
  load().catch((err) => {
    alertMessage(err.message, true);
    $('org2-tree').innerHTML = '<p class="org2-empty">دریافت داده‌های سازمانی ناموفق بود. صفحه را تازه‌سازی کنید.</p>';
  });
})();
