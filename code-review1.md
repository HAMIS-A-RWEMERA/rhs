# Rusumo High School (rhs) — Code Review & Roadmap

Reviewed every file in the repo. PHP + MySQL (mysqli), plain HTML/CSS/JS, no framework.
Below: (1) what works, (2) bugs, (3) security issues — **read these before going live**,
(4) whether your vision is realistic and how to get there, (5) a concrete roadmap.

---

## 0. TL;DR

- Good news: your instinct to use PHP is fine for AOS / `.rw` shared hosting. The vision
  (public landing + news, parent portal, and role-based admin CMSs) is **100% doable** in PHP.
- The current code is a solid *learning prototype* but is **not safe to deploy as-is**.
  There are **SQL injection** and **file-upload remote-code-execution** holes that would let
  an attacker take over the site and dump student/finance data.
- The biggest structural gap vs. your vision: there is **no concept of roles or users** in the
  database (login is a hardcoded `admin`/`1234`), and the **database schema is inconsistent** —
  the parent portal reads columns (`pin`, `score`, `division_name`, `conduct`, `balance`) that
  the admin "Add Student" form never creates. So the parent portal cannot actually work yet.
- Recommendation: keep the front-end look, but **rebuild the backend around a proper schema +
  role-based access control (RBAC)** before adding the bursar / discipline / DoS / teacher CMSs.

---

## 1. What's already good

- Clean, readable landing page (`index.php`) with sensible sections (hero, about, academics,
  student life, parent portal, contact).
- `api/get_student.php` correctly uses a **prepared statement** — good habit, keep doing this everywhere.
- `add-student.php` uses `mysqli_real_escape_string` (escaping is a weaker approach than prepared
  statements, but at least it's something).
- Delete action has a JS `confirm()` and the student table UI is clear.
- Logical folder split (`admin/`, `auth/`, `api/`, `config/`).

---

## 2. Bugs (things that are broken or will break)

### B1. Broken image/asset paths everywhere — `assets/` folder doesn't exist
The repo has **no `assets/` directory**, yet files reference:
- `index.php`: `assets/logo.png`, `assets/headmaster.jpg`
- `admin/dashboard.php`, `admin/students.php`, `admin/edit-student.php`: `../assets/images/logo.png`
- `admin/add-student.php`, `admin/view-students.php`: `../assets/logo.png`

→ Every logo/hero image is a broken image right now, and the paths are **inconsistent** with each
other (`assets/logo.png` vs `assets/images/logo.png`). Pick one location (e.g. `assets/images/`)
and fix all references.

### B2. `edit-student.php` loads the wrong stylesheet
Line 61: `<link ... href="../assets/css/admin.css">` but the CSS actually lives at `css/admin.css`.
→ The edit page renders unstyled. Should be `../css/admin.css`.

### B3. `dashboard.php` logout link is wrong
Line 47: `<a href="logout.php">` resolves to `admin/logout.php`, which **does not exist** (logout is
at `auth/logout.php`). Other pages correctly use `../auth/logout.php`. → Logout from the dashboard 404s.

### B4. Malformed HTML attribute in `dashboard.php`
Line 55: `<div class="class="dashboard-title">` — duplicated/garbled `class=` attribute.
Should be `<div class="dashboard-title">`.

### B5. Schema mismatch — the parent portal can't work with admin data ★ important
- `api/get_student.php` reads: `full_name`, `class_name`, `division_name`, `score`, `conduct`,
  `balance`, and authenticates with a `pin` column.
- `admin/add-student.php` only ever writes: `student_id`, `full_name`, `class_name`, `gender`,
  `parent_phone`, `fees_balance`, `profile_photo`.
- So `pin`, `division_name`, `score`, `conduct` are **never set** by any admin screen, and the
  finance column is called `fees_balance` in admin but `balance` in the API. → A student added
  through the admin panel **cannot log into the parent portal**, and even if they could, score/
  conduct/division would be empty. This is the single most important functional gap.

### B6. No database schema / migration in the repo
There's no `.sql` file describing the `students` table (or any table). A fresh clone can't run.
You're clearly maintaining the schema by hand in phpMyAdmin. → Add a `database/schema.sql` (and
later proper migrations) so the project is reproducible and deployable.

### B7. `test.php` is a leftover debug file
It just prints "Database connected successfully." Remove it before deployment (it also leaks the
fact that a DB exists / its state).

---

## 3. Security issues (must fix before any real deployment) ★★★

This is school data — names, parents' phone numbers, marks, discipline, **finance**. Treat it as
sensitive personal data.

### S1. SQL Injection (critical)
Direct string interpolation of user input into SQL:
- `admin/delete-student.php`: `DELETE FROM students WHERE id = '$id'` with `$id = $_GET['id']`.
- `admin/edit-student.php`: `SELECT ... WHERE id = '$id'` and the whole `UPDATE ... SET ...` use
  raw `$_GET['id']` and raw `$_POST` values.
