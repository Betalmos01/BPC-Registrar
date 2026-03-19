export type NavItem = {
  label: string;
  href: string;
  icon: string;
};

export function getNavigation(role: string): Array<{ section: string; items: NavItem[] }> {
  const normalizedRole = role.trim().toLowerCase();
  const isAdmin = normalizedRole === "administrator" || normalizedRole === "admin";

  if (isAdmin) {
    return [
      {
        section: "Campus Control",
        items: [
          { label: "Dashboard", href: "/admin/dashboard", icon: "dashboard" },
          { label: "User Management", href: "/admin/users", icon: "users" }
        ]
      },
      {
        section: "Registrar Workflow",
        items: [
          { label: "Student Records", href: "/staff/students", icon: "students" },
          { label: "Faculty / Instructor Management", href: "/staff/instructors", icon: "faculty" },
          { label: "Classes and Schedules", href: "/staff/classes", icon: "classes" },
          { label: "Class Lists", href: "/staff/class-lists", icon: "list" },
          { label: "Schedules", href: "/staff/schedules", icon: "calendar" },
          { label: "Enrollment Monitoring", href: "/staff/enrollments", icon: "enrollment" },
          { label: "Grade Records", href: "/staff/grades", icon: "grades" },
          { label: "Document Requests", href: "/staff/documents", icon: "documents" },
          { label: "Staff Directory", href: "/staff/staff-directory", icon: "directory" },
          { label: "Academic Reports", href: "/admin/academic-reports", icon: "academic" },
          { label: "System Logs", href: "/admin/activity", icon: "logs" }
        ]
      },
      {
        section: "Reports",
        items: [
          { label: "Reports", href: "/admin/reports", icon: "reports" }
        ]
      },
      {
        section: "Account",
        items: [
          { label: "My Profile", href: "/profile", icon: "profile" },
          { label: "System Settings", href: "/admin/settings", icon: "settings" }
        ]
      }
    ];
  }

  return [
    {
      section: "Registrar System",
      items: [{ label: "Dashboard", href: "/staff/dashboard", icon: "dashboard" }]
    },
    {
      section: "Student Workflow",
      items: [
        { label: "Student Management", href: "/staff/students", icon: "students" },
        { label: "Faculty / Instructor Management", href: "/staff/instructors", icon: "faculty" },
        { label: "Manage Classes and Schedules", href: "/staff/classes", icon: "classes" },
        { label: "Class Lists", href: "/staff/class-lists", icon: "list" },
        { label: "Schedules", href: "/staff/schedules", icon: "calendar" },
        { label: "Enroll Students", href: "/staff/enrollments", icon: "enrollment" },
        { label: "Record Grades", href: "/staff/grades", icon: "grades" },
        { label: "Document Requests", href: "/staff/documents", icon: "documents" },
        { label: "Staff Directory", href: "/staff/staff-directory", icon: "directory" }
      ]
    },
    {
      section: "Account",
      items: [{ label: "My Profile", href: "/profile", icon: "profile" }]
    }
  ];
}
