import { NextResponse } from "next/server";
import { env } from "@/lib/env";
import { resolveTableName } from "@/lib/db";
import { buildIntegrationManifest, integrationCatalog } from "@/lib/integration-catalog";
import { deliverIntegrationResource } from "@/lib/integration-delivery";
import { getIntegrationPayload } from "@/lib/integration-payload";
import {
  listAcademicReports,
  listClassListSummary,
  listClasses,
  listDocuments,
  listEnrollments,
  listGrades,
  listInstructors,
  listNotifications,
  listReports,
  listStudents,
  listUsers,
  listIntegrationRecords,
  getClassRoster,
  getExportRows,
  workflowTemplates
} from "@/lib/data";
import {
  createAcademicReport,
  createClassSchedule,
  createDocumentRequest,
  createEnrollment,
  createGrade,
  createIntegrationRecord,
  createInstructor,
  createReport,
  createStudent,
  createUserAccount,
  createWorkflowReport,
  deleteAcademicReport,
  deleteClassSchedule,
  deleteDocumentRequest,
  deleteEnrollment,
  deleteGrade,
  deleteInstructor,
  deleteReport,
  deleteStudent,
  deleteUserAccount,
  markAllNotificationsRead,
  markNotificationRead,
  resetUserPassword,
  toggleUserAccount,
  updateAcademicReport,
  updateClassSchedule,
  updateDocumentRequest,
  updateEnrollment,
  updateGrade,
  updateInstructor,
  updateReport,
  updateStudent,
  updateUserAccount
} from "@/lib/mutations";
import { getSessionUser } from "@/lib/session";

async function getInput(request: Request) {
  const type = request.headers.get("content-type") || "";
  if (type.includes("application/json")) {
    return (await request.json()) as Record<string, unknown>;
  }
  const form = await request.formData();
  return Object.fromEntries(form.entries());
}

function json(data: unknown, status = 200) {
  return NextResponse.json(data, { status });
}

async function requireSessionRole(role: "Administrator" | "Registrar Staff") {
  const user = await getSessionUser();
  if (!user) return null;
  const userRole = user.role.trim().toLowerCase() === "admin" ? "admin" : "staff";
  const needed = role.trim().toLowerCase() === "administrator" ? "admin" : "staff";
  if (needed === "staff" && userRole === "admin") return user;
  return userRole === needed ? user : null;
}

async function requireIntegrationAccess(request: Request) {
  const apiKey = request.headers.get("x-integration-key") || request.headers.get("authorization")?.replace(/^Bearer\s+/i, "") || "";
  if (env.INTEGRATION_API_KEY && apiKey === env.INTEGRATION_API_KEY) {
    return { id: null, role: "integration" };
  }
  return requireSessionRole("Administrator");
}

async function requireIntegrationUser(request: Request) {
  const apiKey = request.headers.get("x-integration-key") || request.headers.get("authorization")?.replace(/^Bearer\s+/i, "") || "";
  if (env.INTEGRATION_API_KEY && apiKey === env.INTEGRATION_API_KEY) {
    return { id: null, role: "integration" };
  }
  return requireSessionRole("Registrar Staff");
}

