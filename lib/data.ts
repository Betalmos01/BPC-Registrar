import { hasColumn, hasTable, query, queryOne, queryValue, resolveTableName } from "./db";

export type AppRole = "admin" | "staff";

function splitFullName(fullName: string) {
  const normalized = fullName.trim().replace(/\s+/g, " ");
  if (!normalized) {
    return { firstName: "", lastName: "" };
  }

  const parts = normalized.split(" ");
  if (parts.length === 1) {
    return { firstName: parts[0], lastName: "" };
  }

  return {
    firstName: parts.slice(0, -1).join(" "),
    lastName: parts[parts.length - 1]
  };
}

export function normalizeRole(role: string, isSuperAdmin = false): AppRole {
  if (isSuperAdmin || role.trim().toLowerCase() === "admin") {
    return "admin";
  }

  return "staff";
}

export const workflowTemplates = {
  "access-setup": {
    label: "Access Setup",
    title: "Access Setup Compliance Report",
    department: "Registrar Administration",
    summary: "Review user provisioning, role assignments, and account readiness across the registrar office.",
    dueInDays: 3
  },
  "student-intake": {
    label: "Student Intake",
    title: "Student Intake Master List Report",
    department: "Student Records",
    summary: "Summarize newly encoded student records, intake readiness, and pending validation items.",
    dueInDays: 5
  },
  "class-planning": {
    label: "Class Planning",
    title: "Class Planning and Subject Offering Report",
    department: "Scheduling",
    summary: "Track prepared subjects, room schedules, and section readiness before enrollment starts.",
    dueInDays: 5
  },
  "enrollment-validation": {
    label: "Enrollment Validation",
    title: "Enrollment Validation Summary",
    department: "Enrollment",
    summary: "Measure confirmed registrations, pending validations, and load assignment progress.",
    dueInDays: 4
  },
  "grade-posting": {
    label: "Grade Posting",
    title: "Grade Posting Completion Report",
    department: "Academic Records",
    summary: "Review subject grade completion, posting progress, and release readiness by semester.",
    dueInDays: 4
  },
  "completion-services": {
    label: "Completion Services",
    title: "Completion Services Release Report",
    department: "Document Services",
    summary: "Monitor transcript, certification, and other registrar release services for completed records.",
    dueInDays: 6
  },
  "compliance-reports": {
    label: "Compliance & Reports",
    title: "Registrar Compliance and Reporting Overview",
    department: "Registrar Compliance",
    summary: "Roll up audit, workflow, and institutional reporting checkpoints into one operational view.",
    dueInDays: 7
  }
} as const;

export async function getAuthUserByLogin(login: string) {
  const account = await queryOne<{
    id: number;
    username: string;
    email: string | null;
    full_name: string | null;
    role: string;
    is_super_admin: number | boolean | null;
    password_hash: string;
    status: string;
  }>(
    `select id, username, email, full_name, role, is_super_admin, password_hash, status
     from admin_users
     where (lower(username) = lower($1) or lower(coalesce(email, '')) = lower($1))
       and lower(coalesce(status, 'inactive')) = 'active'
     limit 1`,
    [login]
  );

  if (!account) {
    return null;
  }

  const name = splitFullName(account.full_name ?? "");
  const appRole = normalizeRole(account.role, Boolean(Number(account.is_super_admin ?? 0)));

  return {
    id: account.id,
    username: account.username,
    email: account.email ?? "",
    password_hash: account.password_hash,
    first_name: name.firstName,
    last_name: name.lastName,
    display_name: account.full_name ?? account.username,
    profile_photo: "",
    profile_title: account.role,
    profile_bio: account.email ?? "",
    profile_accent: appRole === "admin" ? "#8B1E3F" : "#2F6BD9",
    role: appRole
  };
}

async function getStudentsTable() {
  return resolveTableName("registrar_students", "students");
}

async function getInstructorsTable() {
  return resolveTableName("hr_instructors", "hr.instructors", "registrar_instructors", "registrar.instructors", "instructors");
}

async function getInstructorAssignmentsTable() {
  return resolveTableName(
    "registrar_instructor_class_assignments",
    "registrar.instructor_class_assignments",
    "instructor_class_assignments"
  );
}

async function getClassesTable() {
  return resolveTableName("registrar_classes", "classes");
}

async function getClassListsTable() {
  return resolveTableName("registrar_class_lists", "registrar.class_lists", "class_lists");
}

async function getSchedulesTable() {
  return resolveTableName("registrar_schedules", "schedules");
}

async function getEnrollmentsTable() {
  return resolveTableName("registrar.enrollments", "registrar_enrollments", "enrollments");
}

async function getGradesTable() {
  return resolveTableName("registrar.grades", "registrar_grades", "grades");
}

async function getAuditLogsTable() {
  return resolveTableName("registrar_audit_logs", "audit_logs");
}

async function getNotificationsTable() {
  return resolveTableName("registrar_notifications", "notifications");
}

async function getAcademicReportsTable() {
  return resolveTableName("registrar_academic_reports", "academic_reports");
}

async function getUsersTable() {
  return resolveTableName("registrar_users", "users");
}

async function getRolesTable() {
  return resolveTableName("registrar_roles", "roles");
}

async function safeCount(promise: Promise<number | null>): Promise<number> {
  try {
    return Number((await promise) ?? 0);
  } catch {
    return 0;
  }
}

export async function getDashboardStats() {
  const reportsTable = await resolveTableName("reports", "registrar_reports");
  const documentsTable = await resolveTableName("documents", "registrar_documents");
  const gradesTable = await getGradesTable();
  const auditLogsTable = await getAuditLogsTable();
  const studentsTable = await getStudentsTable();
  const classesTable = await getClassesTable();
  const enrollmentsTable = await getEnrollmentsTable();
  const [reports, documents, grades, auditLogs, students, classes, enrollments] = await Promise.all([
    reportsTable ? safeCount(queryValue<number>(`select count(*) from ${reportsTable}`)) : Promise.resolve(0),
    documentsTable ? safeCount(queryValue<number>(`select count(*) from ${documentsTable} where status = 'Pending'`)) : Promise.resolve(0),
    gradesTable ? safeCount(queryValue<number>(`select count(*) from ${gradesTable}`)) : Promise.resolve(0),
    auditLogsTable ? safeCount(queryValue<number>(`select count(*) from ${auditLogsTable}`)) : Promise.resolve(0),
    studentsTable ? safeCount(queryValue<number>(`select count(*) from ${studentsTable}`)) : Promise.resolve(0),
    classesTable ? safeCount(queryValue<number>(`select count(*) from ${classesTable}`)) : Promise.resolve(0),
    enrollmentsTable ? safeCount(queryValue<number>(`select count(*) from ${enrollmentsTable}`)) : Promise.resolve(0)
  ]);

  return {
    reports,
    pendingDocuments: documents,
    grades,
    auditLogs,
    students,
    classes,
    enrollments
  };
}

