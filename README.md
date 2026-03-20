# BPC Registrar Next.js Translation

This folder is the TypeScript translation target for the original PHP-based BPC Registrar application.

## Stack

- Next.js
- React
- TypeScript
- PostgreSQL via `pg`
- Supabase Postgres through `DATABASE_URL`

## Current Translation Coverage

- Login screen
- Shared admin/staff app shell
- Admin dashboard
- Staff dashboard
- Students
- Instructors
- Classes
- Class lists
- Enrollments
- Grades
- Documents
- Users
- Reports
- Academic reports
- Office integrations
- Activity logs
- Settings shell

## Setup

1. Use the `.env` file in this folder.
2. Set:
   - `DATABASE_URL`
   - `SESSION_SECRET`
   - `APP_URL`
   - `INTEGRATION_API_KEY`
   - Optional outbound integration targets:
     - `CRAD_STUDENT_LIST_ENDPOINT`
     - `PREFECT_STUDENT_PERSONAL_INFO_ENDPOINT`
     - `COMLAB_STUDENT_LIST_ENDPOINT`
     - `CASHIER_ENROLLMENT_DATA_ENDPOINT`
     - `CLINIC_STUDENT_PERSONAL_INFO_ENDPOINT`
     - `GUIDANCE_STUDENT_PERSONAL_INFO_ENDPOINT`
     - `GUIDANCE_STUDENT_ACADEMIC_RECORDS_ENDPOINT`
     - `PMED_ENROLLMENT_STATISTICS_ENDPOINT`
3. Install dependencies:

```bash
npm install
```

4. Start the app:

```bash
npm run dev
```

App URL: `http://localhost:3000`

## Default accounts

Use these built-in bypass accounts for local testing:

- Admin: `admin@gmail.com` / `admin123`
- Staff: `staff@gmail.com` / `admin123`

## Notes

- This is the real TypeScript app translation path, not just a backend service.
- Some pages are already live with Supabase-backed data reads.
- Some richer PHP interactions are still represented as translated list/detail screens and will need deeper CRUD polish next.
