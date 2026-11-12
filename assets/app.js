/* Expense Tracker front end: validation, fetch (AJAX/JSON) helpers and per-page logic. */
const $ = (s, r = document) => r.querySelector(s);
const CSRF = document.querySelector('meta[name=csrf]')?.content || '';
const inr = (n, d = 0) => '₹' + Number(n).toLocaleString('en-IN', { minimumFractionDigits: d, maximumFractionDigits: d });
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const today = () => new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10);
const PALETTE = ['#0F4C5C', '#2A9D8F', '#E9C46A', '#E76F51', '#8AB17D', '#6D597A', '#B8C9CE', '#457B9D'];

function toast(msg, err = false) {
  const t = $('#toast'); t.textContent = msg; t.className = 'toast show' + (err ? ' err' : '');
  clearTimeout(toast.t); toast.t = setTimeout(() => t.className = 'toast', 2600);
}

/** fetch wrapper: JSON in/out, CSRF header, readable errors. */
async function api(url, method = 'GET', data) {
  const opt = { method, headers: { 'X-CSRF-Token': CSRF } };
  if (data) { opt.headers['Content-Type'] = 'application/json'; opt.body = JSON.stringify(data); }
  let res;
  try { res = await fetch(url, opt); } catch { throw new Error('Network error. Check your connection.'); }
  if (res.status === 401) { location.href = 'login.php'; throw new Error('Please log in.'); }
  const json = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(json.error || 'Something went wrong.');
  return json;
}

/** Client-side validation: marks invalid fields, returns an error message or ''. */
function validate(form, rules) {
  form.querySelectorAll('.invalid').forEach(el => el.classList.remove('invalid'));
  for (const [name, fn] of Object.entries(rules)) {
    const el = form.elements[name], msg = fn(el.value.trim(), form);
    if (msg) { el.classList.add('invalid'); el.focus(); return msg; }
  }
  return '';
}
const R = {
  req: label => v => v ? '' : `${label} is required.`,
  amount: v => (!v || isNaN(v) || +v <= 0) ? 'Enter an amount greater than 0.' : '',
  date: v => !v ? 'Pick a date.' : (v > today() ? 'Date cannot be in the future.' : ''),
  email: v => /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v) ? '' : 'Enter a valid email address.',
  budget: v => (v === '' || (!isNaN(v) && +v >= 0)) ? '' : 'Budget must be zero or more.'
};

/* ---------- Login / Register form checks (server re-validates) ---------- */
document.querySelectorAll('form[data-validate]').forEach(f => f.addEventListener('submit', e => {
  const kind = f.dataset.validate, out = f.querySelector('.form-error');
  const rules = kind === 'login'
    ? { email: R.email, password: R.req('Password') }
    : { name: R.req('Name'), email: R.email, password: v => v.length >= 8 ? '' : 'Password must be at least 8 characters.',
        confirm: (v, fm) => v === fm.elements.password.value ? '' : 'Passwords do not match.' };
  const msg = validate(f, rules);
  if (msg) { e.preventDefault(); out.textContent = msg; }
}));

/* ---------- shared: charts ---------- */
const charts = {};
function draw(id, config) { charts[id]?.destroy(); charts[id] = new Chart($('#' + id), config); }
const baseOpts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } };
const moneyTick = { ticks: { callback: v => '₹' + Number(v).toLocaleString('en-IN') }, grid: { color: '#E6F0F2' } };

function renderInsights(list) {
  $('#insights').innerHTML = list.map(i => `<li class="${esc(i.type)}">${esc(i.text)}</li>`).join('');
}