export async function listStudents(search = "", program = "", year = "") {
  const studentsTable = await getStudentsTable();
  if (!studentsTable) {
    return [];
  }

  const conditions: string[] = [];
  const params: unknown[] = [];

  if (search.trim()) {
    params.push(`%${search.trim()}%`);
    conditions.push(`(student_no ilike $${params.length} or first_name ilike $${params.length} or last_name ilike $${params.length})`);
  }
  if (program.trim()) {
    params.push(program.trim());
    conditions.push(`program = $${params.length}`);
  }
  if (year.trim()) {
    params.push(year.trim());
    conditions.push(`year_level = $${params.length}`);
  }

  const students = await query<any>(
      `select * from ${studentsTable} ${conditions.length ? `where ${conditions.join(" and ")}` : ""}
       order by last_name, first_name, student_no`,
      params
    );

  if (students.length === 0) {
    return students;
  }

  const paymentStatuses = await getStudentPaymentStatuses(
    students.map((student) => ({
      id: Number(student.id),
      student_no: String(student.student_no ?? "")
    }))
  );
  const incomingStatuses = await getStudentIncomingIntegrationStatuses(
    students.map((student) => ({
      id: Number(student.id)
    }))
  );
  const incomingDetails = await getStudentIncomingIntegrationDetails(
    students.map((student) => ({
      id: Number(student.id)
    }))
  );
  const enrollmentStatuses = await getStudentLatestEnrollmentStatuses(
    students.map((student) => ({
      id: Number(student.id),
      student_no: String(student.student_no ?? "")
    }))
  );

  return students.map((student) => ({
    ...student,
    enrollment_status: enrollmentStatuses.get(Number(student.id)) ?? "Not Enrolled",
    payment_status: paymentStatuses.get(String(student.student_no ?? "").trim()) ?? "Pending",
    ...(incomingStatuses.get(Number(student.id)) ?? {
      medical_clearance_status: "Pending",
      counseling_report_status: "Pending",
      discipline_record_status: "Pending",
      activity_participation_status: "Pending"
    }),
    ...(incomingDetails.get(Number(student.id)) ?? {})
  }));
  }

export async function listStudentsBasic() {
  const studentsTable = await getStudentsTable();
  if (!studentsTable) {
    return [];
  }

  return query<{
    id: number;
    student_no: string;
    first_name: string;
    last_name: string;
    program: string | null;
    year_level: string | null;
  }>(
    `select id, student_no, first_name, last_name, program, year_level
     from ${studentsTable}
     order by last_name, first_name, student_no`
  );
}

async function getStudentLatestEnrollmentStatuses(students: Array<{ id: number; student_no: string }>) {
  const statusMap = new Map<number, string>();
  if (students.length === 0) {
    return statusMap;
  }

  const enrollmentsTable = await getEnrollmentsTable();
  if (!enrollmentsTable) {
    return statusMap;
  }

  const studentIds = students.map((student) => Number(student.id)).filter((value) => Number.isFinite(value));
  if (studentIds.length === 0) {
    return statusMap;
  }

  const hasDeletedAt = await hasColumn(enrollmentsTable, "deleted_at");
  const enrollmentRows = await query<{ student_id: number; status: string }>(
    `select distinct on (student_id) student_id, status
     from ${enrollmentsTable}
     where student_id = any($1)
     ${hasDeletedAt ? "and deleted_at is null" : ""}
     order by student_id, created_at desc nulls last, id desc`,
    [studentIds]
  );

  enrollmentRows.forEach((row) => {
    const normalized = String(row.status ?? "").trim();
    if (normalized) {
      statusMap.set(Number(row.student_id), normalized);
    }
  });

  const cashierFeedTable = await resolveTableName("cashier_registrar_student_enrollment_feed");
  if (cashierFeedTable) {
    const studentNos = students.map((student) => String(student.student_no ?? "").trim()).filter(Boolean);
    if (studentNos.length > 0) {
      const feedRows = await query<{ student_no: string; status: string }>(
        `select distinct on (student_no) student_no, status
         from ${cashierFeedTable}
         where student_no = any($1)
         order by student_no, coalesce(action_at, sent_at, created_at) desc nulls last, id desc`,
        [studentNos]
      );

      const studentIdByNo = new Map(
        students.map((student) => [String(student.student_no ?? "").trim(), Number(student.id)] as const)
      );

      feedRows.forEach((row) => {
        const studentId = studentIdByNo.get(String(row.student_no ?? "").trim());
        if (!studentId) return;
        const normalized = String(row.status ?? "").trim();
        if (normalized) {
          statusMap.set(studentId, normalized);
        }
      });
    }
  }

  return statusMap;
}

function normalizePaymentStatus(status: string | null | undefined) {
  const normalized = String(status ?? "").trim().toLowerCase();

  if (!normalized) {
    return "Pending";
  }
  if (["paid", "verified", "completed", "settled", "cleared"].includes(normalized)) {
    return "Paid";
  }
  if (["rejected", "failed", "unpaid", "declined"].includes(normalized)) {
    return "Unpaid";
  }

  return normalized === "submitted" ? "Pending" : normalized.replace(/\b\w/g, (value) => value.toUpperCase());
}