export async function GET(request: Request, context: { params: Promise<{ resource: string }> }) {
  const { resource } = await context.params;
  const slug = resource.toLowerCase();
  const url = new URL(request.url);

  try {
    if (slug === "integrations") {
      const user = await requireIntegrationUser(request);
      if (!user) return json({ ok: false, error: "Unauthorized." }, 401);

      const integrationResource = String(url.searchParams.get("resource") ?? "manifest").toLowerCase();
      if (integrationResource === "manifest") {
        return json({ ok: true, data: buildIntegrationManifest("/api/integrations") });
      }
      return json({
        ok: true,
        data: await getIntegrationPayload(integrationResource, {
          studentNo: String(url.searchParams.get("student_no") ?? "").trim(),
          studentId: Number(url.searchParams.get("student_id") ?? 0)
        })
      });
    }

    const user = await requireSessionRole(slug === "users" || slug === "reports" || slug === "academic-reports" ? "Administrator" : "Registrar Staff");
    if (!user) return json({ ok: false, error: "Unauthorized." }, 401);

    switch (slug) {
      case "students":
        return json({ ok: true, data: { students: await listStudents() } });
      case "instructors":
        return json({ ok: true, data: { instructors: await listInstructors() } });
      case "classes":
        return json({ ok: true, data: { classes: await listClasses() } });
      case "class-lists": {
        const classId = Number(url.searchParams.get("class_id") ?? 0);
        if (classId > 0) {
          const data = await getClassRoster(classId);
          return json({ ok: true, data: { class: data.classInfo, roster: data.roster } });
        }
        return json({ ok: true, data: { classes: await listClassListSummary(String(url.searchParams.get("q") ?? "")) } });
      }
      case "enrollments":
        return json({ ok: true, data: { enrollments: await listEnrollments() } });
      case "grades":
        return json({ ok: true, data: { grades: await listGrades() } });
      case "documents":
        return json({ ok: true, data: { documents: await listDocuments() } });
      case "users":
        return json({ ok: true, data: { users: await listUsers() } });
      case "reports":
        return json({ ok: true, data: { reports: await listReports(), templates: workflowTemplates } });
      case "academic-reports":
        return json({ ok: true, data: { academic_reports: await listAcademicReports() } });
      case "notifications": {
        return json({ ok: true, data: { notifications: await listNotifications(20) } });
      }
      default:
        return json({ ok: false, error: "Unknown resource." }, 404);
    }
  } catch (error) {
    return json({ ok: false, error: error instanceof Error ? error.message : "Request failed." }, 500);
  }
}

