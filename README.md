# 🏫 Rusumo High School Management System

A **production-ready, role-based school management system** built with PHP + MySQL.  
Designed for Rwandan secondary schools with support for the full academic ecosystem.

> **Author:** Hamis A. Rwemera  
> **Tech Stack:** PHP 8+, MySQL/MariaDB, HTML5, CSS3, JavaScript  
> **License:** MIT

---

## ✨ Features

### 👥 Multi-Role Access Control
| Role | Capabilities |
|------|-------------|
| **Admin** | Full system access — users, roles, all data |
| **Registrar** | Student registration, enrollment, records |
| **Director of Studies** | Academic oversight — marks for all subjects |
| **Discipline Master** | Student conduct & discipline records |
| **Bursar** | Finance — invoices, payments, fee structure |
| **Teacher** | Marks entry (scoped to assigned subject/class only) |
| **Parent** | View own children's marks, discipline, finance |

### 📊 Core Modules
- **Student Management** — registration, class assignment, promotion history
- **Academic Tracking** — per-subject marks with assessment types (exam, test, assignment, project)
- **Discipline System** — typed incidents with demerit points, case status tracking
- **Finance Module** — invoicing, payments, real-time balance computation
- **Fee Structure** — configurable fees per class per term
- **News CMS** — publish announcements to the public landing page
- **Parent Portal** — secure login, view children's performance

### 🔒 Security
- Prepared statements everywhere (no SQL injection)
- CSRF tokens on all forms
- XSS protection (`htmlspecialchars` on all output)
- File upload validation (MIME type + extension + random filenames)
- `.htaccess` blocking PHP execution in uploads directory
- Session hardening (HttpOnly, SameSite Strict, regen on login)
- Password hashing (`password_hash()` / `password_verify()`)
- Soft deletes on critical tables
- **Full audit log** with automatic triggers on marks, finance, discipline

### 🏗️ Database Design (Portfolio Highlight)
- Fully normalized to 3NF
- 32 tables, 3 computed views, 4 audit triggers
- Granular RBAC with 21 permissions across 7 groups
- Schema versioning for migrations
- Comprehensive indexing strategy including full-text search

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `mod_rewrite` (or nginx)

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/HAMIS-A-RWEMERA/EduCarta.git
cd rhs

# 2. Configure database credentials
cp config/db.sample.php config/db.php
# Edit config/db.php with your database credentials

# 3. Install the database schema
php database/install_v2.php
# Or run: mysql -u root -p < database/database_v2.sql

# 4. Seed the admin account (if install didn't do it)
php database/seed.php