async function getStudentPaymentStatuses(students: Array<{ id: number; student_no: string }>) {
  const paymentMap = new Map<string, string>();
  const studentNos = students.map((student) => student.student_no).filter(Boolean);
  const studentIds = students.map((student) => student.id).filter((value) => Number.isFinite(value));

  if (studentNos.length === 0) {
    return paymentMap;
  }

  const [paymentLinksTable, paymentEventsTable, integrationRecordsTable] = await Promise.all([
    resolveTableName("registrar.cashier_payment_links", "cashier_payment_links"),
    resolveTableName("registrar.cashier_integration_events", "cashier_integration_events"),
    resolveTableName("integration_records")
  ]);

  if (paymentLinksTable) {
    const rows = await query<{ source_key: string; payment_status: string }>(
      `select distinct on (source_key) source_key, payment_status
       from ${paymentLinksTable}
       where source_key = any($1)
       order by source_key, coalesce(cashier_verified_at, paid_at, updated_at, created_at) desc nulls last`,
      [studentNos]
    );

    rows.forEach((row) => {
      paymentMap.set(String(row.source_key), normalizePaymentStatus(row.payment_status));
    });
  }

  if (paymentEventsTable) {
    const rows = await query<{ source_key: string; payment_status: string }>(
      `select distinct on (source_key) source_key, payment_status
       from ${paymentEventsTable}
       where source_key = any($1)
       order by source_key, coalesce(synced_at, updated_at, created_at) desc nulls last`,
      [studentNos]
    );

    rows.forEach((row) => {
      if (!paymentMap.has(String(row.source_key))) {
        paymentMap.set(String(row.source_key), normalizePaymentStatus(row.payment_status));
      }
    });
  }

  if (integrationRecordsTable && studentIds.length > 0) {
    const rows = await query<{ student_id: number; external_status: string }>(
      `select distinct on (student_id) student_id, external_status
       from ${integrationRecordsTable}
       where record_type = 'payment_confirmation'
         and student_id = any($1)
       order by student_id, received_at desc nulls last, created_at desc nulls last`,
      [studentIds]
    );

    const studentNoById = new Map(students.map((student) => [student.id, student.student_no]));

    rows.forEach((row) => {
      const studentNo = studentNoById.get(Number(row.student_id));
      if (studentNo && !paymentMap.has(studentNo)) {
        paymentMap.set(studentNo, normalizePaymentStatus(row.external_status));
      }
    });
  }

  return paymentMap;
}

function formatIntegrationStatus(value: string | null | undefined) {
  const normalized = String(value ?? "").trim();
  return normalized ? normalized.replace(/\b\w/g, (part) => part.toUpperCase()) : "Pending";
}

async function getStudentIncomingIntegrationStatuses(students: Array<{ id: number }>) {
  const statusMap = new Map<
    number,
    {
      medical_clearance_status: string;
      counseling_report_status: string;
      discipline_record_status: string;
      activity_participation_status: string;
    }
  >();
  const integrationRecordsTable = await resolveTableName("integration_records");

  if (!integrationRecordsTable || students.length === 0) {
    return statusMap;
  }

  const studentIds = students.map((student) => student.id).filter((value) => Number.isFinite(value));
  if (studentIds.length === 0) {
    return statusMap;
  }

  const rows = await query<{ student_id: number; record_type: string; external_status: string }>(
    `select distinct on (student_id, record_type) student_id, record_type, external_status
     from ${integrationRecordsTable}
     where student_id = any($1)
       and record_type = any($2)
     order by student_id, record_type, received_at desc nulls last, created_at desc nulls last`,
    [
      studentIds,
      ["medical_clearance", "counseling_report", "discipline_record", "activity_participation_record"]
    ]
  );

  rows.forEach((row) => {
    const current = statusMap.get(Number(row.student_id)) ?? {
      medical_clearance_status: "Pending",
      counseling_report_status: "Pending",
      discipline_record_status: "Pending",
      activity_participation_status: "Pending"
    };
    const status = formatIntegrationStatus(row.external_status);

    if (row.record_type === "medical_clearance") {
      current.medical_clearance_status = status;
    }
    if (row.record_type === "counseling_report") {
      current.counseling_report_status = status;
    }
    if (row.record_type === "discipline_record") {
      current.discipline_record_status = status;
    }
    if (row.record_type === "activity_participation_record") {
      current.activity_participation_status = status;
    }

    statusMap.set(Number(row.student_id), current);
  });

  return statusMap;
}

async function getStudentIncomingIntegrationDetails(students: Array<{ id: number }>) {
  const detailMap = new Map<
    number,
    {
      medical_clearance_record?: {
        source_office: string;
        reference_no: string;
        external_status: string;
        title: string;
        notes: string;
        received_at: string | null;
      };
      counseling_report_record?: {
        source_office: string;
        reference_no: string;
        external_status: string;
        title: string;
        notes: string;
        received_at: string | null;
      };
      discipline_record_record?: {
        source_office: string;
        reference_no: string;
        external_status: string;
        title: string;
        notes: string;
        received_at: string | null;
      };
      activity_participation_record_record?: {
        source_office: string;
        reference_no: string;
        external_status: string;
        title: string;
        notes: string;
        received_at: string | null;
      };
    }
  >();
  const integrationRecordsTable = await resolveTableName("integration_records");

  if (!integrationRecordsTable || students.length === 0) {
    return detailMap;
  }

  const studentIds = students.map((student) => student.id).filter((value) => Number.isFinite(value));
  if (studentIds.length === 0) {
    return detailMap;
  }

  const rows = await query<{
    student_id: number;
    record_type: string;
    source_office: string;
    reference_no: string;
    external_status: string;
    title: string;
    notes: string;
    received_at: string | null;
  }>(
    `select distinct on (student_id, record_type)
            student_id,
            record_type,
            source_office,
            reference_no,
            external_status,
            title,
            notes,
            received_at
     from ${integrationRecordsTable}
     where student_id = any($1)
       and record_type = any($2)
     order by student_id, record_type, received_at desc nulls last, created_at desc nulls last`,
    [
      studentIds,
      ["medical_clearance", "counseling_report", "discipline_record", "activity_participation_record"]
    ]
  );

  rows.forEach((row) => {
    const current = detailMap.get(Number(row.student_id)) ?? {};
    const detail = {
      source_office: row.source_office ?? "",
      reference_no: row.reference_no ?? "",
      external_status: formatIntegrationStatus(row.external_status),
      title: row.title ?? "",
      notes: row.notes ?? "",
      received_at: row.received_at ?? null
    };

    if (row.record_type === "medical_clearance") {
      current.medical_clearance_record = detail;
    }
    if (row.record_type === "counseling_report") {
      current.counseling_report_record = detail;
    }
    if (row.record_type === "discipline_record") {
      current.discipline_record_record = detail;
    }
    if (row.record_type === "activity_participation_record") {
      current.activity_participation_record_record = detail;
    }

    detailMap.set(Number(row.student_id), current);
  });

  return detailMap;
}

