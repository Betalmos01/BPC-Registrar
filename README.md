# BPC Registrar Management System

## Setup
1. Create the database and tables:

```sql
SOURCE database/schema.sql;
```

2. Update database credentials in `config/config.php` if needed.
3. Seed initial roles and users:

```bash
php database/seed.php
```

## Default Accounts
- Administrator: `admin` / `admin123`
- Registrar Staff: `staff` / `staff123`

## Notes
- All modules require login.
- RBAC is enforced per role.
- Audit logs are recorded for key actions.
