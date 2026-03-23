import { NextRequest, NextResponse } from "next/server";

const legacyRedirects: Record<string, string> = {
  "/index.php": "/",
  "/admin.php": "/admin/dashboard",
  "/staff.php": "/staff/dashboard",
  "/profile.php": "/profile",
  "/access_denied.php": "/access-denied",
  "/auth/login.php": "/",
  "/auth/logout.php": "/",
  "/admin/login": "/",
  "/admin/login.php": "/",
  "/admin/dashboard.php": "/admin/dashboard",
  "/admin/users.php": "/admin/users",
  "/admin/reports.php": "/admin/reports",
  "/admin/export_reports.php": "/admin/reports/export",
  "/admin/academic_reports.php": "/admin/academic-reports",
  "/admin/integrations.php": "/admin/integrations",
  "/admin/activity.php": "/admin/activity",
  "/admin/settings.php": "/admin/settings",
  "/staff/dashboard.php": "/staff/dashboard",
  "/staff/students.php": "/staff/students",
  "/staff/instructors.php": "/staff/instructors",
  "/staff/classes.php": "/staff/classes",
  "/staff/class_lists.php": "/staff/class-lists",
  "/staff/class_list_view.php": "/staff/class-lists",
  "/staff/enrollments.php": "/staff/enrollments",
  "/staff/grades.php": "/staff/grades",
  "/staff/documents.php": "/staff/documents",
  "/staff/schedules.php": "/staff/schedules",
  "/staff/staff_directory.php": "/staff/staff-directory",
  "/api/students.php": "/api/students",
  "/api/instructors.php": "/api/instructors",
  "/api/classes.php": "/api/classes",
  "/api/class_lists.php": "/api/class-lists",
  "/api/enrollments.php": "/api/enrollments",
  "/api/grades.php": "/api/grades",
  "/api/documents.php": "/api/documents",
  "/api/users.php": "/api/users",
  "/api/reports.php": "/api/reports",
  "/api/academic_reports.php": "/api/academic-reports",
  "/api/notifications.php": "/api/notifications",
  "/api/integrations.php": "/api/integrations"
};

export function middleware(request: NextRequest) {
  const target = legacyRedirects[request.nextUrl.pathname];
  if (!target) {
    return NextResponse.next();
  }

  const url = request.nextUrl.clone();
  url.pathname = target;
  return NextResponse.redirect(url, 308);
}

export const config = {
  matcher: ["/:path*"]
};