const pages = {
  /* ================= Dashboard ================= */
  async dashboard() {
    try {
      const [s, p] = await Promise.all([api('api/summary.php'), api('api/predict.php')]);
      $('#st-total').textContent = inr(s.month_total); $('#st-month').textContent = s.month_label;
      $('#st-top').textContent = s.top_category || 'None yet';
      const f = p.forecast;
      $('#st-fc').textContent = f.moving_avg != null ? inr(f.moving_avg) : 'Not enough data';
      $('#st-fc-sub').textContent = f.target_label ? 'for ' + f.target_label : f.message;
      renderInsights(p.insights.slice(0, 5));

      $('#e-pie').hidden = s.categories.length > 0;
      draw('c-pie', { type: 'pie', data: { labels: s.categories.map(c => c.name), datasets: [{ data: s.categories.map(c => c.total), backgroundColor: PALETTE, borderColor: '#fff', borderWidth: 2 }] },
        options: { ...baseOpts, plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: c => ` ${c.label}: ${inr(c.parsed)}` } } } } });
      draw('c-line', { type: 'line', data: { labels: s.trend.labels, datasets: [{ label: 'Total spend', data: s.trend.values, borderColor: '#0F4C5C', backgroundColor: 'rgba(15,76,92,.12)', fill: true, tension: .3, pointRadius: 4 }] },
        options: { ...baseOpts, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ...moneyTick }, x: { grid: { display: false } } } } });
      $('#e-bar').hidden = s.budget.length > 0;
      draw('c-bar', { type: 'bar', data: { labels: s.budget.map(b => b.name), datasets: [
          { label: 'Budget', data: s.budget.map(b => b.budget), backgroundColor: '#B8C9CE', borderRadius: 6 },
          { label: 'Spent', data: s.budget.map(b => b.spent), backgroundColor: s.budget.map(b => b.spent > b.budget ? '#C0392B' : '#0F4C5C'), borderRadius: 6 }] },
        options: { ...baseOpts, scales: { y: { beginAtZero: true, ...moneyTick }, x: { grid: { display: false } } } } });
    } catch (e) { toast(e.message, true); }
  },

  /* ================= Expenses ================= */
  async expenses() {
    const form = $('#exp-form'), dlg = $('#edit-dlg'), eform = $('#edit-form'); let cats = [], editId = null;
    const rules = { amount: R.amount, category_id: R.req('Category'), expense_date: R.date };
    const fill = sel => sel.innerHTML = cats.map(c => `<option value="${c.category_id}">${esc(c.name)}</option>`).join('');
    const load = async () => {
      try {
        const m = $('#f-month').value, rows = await api('api/expenses.php' + (m ? '?month=' + m : ''));
        $('#exp-empty').hidden = rows.length > 0;
        $('#exp-body').innerHTML = rows.map(r => `<tr><td>${esc(r.expense_date)}</td><td>${esc(r.category)}</td><td>${esc(r.note || '')}</td>
          <td class="num">${inr(r.amount, 2)}</td><td class="actions"><button class="btn btn-ghost sm" data-edit="${r.expense_id}">Edit</button><button class="btn btn-danger sm" data-del="${r.expense_id}">Delete</button></td></tr>`).join('');
        $('#exp-body').rows_data = Object.fromEntries(rows.map(r => [r.expense_id, r]));
      } catch (e) { toast(e.message, true); }
    };
    try { cats = await api('api/categories.php'); } catch (e) { return toast(e.message, true); }
    fill(form.elements.category_id); fill(eform.elements.category_id);
    form.elements.expense_date.value = today(); form.elements.expense_date.max = today(); eform.elements.expense_date.max = today();
    form.addEventListener('submit', async e => {
      e.preventDefault(); const err = $('#exp-error'), msg = validate(form, rules); err.textContent = msg; if (msg) return;
      try {
        await api('api/expenses.php', 'POST', Object.fromEntries(new FormData(form)));
        toast('Expense added'); form.elements.amount.value = ''; form.elements.note.value = ''; load();
      } catch (ex) { err.textContent = ex.message; }
    });
    $('#f-month').addEventListener('change', load);
    $('#exp-body').addEventListener('click', async e => {
      const ed = e.target.dataset.edit, del = e.target.dataset.del;
      if (ed) { const r = $('#exp-body').rows_data[ed]; editId = ed; for (const k of ['amount', 'category_id', 'expense_date', 'note']) eform.elements[k].value = r[k] ?? ''; $('#edit-error').textContent = ''; dlg.showModal(); }
      if (del && confirm('Delete this expense?')) { try { await api('api/expenses.php?id=' + del, 'DELETE'); toast('Expense deleted'); load(); } catch (ex) { toast(ex.message, true); } }
    });
    eform.addEventListener('submit', async e => {
      e.preventDefault(); const err = $('#edit-error'), msg = validate(eform, rules); err.textContent = msg; if (msg) return;
      try { await api('api/expenses.php?id=' + editId, 'PUT', Object.fromEntries(new FormData(eform))); dlg.close(); toast('Expense updated'); load(); }
      catch (ex) { err.textContent = ex.message; }
    });
    dlg.querySelector('[data-close]').onclick = () => dlg.close();
    load();
  },

  /* ================= Categories ================= */
  async categories() {
    const form = $('#cat-form'), dlg = $('#edit-dlg'), eform = $('#edit-form'); let rows = [], editId = null;
    const rules = { name: R.req('Name'), budget_limit: R.budget };
    const load = async () => {
      try {
        rows = await api('api/categories.php');
        $('#cat-body').innerHTML = rows.map(r => {
          const b = +r.budget_limit, s = +r.spent, pct = b > 0 ? Math.min(100, s / b * 100) : 0, cls = b > 0 && s > b ? 'over' : (b > 0 && s >= .8 * b ? 'warn' : '');
          return `<tr><td>${esc(r.name)}</td><td class="num">${b > 0 ? inr(b) : '<span class="tiny">No budget</span>'}</td>
            <td>${inr(s)}${b > 0 ? `<div class="bar ${cls}"><i style="width:${pct}%"></i></div><span class="tiny">${Math.round(s / b * 100)}% of budget</span>` : ''}</td>
            <td class="actions"><button class="btn btn-ghost sm" data-edit="${r.category_id}">Edit</button><button class="btn btn-danger sm" data-del="${r.category_id}">Delete</button></td></tr>`;
        }).join('');
      } catch (e) { toast(e.message, true); }
    };
    form.addEventListener('submit', async e => {
      e.preventDefault(); const err = $('#cat-error'), msg = validate(form, rules); err.textContent = msg; if (msg) return;
      try { await api('api/categories.php', 'POST', Object.fromEntries(new FormData(form))); toast('Category added'); form.reset(); load(); }
      catch (ex) { err.textContent = ex.message; }
    });
    $('#cat-body').addEventListener('click', async e => {
      const ed = e.target.dataset.edit, del = e.target.dataset.del;
      if (ed) { const r = rows.find(x => x.category_id == ed); editId = ed; eform.elements.name.value = r.name; eform.elements.budget_limit.value = r.budget_limit ?? ''; $('#edit-error').textContent = ''; dlg.showModal(); }
      if (del && confirm('Delete this category?')) { try { await api('api/categories.php?id=' + del, 'DELETE'); toast('Category deleted'); load(); } catch (ex) { toast(ex.message, true); } }
    });
    eform.addEventListener('submit', async e => {
      e.preventDefault(); const err = $('#edit-error'), msg = validate(eform, rules); err.textContent = msg; if (msg) return;
      try { await api('api/categories.php?id=' + editId, 'PUT', Object.fromEntries(new FormData(eform))); dlg.close(); toast('Category updated'); load(); }
      catch (ex) { err.textContent = ex.message; }
    });
    dlg.querySelector('[data-close]').onclick = () => dlg.close();
    load();
  },

  /* ================= Prediction & Insights ================= */
  async insights() {
    try {
      const { forecast: f, insights } = await api('api/predict.php');
      renderInsights(insights);
      $('#fc-note').textContent = f.message || `Forecast for ${f.target_label}, based on ${f.n} completed month${f.n > 1 ? 's' : ''} of spending.`;
      $('#fc-ma').textContent = f.moving_avg != null ? inr(f.moving_avg) : 'Not enough data';
      $('#fc-ma-sub').textContent = f.moving_avg != null ? `Average of the last ${f.ma_months} month${f.ma_months > 1 ? 's' : ''}` : '';
      $('#fc-lr').textContent = f.regression != null ? inr(f.regression) : 'Needs 2+ months';
      $('#fc-lr-sub').textContent = f.slope != null ? `y = ${f.slope}·x + ${f.intercept}  (x = month number, next = ${f.n + 1})` : 'y = m·x + b';
      if (!f.n) return;
      const labels = [...f.months.map(m => new Date(m + '-01').toLocaleString('en', { month: 'short', year: 'numeric' })), f.target_label];
      const pad = Array(f.n).fill(null), last = f.totals[f.n - 1];
      const ds = [{ label: 'Actual', data: [...f.totals, null], borderColor: '#0F4C5C', backgroundColor: '#0F4C5C', tension: .25, pointRadius: 4 },
        { label: 'Moving average', data: [...pad.slice(0, -1), last, f.moving_avg], borderColor: '#2A9D8F', borderDash: [6, 4], pointStyle: 'rectRot', backgroundColor: '#2A9D8F' }];
      ds[1].pointRadius = ctx => ctx.dataIndex === f.n ? 7 : 0;
      if (f.regression != null) { ds.push({ label: 'Linear regression', data: [...pad.slice(0, -1), last, f.regression], borderColor: '#E76F51', borderDash: [2, 4], backgroundColor: '#E76F51', pointStyle: 'triangle' }); ds[2].pointRadius = ctx => ctx.dataIndex === f.n ? 7 : 0; }
      draw('c-fc', { type: 'line', data: { labels, datasets: ds }, options: { ...baseOpts, scales: { y: { beginAtZero: true, ...moneyTick }, x: { grid: { display: false } } } } });
    } catch (e) { toast(e.message, true); }
  }
};
pages[document.body.dataset.page]?.();