export async function getStudentFilters() {
  const studentsTable = await getStudentsTable();
  if (!studentsTable) {
    return { programs: [], years: [], activeStudents: 0, onHoldStudents: 0 };
  }

  const [programs, years, activeStudents, onHoldStudents] = await Promise.all([
    query<{ program: string }>(`select distinct program from ${studentsTable} where coalesce(program, '') <> '' order by program`),
    query<{ year_level: string }>(`select distinct year_level from ${studentsTable} where coalesce(year_level, '') <> '' order by year_level`),
    queryValue<number>(`select count(*) from ${studentsTable} where status = 'Active'`),
    queryValue<number>(`select count(*) from ${studentsTable} where status = 'On Hold'`)
  ]);

  return {
    programs: programs.map((row) => row.program),
    years: years.map((row) => row.year_level),
    activeStudents: Number(activeStudents ?? 0),
    onHoldStudents: Number(onHoldStudents ?? 0)
  };
}

export async function getNextStudentNumber() {
  const studentsTable = await getStudentsTable();
  const currentYear = new Date().getFullYear();

  if (!studentsTable) {
    return `${currentYear}-0001`;
  }

  const latest = await queryOne<{ student_no: string }>(
    `select student_no
     from ${studentsTable}
     where student_no ~ $1
     order by student_no desc
     limit 1`,
    [`^${currentYear}-[0-9]+$`]
  );

  if (!latest?.student_no) {
    return `${currentYear}-0001`;
  }

  const match = latest.student_no.match(/^(\d{4})-(\d+)$/);
  if (!match) {
    return `${currentYear}-0001`;
  }

  const nextNumber = String(Number(match[2]) + 1).padStart(Math.max(match[2].length, 4), "0");
  return `${match[1]}-${nextNumber}`;
}

export async function listInstructors(search = "") {
  const instructorsTable = await getInstructorsTable();
  if (!instructorsTable) {
    return [];
  }

  const baseRows = search.trim()
    ? await query<{
        id: string | number;
        employee_no: string;
        first_name: string;
        last_name: string;
        department: string | null;
      }>(
      `select id, employee_no, first_name, last_name, department
       from ${instructorsTable}
       where employee_no ilike $1 or first_name ilike $1 or last_name ilike $1 or department ilike $1
       order by last_name, first_name`,
      [`%${search.trim()}%`]
    )
    : await query<{
        id: string | number;
        employee_no: string;
        first_name: string;
        last_name: string;
        department: string | null;
      }>(`select id, employee_no, first_name, last_name, department from ${instructorsTable} order by last_name, first_name`);

  if (baseRows.length === 0) {
    return baseRows;
  }

  const assignmentsTable = await getInstructorAssignmentsTable();
  const classesTable = await getClassesTable();
  const employeeNos = baseRows.map((row) => row.employee_no).filter(Boolean);
  const assignmentMap = new Map<string, Array<{ class_id: number; class_code: string; title: string }>>();

  if (assignmentsTable && classesTable && employeeNos.length > 0) {
    const assignments = await query<{
      instructor_employee_no: string;
      class_id: number;
      class_code: string;
      title: string;
    }>(
      `select assignments.instructor_employee_no, classes.id as class_id, classes.class_code, classes.title
       from ${assignmentsTable} as assignments
       join ${classesTable} as classes on classes.id = assignments.class_id
       where assignments.instructor_employee_no = any($1)
       order by classes.class_code`,
      [employeeNos]
    );

    assignments.forEach((assignment) => {
      const current = assignmentMap.get(assignment.instructor_employee_no) ?? [];
      current.push({
        class_id: Number(assignment.class_id),
        class_code: assignment.class_code,
        title: assignment.title
      });
      assignmentMap.set(assignment.instructor_employee_no, current);
    });
  }

  return baseRows.map((row) => ({
    ...row,
    assigned_classes: assignmentMap.get(row.employee_no) ?? []
  }));
}

export async function listClasses(course = "") {
  const classesTable = await getClassesTable();
  const schedulesTable = await getSchedulesTable();
  if (!classesTable || !schedulesTable) {
    return [];
  }

  return query(
    `select classes.id, classes.class_code, classes.title, classes.course, classes.units,
            schedules.day, schedules.time, schedules.room
     from ${classesTable} as classes
     left join ${schedulesTable} as schedules on schedules.class_id = classes.id
     ${course.trim() ? "where classes.course = $1" : ""}
     order by classes.class_code`,
    course.trim() ? [course.trim()] : []
  );
}

export async function getClassFilters() {
  const schedulesTable = await getSchedulesTable();
  const classesTable = await getClassesTable();
  if (!schedulesTable || !classesTable) {
    return { days: [], times: [], rooms: [], courses: [] };
  }

  const [days, times, rooms, courses] = await Promise.all([
    query<{ day: string }>(`select distinct day from ${schedulesTable} where coalesce(day, '') <> '' order by day`),
    query<{ time: string }>(`select distinct time from ${schedulesTable} where coalesce(time, '') <> '' order by time`),
    query<{ room: string }>(`select distinct room from ${schedulesTable} where coalesce(room, '') <> '' order by room`),
    query<{ course: string }>(`select distinct course from ${classesTable} where coalesce(course, '') <> '' order by course`)
  ]);

  return {
    days: days.map((row) => row.day),
    times: times.map((row) => row.time),
    rooms: rooms.map((row) => row.room),
    courses: courses.map((row) => row.course)
  };
}

export async function listSchedules(search = "", day = "", room = "") {
  const schedulesTable = await getSchedulesTable();
  const classesTable = await getClassesTable();
  if (!schedulesTable || !classesTable) {
    return [];
  }

  const conditions: string[] = [];
  const params: unknown[] = [];

  if (search.trim()) {
    params.push(`%${search.trim()}%`);
    conditions.push(`(classes.class_code ilike $${params.length} or classes.title ilike $${params.length} or schedules.room ilike $${params.length})`);
  }
  if (day.trim()) {
    params.push(day.trim());
    conditions.push(`schedules.day = $${params.length}`);
  }
  if (room.trim()) {
    params.push(room.trim());
    conditions.push(`schedules.room = $${params.length}`);
  }

  return query(
    `select schedules.id, classes.id as class_id, classes.class_code, classes.title, classes.course,
            schedules.day, schedules.time, schedules.room
     from ${schedulesTable} as schedules
     join ${classesTable} as classes on schedules.class_id = classes.id
     ${conditions.length ? `where ${conditions.join(" and ")}` : ""}
     order by schedules.created_at desc`,
    params
  );
}

