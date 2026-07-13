# Taskvel Admin Panel

Role-gated admin area for taskvel-php, styled to match the Samal Consultancy
brand (gold / navy / teal glassmorphism, Space Grotesk + Inter).

## What's inside

```
sql/migration_10_admin.sql   role column + events/enquiries tables
includes/admin.php           require_admin() gate + shared helpers
api/enquiry.php              public form endpoint → admin inbox
admin/
  _layout.php                shared sidebar/topbar + full design system
  index.php                  dashboard — KPIs, 30-day signup sparkline, activity
  users.php                  search/filter users, enable/disable, promote/demote, detail view
  events.php                 events list, inline status change, delete
  event-form.php             add/edit event (gallery images, hotels, categories)
  enquiries.php              inbox for public form submissions, status workflow
  audit.php                  filterable security audit log with event chips
```

## Install (5 steps)

1. Copy `admin/`, `includes/admin.php`, and `api/enquiry.php` into the repo root
   (paths assume `admin/` sits next to `includes/` and `api/`).
2. Run the migration:
   ```
   mysql -u USER -p taskvel_php < sql/migration_10_admin.sql
   ```
3. Promote yourself:
   ```sql
   UPDATE users SET role = 'admin' WHERE email = 'your@email.com';
   ```
4. Open `/admin/` — anyone without `role = 'admin'` gets a 403 and an
   `admin_access_denied` audit entry.
5. **Retire the old pages**: `events-admin.php` and `events-admin-form.php`
   currently let ANY logged-in user manage events. Delete them or replace
   their auth check with:
   ```php
   require_once __DIR__ . '/includes/admin.php';
   require_admin();
   ```

## Wiring the public enquiry form

On `event.php` / your contact section:

```html
<form id="enq">
  <input name="name" required placeholder="Your name">
  <input name="email" type="email" required placeholder="Email">
  <input name="phone" placeholder="Phone (optional)">
  <textarea name="message" placeholder="Message"></textarea>
  <input name="website" style="display:none" tabindex="-1" autocomplete="off"> <!-- honeypot -->
  <button>Send enquiry</button>
</form>
<script>
document.getElementById('enq').addEventListener('submit', async e => {
  e.preventDefault();
  const data = Object.fromEntries(new FormData(e.target));
  data.event_id = <?= (int)$event['id'] ?>; // 0 / omit for general contact
  const res = await fetch('api/enquiry.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data),
  });
  const json = await res.json();
  alert(json.message || json.error);
  if (res.ok) e.target.reset();
});
</script>
```

## Security model

- Every page: `require_admin()` first line — session auth + role check + audit on denial.
- Every state change: CSRF enforced (`X-CSRF-Token` header via the shared `post()`
  helper, or hidden `csrf_token` field on the event form).
- Every query: PDO prepared statements; enums whitelisted via `one_of()`.
- Every admin action: written to `security_audit_log` (`admin_*` events),
  visible in the Audit log page.
- Guardrails: can't disable/demote your own account; can't demote the last
  active admin (prevents locking everyone out).
- Public enquiry endpoint: per-IP rate limit + honeypot + strict validation.