- `admin/students.php`: search uses `... LIKE '%$search%'` with raw `$_GET['search']`.

→ An attacker can read/modify/delete the entire database. **Fix: use prepared statements with
bound parameters everywhere** (like `api/get_student.php` already does). Escaping is not enough.

### S2. File-upload → Remote Code Execution (critical)
`add-student.php` saves uploads to the **web-accessible** `uploads/students/` using the user's
original filename, with **no real validation** (the `accept="image/*"` is client-side only and
trivially bypassed). An attacker who reaches that form can upload `shell.php` and then browse to
`uploads/students/shell.php` to run arbitrary code on your server.
→ Fix: validate real MIME type + extension allow-list (jpg/png/webp), generate a random filename,
ignore the user's filename, cap file size, and ideally store uploads **outside the web root** or
drop a `.htaccess`/nginx rule that forbids executing PHP in `uploads/`.

### S3. Hardcoded admin credentials, no users table
`auth/login.php` hardcodes `admin` / `1234`. No hashing, no DB, no way to add other staff.
→ This blocks your entire multi-role vision. Replace with a `users` table storing
`password_hash` (use `password_hash()` / `password_verify()`), plus a `role` column.

### S4. Plaintext parent PINs
The portal compares `pin` directly in SQL — PINs are stored in plaintext.
→ Hash them (or better, give parents real accounts with hashed passwords; see §4).

### S5. Cross-Site Scripting (XSS)
Almost all dynamic output is echoed without `htmlspecialchars()`:
- `students.php` reflects the search term back into the input `value` (reflected XSS).
- Student fields (`full_name`, etc.), `admin_username`, and the portal's JS `innerHTML`
  (`data.student.name`, …) are all unescaped. A malicious student name would execute as script.
→ Fix: `htmlspecialchars()` on every echoed value; in JS, build nodes / use `textContent` instead
of `innerHTML`.

### S6. No CSRF protection; destructive actions over GET
- No CSRF tokens on add/edit/delete forms.
- **Delete is a GET link** (`delete-student.php?id=...`). That means it can be triggered by a crawler,
  a prefetch, or a CSRF `<img>` tag — a logged-in admin could delete students just by loading a page.
→ Fix: make delete a POST form, add CSRF tokens to all state-changing requests.

### S7. Information disclosure
`add-student.php` echoes raw `mysqli_error($conn)` to the user, and `db.php` prints the connection
error. → Log errors server-side; show generic messages to users.

### S8. Session hardening
No `session_regenerate_id()` on login (session fixation), no secure/httponly/samesite cookie flags,
no idle timeout. → Harden session config and regenerate the ID at login.

### S9. DB credentials in code
`config/db.php` uses `root` with empty password and is committed. For AOS hosting, move credentials
to a config file outside the web root or environment variables, and **never commit real
credentials**. Add a `config/db.sample.php` and `.gitignore` the real one.

---

## 4. Is your vision realistic? — Yes. Here's the shape of it.

Your vision = one platform with three audiences:
1. **Public site** (anyone): landing page + **news/announcements** (a small CMS for posts).
2. **Parent portal** (parents): log in, see *their child(ren)*: performance, discipline, finance.
3. **Staff CMSs** (role-based): registrar, discipline master, bursar, director of studies,
   and later per-teacher (scoped to their subject).

This is a classic **role-based access control (RBAC)** application. The current single-`students`-
table, single-hardcoded-admin design can't express it. You need a relational schema plus a
permission layer. Below is a suggested data model and the permissions each role gets.

### 4.1 Suggested database schema (starting point)

```
users(id, username, email, password_hash, role, is_active, created_at)
    role ∈ {admin, registrar, discipline_master, bursar, director_of_studies, teacher, parent}

students(id, student_id, full_name, gender, dob, class_id, photo, status, created_at)
classes(id, name, level, stream)           -- e.g. "S5 MCB"
subjects(id, name, code)
teachers(id, user_id)                       -- staff who teach
teacher_subject_class(id, teacher_id, subject_id, class_id, term, year)  -- who teaches what

parents(id, user_id, full_name, phone, email)
parent_student(id, parent_id, student_id, relationship)  -- a parent ↔ many children

marks(id, student_id, subject_id, term, year, score, max_score, entered_by, updated_at)
discipline(id, student_id, date, type, conduct_score, observation, recorded_by)
finance_invoices(id, student_id, term, year, amount, description, created_by)
finance_payments(id, student_id, invoice_id, amount, paid_at, method, recorded_by)
    -- balance = sum(invoices) - sum(payments), computed (don't store a stale balance column)

news(id, title, slug, body, image, author_id, published_at, is_published)  -- landing-page CMS
audit_log(id, user_id, action, entity, entity_id, created_at)  -- who changed what (important for marks/finance)
```