export async function getClassRoster(classId: number) {
  const classesTable = await getClassesTable();
  const schedulesTable = await getSchedulesTable();
  const enrollmentsTable = await getEnrollmentsTable();
  const studentsTable = await getStudentsTable();
  const gradesTable = await getGradesTable();
  if (!classesTable || !schedulesTable || !enrollmentsTable || !studentsTable || !gradesTable) {
    return { classInfo: null, roster: [] };
  }

  const classInfo = await queryOne(
    `select classes.id, classes.class_code, classes.title, classes.course, classes.units,
            schedules.day, schedules.time, schedules.room
     from ${classesTable} as classes
     left join ${schedulesTable} as schedules on schedules.class_id = classes.id
     where classes.id = $1
     limit 1`,
    [classId]
  );

  const roster = await query(
    `select enrollments.id as enrollment_id, enrollments.status as enrollment_status,
            students.id as student_id, students.student_no, students.first_name, students.last_name, students.program, students.year_level,
            grades.id as grade_id, grades.grade, grades.remarks
     from ${enrollmentsTable} as enrollments
     join ${studentsTable} as students on students.id = enrollments.student_id
     left join ${gradesTable} as grades on grades.student_id = enrollments.student_id and grades.class_id = enrollments.class_id
     where enrollments.class_id = $1
     order by students.last_name, students.first_name`,
    [classId]
  );

  return { classInfo, roster };
}

export async function listClassListSummary(search = "") {
  const classesTable = await getClassesTable();
  const enrollmentsTable = await getEnrollmentsTable();
  const classListsTable = await getClassListsTable();
  const schedulesTable = await getSchedulesTable();
  if (!classesTable || !enrollmentsTable) {
    return [];
  }

  const sourceTable = classListsTable ? `${classListsTable} as class_lists` : `${classesTable} as class_lists`;
  const classJoin = classListsTable ? "class_lists.class_id" : "class_lists.id";
  const params = search.trim() ? [`%${search.trim()}%`] : [];

  return query(
    `select classes.id,
            classes.class_code,
            classes.title,
            classes.course,
            schedules.day,
            schedules.time,
            schedules.room,
            sum(case when enrollments.status = 'Enrolled' then 1 else 0 end) as enrolled_students,
            count(enrollments.id) as total_students
     from ${sourceTable}
     join ${classesTable} as classes on classes.id = ${classJoin}
     left join ${schedulesTable} as schedules on schedules.class_id = classes.id
     left join ${enrollmentsTable} as enrollments on classes.id = enrollments.class_id
     ${search.trim() ? "where classes.class_code ilike $1 or classes.title ilike $1" : ""}
     group by classes.id, classes.class_code, classes.title, classes.course, schedules.day, schedules.time, schedules.room
     order by classes.class_code`,
    params
  );
}

export async function listAvailableClassesForClassLists() {
  const classesTable = await getClassesTable();
  const classListsTable = await getClassListsTable();
  if (!classesTable) {
    return [];
  }

  if (!classListsTable) {
    return query<{ id: number; class_code: string; title: string }>(
      `select id, class_code, title
       from ${classesTable}
       order by class_code`
    );
  }

  return query<{ id: number; class_code: string; title: string }>(
    `select classes.id, classes.class_code, classes.title
     from ${classesTable} as classes
     left join ${classListsTable} as class_lists on class_lists.class_id = classes.id
     where class_lists.class_id is null
     order by classes.class_code`
  );
}

export async function listEnrollments() {
  const enrollmentsTable = await getEnrollmentsTable();
  const studentsTable = await getStudentsTable();
  const classesTable = await getClassesTable();
  if (!enrollmentsTable || !studentsTable || !classesTable) {
    return [];
  }

  const hasDeletedAt = await hasColumn(enrollmentsTable, "deleted_at");
  const hasCashierEnrollmentFeed = await hasTable("cashier_registrar_student_enrollment_feed");
  const hasAcademicYearColumn = await hasColumn(enrollmentsTable, "academic_year");
  const hasSemesterColumn = await hasColumn(enrollmentsTable, "semester");
  const hasTuitionFeeColumn = await hasColumn(enrollmentsTable, "tuition_fee");
  const hasDownpaymentAmountColumn = await hasColumn(enrollmentsTable, "downpayment_amount");
  const hasMedicalFeeColumn = await hasColumn(enrollmentsTable, "medical_fee");
  const hasIdFeeColumn = await hasColumn(enrollmentsTable, "id_fee");

  const cashierFeedJoin = hasCashierEnrollmentFeed
    ? `left join lateral (
         select feed.status
         from public.cashier_registrar_student_enrollment_feed as feed
         where feed.student_no = students.student_no
           and (
             ${hasAcademicYearColumn ? "coalesce(feed.academic_year, '') = '' or feed.academic_year = enrollments.academic_year" : "true"}
           )
           and (
             ${hasSemesterColumn ? "coalesce(feed.semester, '') = '' or feed.semester = enrollments.semester" : "true"}
           )
         order by coalesce(feed.action_at, feed.sent_at, feed.created_at) desc nulls last, feed.id desc
         limit 1
       ) as cashier_feed on true`
    : "";

  return query(
    `select enrollments.id, enrollments.student_id, enrollments.class_id,
            ${hasCashierEnrollmentFeed ? "coalesce(nullif(cashier_feed.status, ''), enrollments.status)" : "enrollments.status"} as status,
            enrollments.created_at,
            ${hasAcademicYearColumn ? "enrollments.academic_year," : "'' as academic_year,"}
            ${hasSemesterColumn ? "enrollments.semester," : "'' as semester,"}
            ${hasTuitionFeeColumn ? "enrollments.tuition_fee," : "0::numeric as tuition_fee,"}
            ${hasDownpaymentAmountColumn ? "enrollments.downpayment_amount," : "0::numeric as downpayment_amount,"}
            ${hasMedicalFeeColumn ? "enrollments.medical_fee," : "0::numeric as medical_fee,"}
            ${hasIdFeeColumn ? "enrollments.id_fee," : "0::numeric as id_fee,"}
            students.student_no, students.first_name, students.last_name, students.program, students.year_level,
            classes.class_code, classes.title, classes.course
     from ${enrollmentsTable} as enrollments
     join ${studentsTable} as students on students.id = enrollments.student_id
     join ${classesTable} as classes on classes.id = enrollments.class_id
     ${cashierFeedJoin}
     ${hasDeletedAt ? "where enrollments.deleted_at is null" : ""}
     order by enrollments.created_at desc`
  );
}