# 5. Open in browser
# http://localhost/rhs/
```

### Default Credentials
| Username | Password | Role |
|----------|----------|------|
| `admin` | `Admin@1234` | Administrator |

> **⚠️ Change the default password immediately after first login!**

---

## 📁 Project Structure

```
rhs/
├── admin/                  # Staff CMS (role-based dashboards)
│   ├── dashboard.php       # Role-aware dashboard with live stats
│   ├── add-student.php     # Registrar: register new students
│   ├── edit-student.php    # Registrar: edit student details
│   ├── delete-student.php  # Soft-delete with CSRF protection
│   ├── students.php        # Student list with search
│   ├── view-students.php   # Read-only student records
│   └── manage-users.php    # Admin: create/manage staff accounts
├── api/
│   └── get_student.php     # Parent portal API (prepared statements)
├── assets/
│   └── images/             # Logos, hero images, etc.
├── auth/
│   ├── login.php           # Secure login with password_hash + role check
│   └── logout.php          # Session destroy
├── config/
│   ├── db.php              # Database connection (gitignored in production)
│   ├── db.sample.php       # Sample config template
│   └── helpers.php         # Auth helpers, CSRF, XSS escaping
├── css/
│   ├── admin.css           # Admin dashboard styles
│   └── style.css           # Public landing page styles
├── database/
│   ├── schema.sql          # Original flat schema (v1)
│   ├── database_v2.sql     # ★ New normalized schema (v2) — FULL RBAC
│   ├── seed.php            # Seed default admin account
│   └── install_v2.php      # ★ Automated installer for v2 schema
├── uploads/
│   └── students/           # Student photos (PHP execution blocked)
├── index.php               # Public landing page
└── script.js               # Frontend interactivity
```

---

## 🗄️ Database Schema (v2)

The new normalized schema replaces the flat `students` table with a proper relational design:

**32 tables** organized into 8 domains:

| Domain | Tables | Purpose |
|--------|--------|---------|
| **Auth & RBAC** | `roles`, `permissions`, `role_permissions`, `users`, `user_sessions`, `password_resets` | Secure login with granular permissions |
| **Academic** | `academic_years`, `terms`, `classes`, `subjects`, `class_subjects`, `teachers`, `teacher_subject_class` | School structure & teacher assignments |
| **Students** | `students`, `parents`, `parent_student`, `student_class_history` | Student records with parent linking |
| **Marks** | `assessment_types`, `marks` | Per-subject/per-term academic scores |
| **Discipline** | `discipline_types`, `discipline` | Incident tracking with demerit points |
| **Finance** | `fee_items`, `fee_structure`, `finance_invoices`, `finance_payments`, `payment_methods` | Invoicing & payments with computed balance |
| **Content** | `news` | Public announcements CMS |
| **System** | `schema_migrations`, `audit_log` | Versioning & immutable audit trail |

### Key Design Decisions
- **Computed balance** — `v_student_balance` view calculates `SUM(invoices) − SUM(payments)` in real-time
- **Teacher scoping** — `teacher_subject_class` table enforces what each teacher can access
- **Soft deletes** — critical records are never truly deleted (just flagged)
- **Audit triggers** — marks, finance, discipline changes are automatically logged with before/after values
- **Full-text search** — indexes on students (name) and news (title, body)

---

## 🛡️ Security Checklist

- [x] Prepared statements on all database queries
- [x] CSRF tokens on every form
- [x] `htmlspecialchars()` on all dynamic output
- [x] File upload: MIME type validation + random filenames
- [x] `.htaccess` blocks PHP in uploads directory
- [x] Session: HttpOnly, SameSite=Strict, regen on login
- [x] Passwords: `password_hash()` with default bcrypt
- [x] Errors logged server-side, generic messages shown to users
- [x] POST-only for destructive actions
- [x] Soft deletes on sensitive tables
- [x] Audit logging on marks, finance, discipline

---

## 🗺️ Development Roadmap

| Phase | Status | Description |
|-------|--------|-------------|
| **Phase 0** | ✅ Complete | Security hardening (SQLi, XSS, CSRF, upload, sessions) |
| **Phase 1** | ✅ Complete | Real auth with RBAC, prepared `helpers.php`, user management |
| **Phase 2** | 🔜 Started | Database normalization → `database_v2.sql` done, PHP code pending |
| **Phase 3** | ⬜ Pending | Bursar CMS, Discipline CMS, DoS CMS, Teacher CMS |
| **Phase 4** | ⬜ Pending | Parent portal rebuilt on real accounts |
| **Phase 5** | ⬜ Pending | News CMS, public site integration |

---

## 📈 Portfolio Value

This project demonstrates:

1. **Database Design** — Full normalization, RBAC schema, audit trails, computed views
2. **Security Best Practices** — OWASP top 10 mitigations (SQLi, XSS, CSRF, upload RCE)
3. **PHP 8+** — Prepared statements, OOP patterns, secure session handling
4. **Domain Modeling** — Real Rwandan school system (streams/combinations, fee terms, Mobile Money payments)
5. **Full-Stack** — PHP backend + HTML/CSS/JS frontend + MySQL database
6. **DevOps** — Schema migration strategy, `.gitignore`, sample config pattern

---

## 🤝 Contributing

This is a personal portfolio project, but suggestions and PRs are welcome.  
Open an issue or reach out directly.

---

## 📄 License

MIT — free to use, modify, and distribute. Attribution appreciated.

---

*Built with ❤️ for Rwandan education.*