Notes:
- Replace the single ad-hoc `students.score/conduct/balance` with proper `marks`, `discipline`,
  and `finance_*` tables. Marks are *per subject per term*, which the current flat columns can't do.
- Compute finance balance from invoices/payments rather than storing one number — avoids the
  `fees_balance`/`balance` confusion (B5) and keeps an auditable history.

### 4.2 Role → permission matrix

| Capability                              | admin | registrar | discipline | bursar | DoS | teacher | parent |
|-----------------------------------------|:----:|:---------:|:----------:|:------:|:---:|:-------:|:------:|
| Manage user accounts / roles            |  ✔   |           |            |        |     |         |        |
| Register / edit / remove students       |  ✔   |    ✔      |            |        |     |         |        |
| Edit discipline & observations          |  ✔   |           |     ✔      |        |     |         |        |
| Add / modify finance (invoices/pmts)    |  ✔   |           |            |   ✔    |     |         |        |
| Enter / edit marks (all subjects)       |  ✔   |           |            |        |  ✔  |         |        |
| Enter marks **only for own subject/class** |   |           |            |        |     |   ✔     |        |
| Publish news/announcements              |  ✔   |    ✔*     |            |        |     |         |        |
| View own child(ren): marks/discipline/fees |  |           |            |        |     |         |   ✔    |

`✔* = optional`. The teacher row is the trickiest: a teacher may only touch `marks` for a
`(subject, class)` they're assigned via `teacher_subject_class`. Enforce this **server-side** on
every write, not just by hiding buttons.

### 4.3 Architecture recommendations (keep it PHP, make it maintainable)

You don't need a heavy framework, but a little structure will save you a lot of pain:
- **Single front controller + router** (or at least a shared bootstrap) so every request goes
  through one auth/role check, instead of copy-pasting the `if(!isset($_SESSION...))` block into
  every file.
- **Separate logic from HTML**: put DB access in small functions/classes (`Student`, `Mark`,
  `Finance`…) and keep `.php` views for markup. Even a light structure beats the current
  "SQL + HTML in one file" pattern.
- **One `require_login($role)` helper** that redirects/403s based on role — central place to get
  authorization right.
- Consider **Composer** + a micro-framework later (Slim) or a full one (**Laravel**) if the project
  grows; Laravel gives you migrations, auth, RBAC, CSRF, templating, and validation out of the box
  and runs fine on `.rw` shared hosting that supports PHP 8+. If you'd rather keep it lightweight,
  staying vanilla PHP is fine — just adopt prepared statements, a router, and templating.
- Add **PDO** (or keep mysqli) but **always prepared statements**.
- Add a `.gitignore` (ignore `uploads/`, real config, `vendor/`), a `README.md` with setup steps,
  and `database/schema.sql` + seed data.

---

## 5. Suggested roadmap (incremental, each step shippable)

**Phase 0 — Stop the bleeding (security & bugs):**
1. Convert all queries to prepared statements (S1).
2. Lock down file uploads (S2). 3. Escape all output (S5). 4. CSRF + POST-only deletes (S6).
5. Fix asset paths, dashboard logout, malformed `class=` (B1–B4). Remove `test.php` (B7).
6. Add `database/schema.sql` and move DB creds out of code (B6, S9).

**Phase 1 — Real auth & roles:**
7. `users` table + `password_hash` login (S3). 8. `require_login($role)` helper + central router.
9. Replace hardcoded admin with seeded admin account.

**Phase 2 — Proper student domain:**
10. Normalize schema (classes, subjects, marks, discipline, finance, parents). Migrate existing data.
11. Rebuild registrar CMS (students CRUD) on the new schema.

**Phase 3 — The role CMSs (in your priority order):**
12. Bursar CMS (invoices/payments → computed balance).
13. Discipline master CMS (discipline/observations/conduct).
14. Director of Studies CMS (marks for all subjects).
15. Parent portal rebuilt on real accounts (children → marks/discipline/finance). Fixes B5.

**Phase 4 — Public CMS & teachers:**
16. News/announcements CMS for the landing page.
17. Per-teacher CMS scoped to assigned subject/class (teacher_subject_class enforcement).
18. Audit log on marks/finance, parent notifications (SMS via `parent_phone` is realistic in RW).

**Nice-to-haves later:** report-card PDF export, term/year management, attendance, dashboards with
real stats (the dashboard currently shows hardcoded "35 teachers / 12 news / 24 pending fees"),
2FA for staff, automated backups.

---

## 6. Quick wins I can do right now (if you want)

If you'd like, I can open a PR for **Phase 0** (security + bug fixes) without changing your design:
prepared statements, upload hardening, output escaping, CSRF, path fixes, `schema.sql`, `.gitignore`.
That makes the current app safe and reproducible, and is the right foundation before building the
multi-role CMS. Tell me which phase you want to start with.