export async function listEnrollmentBin() {
  const enrollmentsTable = await getEnrollmentsTable();
  const studentsTable = await getStudentsTable();
  const classesTable = await getClassesTable();
  if (!enrollmentsTable || !studentsTable || !classesTable) {
    return [];
  }
  if (!(await hasColumn(enrollmentsTable, "deleted_at"))) {
    return [];
  }

  return query(
    `select enrollments.id, enrollments.student_id, enrollments.class_id, enrollments.status, enrollments.created_at,
            enrollments.deleted_at,
            ${await hasColumn(enrollmentsTable, "academic_year") ? "enrollments.academic_year," : "'' as academic_year,"}
            ${await hasColumn(enrollmentsTable, "semester") ? "enrollments.semester," : "'' as semester,"}
            ${await hasColumn(enrollmentsTable, "tuition_fee") ? "enrollments.tuition_fee," : "0::numeric as tuition_fee,"}
            ${await hasColumn(enrollmentsTable, "downpayment_amount") ? "enrollments.downpayment_amount," : "0::numeric as downpayment_amount,"}
            ${await hasColumn(enrollmentsTable, "medical_fee") ? "enrollments.medical_fee," : "0::numeric as medical_fee,"}
            ${await hasColumn(enrollmentsTable, "id_fee") ? "enrollments.id_fee," : "0::numeric as id_fee,"}
            students.student_no, students.first_name, students.last_name,
            classes.class_code, classes.title
     from ${enrollmentsTable} as enrollments
     join ${studentsTable} as students on students.id = enrollments.student_id
     join ${classesTable} as classes on classes.id = enrollments.class_id
     where enrollments.deleted_at is not null
     order by enrollments.deleted_at desc nulls last, enrollments.created_at desc`
  );
}

export async function listGrades() {
  const gradesTable = await getGradesTable();
  const studentsTable = await getStudentsTable();
  const classesTable = await getClassesTable();
  if (!gradesTable || !studentsTable || !classesTable) {
    return [];
  }

  return query(
    `select grades.id, grades.student_id, grades.class_id, ${await hasColumn(gradesTable, "semester") ? "coalesce(grades.semester, '')" : "''"} as semester, grades.grade, grades.remarks,
            students.student_no, students.first_name, students.last_name,
            classes.class_code, classes.title,
            avg(
              case
                when trim(coalesce(grades.grade, '')) ~ '^[0-9]+(\.[0-9]+)?$'
                then cast(grades.grade as numeric)
                else null
              end
            ) over (partition by grades.student_id) as student_average
     from ${gradesTable} as grades
     join ${studentsTable} as students on students.id = grades.student_id
     join ${classesTable} as classes on classes.id = grades.class_id
     order by grades.created_at desc`
  );
}

export async function listDocuments() {
  const documentsTable = await resolveTableName("documents", "registrar_documents");
  const studentsTable = await resolveTableName("registrar_students", "students");

  if (!documentsTable || !studentsTable) {
    return [];
  }

  return query(
    `select documents.id, documents.student_id, documents.doc_type, documents.status, documents.requested_at, documents.completed_at,
            students.student_no, students.first_name, students.last_name
     from ${documentsTable} as documents
     join ${studentsTable} as students on students.id = documents.student_id
     order by documents.requested_at desc`
  );
}

export async function listUsers() {
  const usersTable = await getUsersTable();
  const rolesTable = await getRolesTable();
  if (!usersTable || !rolesTable) {
    return [];
  }

  return query(
    `select users.id, users.role_id, users.username, users.first_name, users.last_name, users.is_active, roles.name as role
     from ${usersTable} as users
     join ${rolesTable} as roles on roles.id = users.role_id
     order by users.created_at desc`
  );
}

export async function listRoles() {
  const rolesTable = await getRolesTable();
  if (!rolesTable) {
    return [];
  }

  return query(`select id, name from ${rolesTable} order by name`);
}

export async function listReports() {
  const reportsTable = await resolveTableName("reports", "registrar_reports");
  if (!reportsTable) {
    return [];
  }

  let orderBy = "created_at desc";
  if (!(await hasColumn(reportsTable, "created_at"))) {
    orderBy = (await hasColumn(reportsTable, "id")) ? "id desc" : "1";
  }

  try {
    return await query(`select * from ${reportsTable} order by ${orderBy}`);
  } catch {
    return [];
  }
}

export async function getReportMetrics() {
  const documentsTable = await resolveTableName("documents", "registrar_documents");
  const usersTable = await getUsersTable();
  const studentsTable = await getStudentsTable();
  const classesTable = await getClassesTable();
  const enrollmentsTable = await getEnrollmentsTable();
  const gradesTable = await getGradesTable();
  const auditLogsTable = await getAuditLogsTable();
  const [users, students, classes, enrollments, grades, documents, logs] = await Promise.all([
    usersTable ? queryValue<number>(`select count(*) from ${usersTable}`) : Promise.resolve(0),
    studentsTable ? queryValue<number>(`select count(*) from ${studentsTable}`) : Promise.resolve(0),
    classesTable ? queryValue<number>(`select count(*) from ${classesTable}`) : Promise.resolve(0),
    enrollmentsTable ? queryValue<number>(`select count(*) from ${enrollmentsTable}`) : Promise.resolve(0),
    gradesTable ? queryValue<number>(`select count(*) from ${gradesTable}`) : Promise.resolve(0),
    documentsTable ? queryValue<number>(`select count(*) from ${documentsTable}`) : Promise.resolve(0),
    auditLogsTable ? queryValue<number>(`select count(*) from ${auditLogsTable}`) : Promise.resolve(0)
  ]);

  return {
    workflowStats: {
      "access-setup": Number(users ?? 0),
      "student-intake": Number(students ?? 0),
      "class-planning": Number(classes ?? 0),
      "enrollment-validation": Number(enrollments ?? 0),
      "grade-posting": Number(grades ?? 0),
      "completion-services": Number(documents ?? 0),
      "compliance-reports": Number(logs ?? 0)
    }
  };
}