export async function POST(request: Request, context: { params: Promise<{ resource: string }> }) {
  const { resource } = await context.params;
  const slug = resource.toLowerCase();
  const input = await getInput(request);

  try {
    if (slug === "integrations") {
      const integrationResource = String(input.resource ?? "").toLowerCase();
      const outgoingMapping = integrationCatalog.find((entry) => entry.direction === "outgoing" && entry.key === integrationResource);
      if (String(input.action ?? "").toLowerCase() === "deliver" && outgoingMapping) {
        const user = await requireIntegrationUser(request);
        if (!user) return json({ ok: false, error: "Unauthorized." }, 401);

        const result = await deliverIntegrationResource(integrationResource, {
          studentNo: String(input.student_no ?? input.studentNo ?? "").trim(),
          studentId: Number(input.student_id ?? input.studentId ?? 0)
        });

        return json({ ok: result.ok, message: result.message, data: result }, result.ok ? 200 : 422);
      }

      const user = await requireIntegrationAccess(request);
      if (!user) return json({ ok: false, error: "Unauthorized." }, 401);
      const mapping = integrationCatalog.find((entry) => entry.direction === "incoming" && entry.key === integrationResource);
      if (!mapping) return json({ ok: false, error: "Unsupported integration resource." }, 405);
      const id = await createIntegrationRecord({
        studentNo: String(input.student_no ?? input.studentNo ?? "").trim(),
        sourceOffice: String(input.source_office ?? mapping.office).trim(),
        recordType: String(input.record_type ?? mapping.key.replace(/-/g, "_").replace(/s$/, "")).trim(),
        referenceNo: String(input.reference_no ?? "").trim(),
        externalStatus: String(input.status ?? input.external_status ?? "Received").trim() || "Received",
        title: String(input.title ?? "").trim(),
        notes: String(input.notes ?? "").trim(),
        actorId: user.id
      });
      return json({ ok: true, message: "Integration record received successfully.", data: { id } }, 201);
    }

    const user = await requireSessionRole(slug === "users" || slug === "reports" || slug === "academic-reports" ? "Administrator" : "Registrar Staff");
    if (!user) return json({ ok: false, error: "Unauthorized." }, 401);

    const action = String(input.action ?? "").toLowerCase();

    switch (slug) {
      case "students":
        if (action === "create") return json({ ok: true, data: { id: await createStudent({ studentNo: String(input.student_no ?? ""), firstName: String(input.first_name ?? ""), lastName: String(input.last_name ?? ""), program: String(input.program ?? ""), yearLevel: String(input.year_level ?? ""), status: String(input.status ?? "Active"), actorId: user.id }) } });
        if (action === "update") return json({ ok: true, data: { id: Number(input.id ?? 0) }, message: (await updateStudent({ id: Number(input.id ?? 0), studentNo: String(input.student_no ?? ""), firstName: String(input.first_name ?? ""), lastName: String(input.last_name ?? ""), program: String(input.program ?? ""), yearLevel: String(input.year_level ?? ""), status: String(input.status ?? "Active"), actorId: user.id }), "Student updated.") });
        if (action === "delete") return json({ ok: true, data: { id: Number(input.id ?? 0) }, message: (await deleteStudent({ id: Number(input.id ?? 0), actorId: user.id }), "Student deleted.") });
        break;
      case "instructors":
        if (action === "create") return json({ ok: true, data: { id: await createInstructor({ employeeNo: String(input.employee_no ?? ""), firstName: String(input.first_name ?? ""), lastName: String(input.last_name ?? ""), department: String(input.department ?? ""), actorId: user.id }) } });
        if (action === "update") return json({ ok: true, message: (await updateInstructor({ id: Number(input.id ?? 0), employeeNo: String(input.employee_no ?? ""), firstName: String(input.first_name ?? ""), lastName: String(input.last_name ?? ""), department: String(input.department ?? ""), actorId: user.id }), "Instructor updated.") });
        if (action === "delete") return json({ ok: true, message: (await deleteInstructor({ id: Number(input.id ?? 0), actorId: user.id }), "Instructor deleted.") });
        break;
      case "classes":
        if (action === "create") return json({ ok: true, data: { id: await createClassSchedule({ classCode: String(input.class_code ?? ""), title: String(input.class_title ?? ""), course: String(input.course ?? ""), units: Number(input.units ?? 0), day: String(input.day ?? ""), time: String(input.time ?? ""), room: String(input.room ?? ""), actorId: user.id }) } });
        if (action === "update") return json({ ok: true, message: (await updateClassSchedule({ classId: Number(input.class_id ?? 0), classCode: String(input.class_code ?? ""), title: String(input.class_title ?? ""), course: String(input.course ?? ""), units: Number(input.units ?? 0), day: String(input.day ?? ""), time: String(input.time ?? ""), room: String(input.room ?? ""), actorId: user.id }), "Class updated.") });
        if (action === "delete") return json({ ok: true, message: (await deleteClassSchedule({ classId: Number(input.class_id ?? 0), actorId: user.id }), "Class deleted.") });
        break;
      case "enrollments":
        if (action === "create") return json({ ok: true, data: { id: await createEnrollment({ studentId: Number(input.student_id ?? 0), classId: Number(input.class_id ?? 0), status: String(input.status ?? "Enrolled"), actorId: user.id }) } });
        if (action === "update") return json({ ok: true, message: (await updateEnrollment({ id: Number(input.id ?? 0), status: String(input.status ?? ""), actorId: user.id }), "Enrollment updated.") });
        if (action === "delete") return json({ ok: true, message: (await deleteEnrollment({ id: Number(input.id ?? 0), actorId: user.id }), "Enrollment deleted.") });
        break;
      case "grades":
        if (action === "create") return json({ ok: true, data: { id: await createGrade({ studentId: Number(input.student_id ?? 0), classId: Number(input.class_id ?? 0), semester: String(input.semester ?? ""), grade: String(input.grade ?? ""), remarks: String(input.remarks ?? ""), actorId: user.id }) } });
        if (action === "update") return json({ ok: true, message: (await updateGrade({ id: Number(input.id ?? 0), grade: String(input.grade ?? ""), remarks: String(input.remarks ?? ""), actorId: user.id }), "Grade updated.") });
        if (action === "delete") return json({ ok: true, message: (await deleteGrade({ id: Number(input.id ?? 0), actorId: user.id }), "Grade deleted.") });
        break;
      case "documents":
        if (action === "create") return json({ ok: true, data: { id: await createDocumentRequest({ studentId: Number(input.student_id ?? 0), docType: String(input.doc_type ?? ""), actorId: user.id }) } });
        if (action === "update") return json({ ok: true, message: (await updateDocumentRequest({ id: Number(input.doc_id ?? 0), status: String(input.status ?? ""), actorId: user.id }), "Document updated.") });
        if (action === "delete") return json({ ok: true, message: (await deleteDocumentRequest({ id: Number(input.doc_id ?? 0), actorId: user.id }), "Document deleted.") });
        break;
      case "users":
        if (action === "create") return json({ ok: true, data: { id: await createUserAccount({ roleId: Number(input.role_id ?? 0), username: String(input.username ?? ""), password: String(input.password ?? ""), firstName: String(input.first_name ?? ""), lastName: String(input.last_name ?? ""), actorId: user.id }) } });
        if (action === "update") return json({ ok: true, message: (await updateUserAccount({ id: Number(input.id ?? 0), roleId: Number(input.role_id ?? 0), username: String(input.username ?? ""), firstName: String(input.first_name ?? ""), lastName: String(input.last_name ?? ""), actorId: user.id }), "User updated.") });
        if (action === "toggle_active") return json({ ok: true, message: (await toggleUserAccount({ id: Number(input.id ?? 0), isActive: Number(input.is_active ?? 0) === 1, actorId: user.id }), "User status updated.") });
        if (action === "reset_password") return json({ ok: true, message: (await resetUserPassword({ id: Number(input.id ?? 0), password: String(input.password ?? ""), actorId: user.id }), "Password reset successful.") });
        if (action === "delete") return json({ ok: true, message: (await deleteUserAccount({ id: Number(input.id ?? 0), actorId: user.id }), "User deleted.") });
        break;
      case "reports":
        if (action === "create") return json({ ok: true, data: { id: await createReport({ title: String(input.title ?? ""), department: String(input.department ?? ""), status: String(input.status ?? "Pending"), dueDate: String(input.due_date ?? ""), actorId: user.id }) } });
        if (action === "create_workflow_report") return json({ ok: true, data: { id: await createWorkflowReport({ workflowKey: String(input.workflow_key ?? "") as never, title: String(input.title ?? ""), department: String(input.department ?? ""), status: String(input.status ?? "Pending"), dueDate: String(input.due_date ?? ""), actorId: user.id }) } });
        if (action === "update") return json({ ok: true, message: (await updateReport({ id: Number(input.id ?? 0), title: String(input.title ?? ""), department: String(input.department ?? ""), status: String(input.status ?? "Pending"), dueDate: String(input.due_date ?? ""), actorId: user.id }), "Report updated.") });
        if (action === "delete") return json({ ok: true, message: (await deleteReport({ id: Number(input.id ?? 0), actorId: user.id }), "Report deleted.") });
        break;
      case "academic-reports":
        if (action === "create") return json({ ok: true, data: { id: await createAcademicReport({ studentId: Number(input.student_id ?? 0), title: String(input.title ?? ""), coverage: String(input.coverage ?? ""), reportType: String(input.report_type ?? "Transcript"), semester: String(input.semester ?? ""), authorizationStatus: String(input.authorization_status ?? "Pending"), outputFormat: String(input.output_format ?? "Transcript / Grade Report"), status: String(input.status ?? "Draft"), actorId: user.id }) } });
        if (action === "update") return json({ ok: true, message: (await updateAcademicReport({ id: Number(input.id ?? 0), studentId: Number(input.student_id ?? 0), title: String(input.title ?? ""), coverage: String(input.coverage ?? ""), reportType: String(input.report_type ?? "Transcript"), semester: String(input.semester ?? ""), authorizationStatus: String(input.authorization_status ?? "Pending"), outputFormat: String(input.output_format ?? "Transcript / Grade Report"), status: String(input.status ?? "Draft"), actorId: user.id }), "Academic report updated.") });
        if (action === "delete") return json({ ok: true, message: (await deleteAcademicReport({ id: Number(input.id ?? 0), actorId: user.id }), "Academic report deleted.") });
        break;
      case "notifications":
        if (action === "mark_read") return json({ ok: true, message: (await markNotificationRead({ id: Number(input.id ?? 0) }), "Marked as read.") });
        if (action === "mark_all_read") return json({ ok: true, message: (await markAllNotificationsRead(), "All notifications marked as read.") });
        break;
    }

    return json({ ok: false, error: "Unknown action." }, 400);
  } catch (error) {
    return json({ ok: false, error: error instanceof Error ? error.message : "Request failed." }, 422);
  }
}
