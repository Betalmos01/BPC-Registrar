# BPC Registrar Management System

github repo

  https://github.com/Betalmos01/BPC-Registrar.git
  
## Localhost Setup (XAMPP)
1. Copy or clone this project into your XAMPP web root:
   - `C:\xampp\htdocs\BPC-Registrar`

2. Start Apache and MySQL in the XAMPP Control Panel.

3. Create the database and tables:
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create a database named `bpc_registrar`
   - Import `database/schema.sql`

4. Verify database credentials in `config/config.php`:
   - `DB_HOST`: `localhost`
   - `DB_NAME`: `bpc_registrar`
   - `DB_USER`: `root`
   - `DB_PASS`: `` (empty by default in XAMPP)

5. Open the app in your browser:
   - `http://localhost/BPC-Registrar`

## Default Accounts
- Administrator: `adminaccount@gmail.com` / `admin123`
- Registrar Staff: `staffaccount@gmail.com` / `admin123`

## Notes
- All modules require login.
- Role-based access control is enforced per role.
- Audit logs are recorded for key actions.