export async function listAcademicReports() {
  const reportsTable = await getAcademicReportsTable();
  if (!reportsTable) {
    return [];
  }

  return query(
    `select academic_reports.id,
            academic_reports.title,
            academic_reports.coverage,
            academic_reports.status,
            academic_reports.created_at,
            null::text as report_type,
            null::text as semester,
            null::text as authorization_status,
            null::text as output_format,
            null::numeric as gpa,
            null::bigint as student_id,
            null::text as student_no,
            null::text as first_name,
            null::text as last_name
     from ${reportsTable} as academic_reports
     order by academic_reports.created_at desc`
  );
}

export async function listAuditLogs() {
  const auditLogsTable = await getAuditLogsTable();
  if (!auditLogsTable) {
    return [];
  }

  return query(
    `select audit_logs.id, audit_logs.created_at, audit_logs.action, audit_logs.module, audit_logs.details, users.username
     from ${auditLogsTable} as audit_logs
     left join admin_users as users on users.id = audit_logs.user_id
     order by audit_logs.created_at desc
     limit 100`
  );
}

export async function listNotifications(limit = 6) {
  const notificationsTable = await getNotificationsTable();
  if (!notificationsTable) {
    return [];
  }

  const hasCreated = await hasColumn(notificationsTable, "created_at");
  const orderCol = hasCreated
    ? "created_at"
    : (await hasColumn(notificationsTable, "id"))
      ? "id"
      : null;
  if (!orderCol) {
    return [];
  }

  const createdExpr = hasCreated ? "created_at" : "null::timestamptz";

  try {
    return await query(
      `select id, title, message, status, ${createdExpr} as created_at
       from ${notificationsTable}
       order by ${orderCol} desc
       limit $1`,
      [limit]
    );
  } catch {
    return [];
  }
}

export async function getUnreadNotificationCount() {
  const notificationsTable = await getNotificationsTable();
  if (!notificationsTable) {
    return 0;
  }
  try {
    const count = await queryValue<number>(
      `select count(*) from ${notificationsTable} where status = 'Unread'`
    );
    return Number(count ?? 0);
  } catch {
    return 0;
  }
}

export async function listIntegrationRecords(filters?: { recordType?: string; studentNo?: string; limit?: number }) {
  const integrationRecordsTable = await resolveTableName("integration_records");
  const studentsTable = await resolveTableName("registrar_students", "students");
  if (!integrationRecordsTable) {
    return [];
  }
  const clauses: string[] = [];
  const params: unknown[] = [];

  if (filters?.recordType?.trim()) {
    params.push(filters.recordType.trim());
    clauses.push(`${integrationRecordsTable}.record_type = $${params.length}`);
  }

  if (filters?.studentNo?.trim()) {
    params.push(filters.studentNo.trim());
    clauses.push(`students.student_no = $${params.length}`);
  }

  params.push(Number(filters?.limit ?? 20));

  return query(
    `select ${integrationRecordsTable}.id, ${integrationRecordsTable}.record_type, ${integrationRecordsTable}.source_office,
            ${integrationRecordsTable}.reference_no, ${integrationRecordsTable}.external_status, ${integrationRecordsTable}.title,
            ${integrationRecordsTable}.notes, ${integrationRecordsTable}.received_at,
            ${studentsTable ? "students.student_no, students.first_name, students.last_name" : "null as student_no, null as first_name, null as last_name"}
     from ${integrationRecordsTable}
     ${studentsTable ? `left join ${studentsTable} as students on students.id = ${integrationRecordsTable}.student_id` : ""}
     ${clauses.length ? `where ${clauses.join(" and ")}` : ""}
     order by ${integrationRecordsTable}.received_at desc
     limit $${params.length}`,
    params
  );
}

export async function getIntegrationSummary() {
  const manifest = await import("./integration-catalog").then(({ buildIntegrationManifest }) => buildIntegrationManifest());
  const integrationRecordsTable = await resolveTableName("integration_records");
  if (!integrationRecordsTable) {
    return {
      incomingOffices: 0,
      outgoingFeeds: manifest.outgoing.length,
      recordsReceived: 0
    };
  }
  const [incoming, outgoing, received] = await Promise.all([
    queryValue<number>(`select count(distinct source_office) from ${integrationRecordsTable}`),
    Promise.resolve(manifest.outgoing.length),
    queryValue<number>(`select count(*) from ${integrationRecordsTable}`)
  ]);

  return {
    incomingOffices: Number(incoming ?? 0),
    outgoingFeeds: Number(outgoing),
    recordsReceived: Number(received ?? 0)
  };
}

export async function getUserProfile(userId: number) {
  const account = await queryOne<{
    id: number;
    username: string;
    email: string | null;
    full_name: string | null;
    role: string;
    is_super_admin: number | boolean | null;
  }>(
    `select id, username, email, full_name, role, is_super_admin
     from admin_users
     where id = $1
     limit 1`,
    [userId]
  );

  if (account) {
    const name = splitFullName(account.full_name ?? "");
    return {
      id: account.id,
      username: account.username,
      first_name: name.firstName,
      last_name: name.lastName,
      display_name: account.full_name ?? account.username,
      profile_photo: "",
      profile_title: account.role,
      profile_bio: account.email ?? "",
      profile_accent: normalizeRole(account.role, Boolean(Number(account.is_super_admin ?? 0))) === "admin" ? "#8B1E3F" : "#2F6BD9",
      role: normalizeRole(account.role, Boolean(Number(account.is_super_admin ?? 0)))
    };
  }

  const usersTable = await getUsersTable();
  const rolesTable = await getRolesTable();
  if (!usersTable || !rolesTable) {
    return null;
  }

  return queryOne(
    `select users.id, users.username, users.first_name, users.last_name, users.display_name,
            users.profile_photo, users.profile_title, users.profile_bio, users.profile_accent,
            roles.name as role
     from ${usersTable} as users
     join ${rolesTable} as roles on roles.id = users.role_id
     where users.id = $1
     limit 1`,
    [userId]
  );
}

export async function listStaffDirectory(search = "") {
  const usersTable = await getUsersTable();
  const rolesTable = await getRolesTable();
  if (!usersTable || !rolesTable) {
    return [];
  }

  return query(
    `select users.id, users.first_name, users.last_name, users.username, users.is_active, roles.name as role
     from ${usersTable} as users
     join ${rolesTable} as roles on roles.id = users.role_id
     ${search.trim() ? "where users.username ilike $1 or users.first_name ilike $1 or users.last_name ilike $1 or roles.name ilike $1" : ""}
     order by roles.name, users.last_name, users.first_name`,
    search.trim() ? [`%${search.trim()}%`] : []
  );
}

export async function getStaffDirectoryMetrics() {
  const usersTable = await getUsersTable();
  const rolesTable = await getRolesTable();
  if (!usersTable || !rolesTable) {
    return { activeCount: 0, adminCount: 0 };
  }

  const [activeCount, adminCount] = await Promise.all([
    queryValue<number>(`select count(*) from ${usersTable} where is_active = true`),
    queryValue<number>(`select count(*) from ${usersTable} as users join ${rolesTable} as roles on users.role_id = roles.id where lower(roles.name) = 'admin'`)
  ]);

  return {
    activeCount: Number(activeCount ?? 0),
    adminCount: Number(adminCount ?? 0)
  };
}

export async function getExportRows(workflowKey = "") {
  const documentsTable = workflowKey === "completion-services" ? await resolveTableName("documents", "registrar_documents") : null;
  const reportsTable = workflowKey === "" ? await resolveTableName("reports", "registrar_reports") : null;
  const studentsTable = workflowKey === "completion-services" ? await resolveTableName("registrar_students", "students") : null;
  const enrollmentsTable = workflowKey === "enrollment-validation" ? await getEnrollmentsTable() : null;
  const classesTable = workflowKey === "enrollment-validation" || workflowKey === "grade-posting" || workflowKey === "class-planning" ? await getClassesTable() : null;
  const gradesTable = workflowKey === "enrollment-validation" || workflowKey === "grade-posting" ? await getGradesTable() : null;
  const exportStudentsTable = workflowKey === "enrollment-validation" || workflowKey === "grade-posting" ? await getStudentsTable() : studentsTable;
  const usersTable = workflowKey === "access-setup" || workflowKey === "compliance-reports" ? await getUsersTable() : null;
  const rolesTable = workflowKey === "access-setup" ? await getRolesTable() : null;
  const auditLogsTable = workflowKey === "compliance-reports" ? await getAuditLogsTable() : null;
  switch (workflowKey) {
    case "access-setup":
      return {
        title: workflowTemplates[workflowKey].title,
        columns: ["Username", "Name", "Role", "Status", "Created"],
        rows: usersTable && rolesTable
          ? await query(
          `select users.username,
                  concat(users.first_name, ' ', users.last_name) as full_name,
                  roles.name as role,
                  case when users.is_active = true then 'Active' else 'Inactive' end as account_status,
                  users.created_at
           from ${usersTable} as users
           join ${rolesTable} as roles on users.role_id = roles.id
           order by roles.name, users.last_name, users.first_name`
        )
          : []
      };
    case "student-intake":
      return {
        title: workflowTemplates[workflowKey].title,
        columns: ["Student No", "Name", "Program", "Year", "Status"],
        rows: exportStudentsTable
          ? await query(
              `select student_no,
                      concat(last_name, ', ', first_name) as full_name,
                      program,
                      year_level,
                      status
               from ${exportStudentsTable}
               order by last_name, first_name`
            )
          : []
      };
    case "class-planning":
      return {
        title: workflowTemplates[workflowKey].title,
        columns: ["Subject Code", "Subject Title", "Course", "Units", "Created"],
        rows: classesTable ? await query(`select class_code, title, course, units, created_at from ${classesTable} order by class_code`) : []
      };
    case "enrollment-validation":
      return {
        title: workflowTemplates[workflowKey].title,
        columns: ["Student", "Subject", "Status", "Semester", "Created"],
        rows: enrollmentsTable && exportStudentsTable && classesTable
          ? await query(
          `select concat(students.student_no, ' - ', students.last_name, ', ', students.first_name) as student_label,
                  concat(classes.class_code, ' - ', classes.title) as subject_label,
                  enrollments.status,
                  '' as semester_label,
                  enrollments.created_at
           from ${enrollmentsTable} as enrollments
           join ${exportStudentsTable} as students on students.id = enrollments.student_id
           join ${classesTable} as classes on classes.id = enrollments.class_id
           ${gradesTable ? `left join ${gradesTable} as grades on grades.student_id = enrollments.student_id and grades.class_id = enrollments.class_id` : ""}
           order by enrollments.created_at desc`
        )
          : []
      };
    case "grade-posting":
      return {
        title: workflowTemplates[workflowKey].title,
        columns: ["Student", "Semester", "Subject", "Grade", "Remarks"],
        rows: gradesTable && exportStudentsTable && classesTable
          ? await query(
          `select concat(students.student_no, ' - ', students.last_name, ', ', students.first_name) as student_label,
                  '' as semester,
                  concat(classes.class_code, ' - ', classes.title) as subject_label,
                  grades.grade,
                  grades.remarks
           from ${gradesTable} as grades
           join ${exportStudentsTable} as students on students.id = grades.student_id
           join ${classesTable} as classes on classes.id = grades.class_id
           order by students.last_name, students.first_name, classes.class_code`
        )
          : []
      };
    case "completion-services":
      return {
        title: workflowTemplates[workflowKey].title,
        columns: ["Student", "Document Type", "Status", "Requested", "Completed"],
        rows: documentsTable && studentsTable
          ? await query(
              `select concat(students.student_no, ' - ', students.last_name, ', ', students.first_name) as student_label,
                      documents.doc_type,
                      documents.status,
                      documents.requested_at,
                      documents.completed_at
               from ${documentsTable} as documents
               join ${studentsTable} as students on students.id = documents.student_id
               order by documents.requested_at desc`
            )
          : []
      };
    case "compliance-reports":
      return {
        title: workflowTemplates[workflowKey].title,
        columns: ["User", "Action", "Module", "Details", "Created"],
        rows: auditLogsTable
          ? await query(
          `select coalesce(nullif(trim(users.full_name), ''), nullif(trim(users.username), ''), 'System') as user_name,
                  audit_logs.action,
                  audit_logs.module,
                  audit_logs.details,
                  audit_logs.created_at
           from ${auditLogsTable} as audit_logs
           left join admin_users as users on users.id = audit_logs.user_id
           order by audit_logs.created_at desc`
        )
          : []
      };
    default:
      return {
        title: "Registrar Reports Export",
        columns: ["Title", "Department", "Status", "Due Date", "Created"],
        rows: reportsTable ? await query(`select title, department, status, due_date, created_at from ${reportsTable} order by created_at desc`) : []
      };
  }
}
