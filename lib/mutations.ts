import bcrypt from "bcryptjs";
import { pool, queryOne, queryValue, resolveTableName } from "./db";
import { workflowTemplates } from "./data";

async function requireDocumentsTable() {
  const tableName = await resolveTableName("documents", "registrar_documents");
  if (!tableName) {
    throw new Error("The documents table has not been created in this database yet.");
  }
  return tableName;
}

async function requireReportsTable() {
  const tableName = await resolveTableName("reports", "registrar_reports");
  if (!tableName) {
    throw new Error("The reports table has not been created in this database yet.");
  }
  return tableName;
}

async function requireGradesTable() {
  const tableName = await resolveTableName("registrar_grades", "grades");
  if (!tableName) {
    throw new Error("The grades table has not been created in this database yet.");
  }
  return tableName;
}

async function requireClassesTable() {
  const tableName = await resolveTableName("registrar_classes", "classes");
  if (!tableName) {
    throw new Error("The classes table has not been created in this database yet.");
  }
  return tableName;
}

async function requireSchedulesTable() {
  const tableName = await resolveTableName("registrar_schedules", "schedules");
  if (!tableName) {
    throw new Error("The schedules table has not been created in this database yet.");
  }
  return tableName;
}

async function requireEnrollmentsTable() {
  const tableName = await resolveTableName("registrar_enrollments", "enrollments");
  if (!tableName) {
    throw new Error("The enrollments table has not been created in this database yet.");
  }
  return tableName;
}

async function requireStudentsTable() {
  const tableName = await resolveTableName("registrar_students", "students");
  if (!tableName) {
    throw new Error("The students table has not been created in this database yet.");
  }
  return tableName;
}

async function requireUsersTable() {
  const tableName = await resolveTableName("registrar_users", "users");
  if (!tableName) {
    throw new Error("The users table has not been created in this database yet.");
  }
  return tableName;
}

async function requireRolesTable() {
  const tableName = await resolveTableName("registrar_roles", "roles");
  if (!tableName) {
    throw new Error("The roles table has not been created in this database yet.");
  }
  return tableName;
}

async function requireAuditLogsTable() {
  const tableName = await resolveTableName("registrar_audit_logs", "audit_logs");
  if (!tableName) {
    throw new Error("The audit logs table has not been created in this database yet.");
  }
  return tableName;
}

async function requireNotificationsTable() {
  const tableName = await resolveTableName("registrar_notifications", "notifications");
  if (!tableName) {
    throw new Error("The notifications table has not been created in this database yet.");
  }
  return tableName;
}

async function requireAcademicReportsTable() {
  const tableName = await resolveTableName("registrar_academic_reports", "academic_reports");
  if (!tableName) {
    throw new Error("The academic reports table has not been created in this database yet.");
  }
  return tableName;
}

async function logAction(userId: number | null, action: string, moduleName: string, details: string) {
  try {
    const auditLogsTable = await requireAuditLogsTable();
    await pool.query(
      `insert into ${auditLogsTable} (user_id, action, module, details, created_at)
       values ($1, $2, $3, $4, current_timestamp)`,
      [userId, action, moduleName, details]
    );
  } catch {
    // Non-blocking.
  }
}

async function createNotification(title: string, message: string) {
  try {
    const notificationsTable = await requireNotificationsTable();
    await pool.query(
      `insert into ${notificationsTable} (title, message, status, created_at)
       values ($1, $2, 'Unread', current_timestamp)`,
      [title, message]
    );
  } catch {
    // Non-blocking.
  }
}

async function computeGpa(studentId: number) {
  const gradesTable = await requireGradesTable();
  const classesTable = await requireClassesTable();
  const rows = await pool.query(
    `select grades.grade, classes.units
     from ${gradesTable} as grades
     join ${classesTable} as classes on grades.class_id = classes.id
     where grades.student_id = $1`,
    [studentId]
  );

  let weightedTotal = 0;
  let unitTotal = 0;

  for (const row of rows.rows) {
    const grade = String(row.grade ?? "").trim();
    const units = Number(row.units ?? 0);
    if (!grade || Number.isNaN(Number(grade)) || units <= 0) {
      continue;
    }

    weightedTotal += Number(grade) * units;
    unitTotal += units;
  }

  return unitTotal > 0 ? Number((weightedTotal / unitTotal).toFixed(2)) : null;
}

export async function createStudent(input: {
  studentNo: string;
  firstName: string;
  lastName: string;
  program?: string;
  yearLevel?: string;
  status?: string;
  actorId: number;
}) {
  const studentsTable = await requireStudentsTable();
  const exists = await queryOne(`select id from ${studentsTable} where student_no = $1`, [input.studentNo]);
  if (exists) {
    throw new Error("Student No already exists.");
  }

  const result = await pool.query(
    `insert into ${studentsTable} (student_no, first_name, last_name, program, year_level, status, created_at)
     values ($1, $2, $3, $4, $5, $6, current_timestamp)
     returning id`,
    [input.studentNo, input.firstName, input.lastName, input.program ?? "", input.yearLevel ?? "", input.status ?? "Active"]
  );

  await logAction(input.actorId, "Create", "Student Records", `Added student ${input.studentNo}`);
  return result.rows[0]?.id as number;
}

export async function updateStudent(input: {
  id: number;
  studentNo: string;
  firstName: string;
  lastName: string;
  program?: string;
  yearLevel?: string;
  status?: string;
  actorId: number;
}) {
  const studentsTable = await requireStudentsTable();
  const exists = await queryOne(`select id from ${studentsTable} where student_no = $1 and id <> $2`, [input.studentNo, input.id]);
  if (exists) {
    throw new Error("Student No already exists.");
  }

  await pool.query(
    `update ${studentsTable}
     set student_no = $1, first_name = $2, last_name = $3, program = $4, year_level = $5, status = $6
     where id = $7`,
    [input.studentNo, input.firstName, input.lastName, input.program ?? "", input.yearLevel ?? "", input.status ?? "Active", input.id]
  );

  await logAction(input.actorId, "Update", "Student Records", `Updated student ID ${input.id}`);
}

export async function deleteStudent(input: { id: number; actorId: number }) {
  const studentsTable = await requireStudentsTable();
  await pool.query(`delete from ${studentsTable} where id = $1`, [input.id]);
  await logAction(input.actorId, "Delete", "Student Records", `Deleted student ID ${input.id}`);
}

export async function createInstructor(input: {
  employeeNo: string;
  firstName: string;
  lastName: string;
  department?: string;
  actorId: number;
}) {
  const instructorsTable = await resolveTableName("registrar_instructors", "instructors");
  if (!instructorsTable) throw new Error("The instructors table has not been created in this database yet.");
  const exists = await queryOne(`select id from ${instructorsTable} where employee_no = $1`, [input.employeeNo]);
  if (exists) {
    throw new Error("Employee No already exists.");
  }

  const result = await pool.query(
    `insert into ${instructorsTable} (employee_no, first_name, last_name, department, created_at)
     values ($1, $2, $3, $4, current_timestamp)
     returning id`,
    [input.employeeNo, input.firstName, input.lastName, input.department ?? ""]
  );

  await logAction(input.actorId, "Create", "Instructors", `Added instructor ${input.employeeNo}`);
  return result.rows[0]?.id as number;
}

export async function updateInstructor(input: {
  id: number;
  employeeNo: string;
  firstName: string;
  lastName: string;
  department?: string;
  actorId: number;
}) {
  const instructorsTable = await resolveTableName("registrar_instructors", "instructors");
  if (!instructorsTable) throw new Error("The instructors table has not been created in this database yet.");
  const exists = await queryOne(`select id from ${instructorsTable} where employee_no = $1 and id <> $2`, [input.employeeNo, input.id]);
  if (exists) {
    throw new Error("Employee No already exists.");
  }

  await pool.query(
    `update ${instructorsTable} set employee_no = $1, first_name = $2, last_name = $3, department = $4 where id = $5`,
    [input.employeeNo, input.firstName, input.lastName, input.department ?? "", input.id]
  );

  await logAction(input.actorId, "Update", "Instructors", `Updated instructor ID ${input.id}`);
}

export async function deleteInstructor(input: { id: number; actorId: number }) {
  const instructorsTable = await resolveTableName("registrar_instructors", "instructors");
  if (!instructorsTable) throw new Error("The instructors table has not been created in this database yet.");
  await pool.query(`delete from ${instructorsTable} where id = $1`, [input.id]);
  await logAction(input.actorId, "Delete", "Instructors", `Deleted instructor ID ${input.id}`);
}

export async function createClassSchedule(input: {
  classCode: string;
  title: string;
  course?: string;
  units?: number;
  day?: string;
  time?: string;
  room?: string;
  actorId: number;
}) {
  const classesTable = await requireClassesTable();
  const schedulesTable = await requireSchedulesTable();
  const client = await pool.connect();
  try {
    await client.query("begin");
    const classResult = await client.query(
      `insert into ${classesTable} (class_code, title, course, units, created_at)
       values ($1, $2, $3, $4, current_timestamp)
       returning id`,
      [input.classCode, input.title, input.course ?? "", input.units ?? 0]
    );
    const classId = classResult.rows[0]?.id as number;
    await client.query(
      `insert into ${schedulesTable} (class_id, day, time, room, created_at)
       values ($1, $2, $3, $4, current_timestamp)`,
      [classId, input.day ?? "", input.time ?? "", input.room ?? ""]
    );
    await client.query("commit");
    await logAction(input.actorId, "Create", "Classes & Schedules", `Added class ${input.classCode}`);
    return classId;
  } catch (error) {
    await client.query("rollback");
    throw error;
  } finally {
    client.release();
  }
}

export async function updateClassSchedule(input: {
  classId: number;
  classCode: string;
  title: string;
  course?: string;
  units?: number;
  day?: string;
  time?: string;
  room?: string;
  actorId: number;
}) {
  const classesTable = await requireClassesTable();
  const schedulesTable = await requireSchedulesTable();
  const client = await pool.connect();
  try {
    await client.query("begin");
    await client.query(
      `update ${classesTable} set class_code = $1, title = $2, course = $3, units = $4 where id = $5`,
      [input.classCode, input.title, input.course ?? "", input.units ?? 0, input.classId]
    );

    const existingSchedule = await client.query(`select id from ${schedulesTable} where class_id = $1 limit 1`, [input.classId]);
    if (existingSchedule.rows[0]?.id) {
      await client.query(
        `update ${schedulesTable} set day = $1, time = $2, room = $3 where id = $4`,
        [input.day ?? "", input.time ?? "", input.room ?? "", existingSchedule.rows[0].id]
      );
    } else {
      await client.query(
        `insert into ${schedulesTable} (class_id, day, time, room, created_at)
         values ($1, $2, $3, $4, current_timestamp)`,
        [input.classId, input.day ?? "", input.time ?? "", input.room ?? ""]
      );
    }

    await client.query("commit");
    await logAction(input.actorId, "Update", "Classes & Schedules", `Updated class ID ${input.classId}`);
  } catch (error) {
    await client.query("rollback");
    throw error;
  } finally {
    client.release();
  }
}

export async function deleteClassSchedule(input: { classId: number; actorId: number }) {
  const classesTable = await requireClassesTable();
  await pool.query(`delete from ${classesTable} where id = $1`, [input.classId]);
  await logAction(input.actorId, "Delete", "Classes & Schedules", `Deleted class ID ${input.classId}`);
}

export async function createEnrollment(input: {
  studentId: number;
  classId: number;
  status?: string;
  actorId: number;
}) {
  const enrollmentsTable = await requireEnrollmentsTable();
  const result = await pool.query(
    `insert into ${enrollmentsTable} (student_id, class_id, status, created_at)
     values ($1, $2, $3, current_timestamp)
     returning id`,
    [input.studentId, input.classId, input.status ?? "Enrolled"]
  );
  await logAction(input.actorId, "Create", "Enrollment", `Enrolled student ID ${input.studentId} to class ID ${input.classId}`);
  return result.rows[0]?.id as number;
}

export async function updateEnrollment(input: { id: number; status: string; actorId: number }) {
  const enrollmentsTable = await requireEnrollmentsTable();
  await pool.query(`update ${enrollmentsTable} set status = $1 where id = $2`, [input.status, input.id]);
  await logAction(input.actorId, "Update", "Enrollment", `Updated enrollment ID ${input.id}`);
}

export async function deleteEnrollment(input: { id: number; actorId: number }) {
  const enrollmentsTable = await requireEnrollmentsTable();
  await pool.query(`delete from ${enrollmentsTable} where id = $1`, [input.id]);
  await logAction(input.actorId, "Delete", "Enrollment", `Deleted enrollment ID ${input.id}`);
}

export async function createGrade(input: {
  studentId: number;
  classId: number;
  semester: string;
  grade: string;
  remarks?: string;
  actorId: number;
}) {
  const gradesTable = await requireGradesTable();
  const result = await pool.query(
    `insert into ${gradesTable} (student_id, class_id, grade, remarks, created_at)
     values ($1, $2, $3, $4, current_timestamp)
     returning id`,
    [input.studentId, input.classId, input.grade, input.remarks ?? ""]
  );
  await logAction(input.actorId, "Create", "Grades", `Recorded subject grade for student ID ${input.studentId} in ${input.semester}`);
  return result.rows[0]?.id as number;
}

export async function updateGrade(input: { id: number; grade: string; remarks?: string; actorId: number }) {
  const gradesTable = await requireGradesTable();
  await pool.query(`update ${gradesTable} set grade = $1, remarks = $2 where id = $3`, [input.grade, input.remarks ?? "", input.id]);
  await logAction(input.actorId, "Update", "Grades", `Updated grade ID ${input.id}`);
}

export async function deleteGrade(input: { id: number; actorId: number }) {
  const gradesTable = await requireGradesTable();
  await pool.query(`delete from ${gradesTable} where id = $1`, [input.id]);
  await logAction(input.actorId, "Delete", "Grades", `Deleted grade ID ${input.id}`);
}

export async function createDocumentRequest(input: {
  studentId: number;
  docType: string;
  actorId: number;
}) {
  const documentsTable = await requireDocumentsTable();
  const result = await pool.query(
    `insert into ${documentsTable} (student_id, doc_type, status, requested_at)
     values ($1, $2, 'Pending', current_timestamp)
     returning id`,
    [input.studentId, input.docType]
  );
  await createNotification("New Document Request", `A new document request was filed: ${input.docType}.`);
  await logAction(input.actorId, "Create", "Document Requests", `Requested ${input.docType}`);
  return result.rows[0]?.id as number;
}

export async function updateDocumentRequest(input: { id: number; status: string; actorId: number }) {
  const documentsTable = await requireDocumentsTable();
  await pool.query(
    `update ${documentsTable}
     set status = $1,
         completed_at = case when $1 = 'Completed' then current_timestamp else completed_at end
     where id = $2`,
    [input.status, input.id]
  );
  if (input.status.toLowerCase() === "completed") {
    await createNotification("Document Completed", `A document request was marked as Completed (ID ${input.id}).`);
  }
  await logAction(input.actorId, "Update", "Document Requests", `Updated document request ${input.id}`);
}

export async function deleteDocumentRequest(input: { id: number; actorId: number }) {
  const documentsTable = await requireDocumentsTable();
  await pool.query(`delete from ${documentsTable} where id = $1`, [input.id]);
  await logAction(input.actorId, "Delete", "Document Requests", `Deleted document request ${input.id}`);
}

export async function createUserAccount(input: {
  roleId: number;
  username: string;
  password: string;
  firstName: string;
  lastName: string;
  actorId: number;
}) {
  const usersTable = await requireUsersTable();
  const exists = await queryOne(`select id from ${usersTable} where username = $1`, [input.username]);
  if (exists) {
    throw new Error("Username already exists.");
  }

  const result = await pool.query(
    `insert into ${usersTable} (role_id, username, password_hash, first_name, last_name, is_active)
     values ($1, $2, $3, $4, $5, true)
     returning id`,
    [input.roleId, input.username, await bcrypt.hash(input.password, 10), input.firstName, input.lastName]
  );

  await logAction(input.actorId, "Create", "Users", `Created user ${input.username}`);
  return result.rows[0]?.id as number;
}

export async function updateUserAccount(input: {
  id: number;
  roleId: number;
  username: string;
  firstName: string;
  lastName: string;
  actorId: number;
}) {
  const usersTable = await requireUsersTable();
  const exists = await queryOne(`select id from ${usersTable} where username = $1 and id <> $2`, [input.username, input.id]);
  if (exists) {
    throw new Error("Username already exists.");
  }

  await pool.query(
    `update ${usersTable} set role_id = $1, username = $2, first_name = $3, last_name = $4 where id = $5`,
    [input.roleId, input.username, input.firstName, input.lastName, input.id]
  );
  await logAction(input.actorId, "Update", "Users", `Updated user ID ${input.id}`);
}

export async function toggleUserAccount(input: { id: number; isActive: boolean; actorId: number }) {
  if (input.id === input.actorId) {
    throw new Error("You cannot change your own active status.");
  }
  const usersTable = await requireUsersTable();
  await pool.query(`update ${usersTable} set is_active = $1 where id = $2`, [input.isActive, input.id]);
  await logAction(input.actorId, "Update", "Users", `Set active=${input.isActive ? 1 : 0} for user ID ${input.id}`);
}

export async function resetUserPassword(input: { id: number; password: string; actorId: number }) {
  const usersTable = await requireUsersTable();
  await pool.query(`update ${usersTable} set password_hash = $1 where id = $2`, [await bcrypt.hash(input.password, 10), input.id]);
  await logAction(input.actorId, "Update", "Users", `Reset password for user ID ${input.id}`);
}

export async function deleteUserAccount(input: { id: number; actorId: number }) {
  if (input.id === input.actorId) {
    throw new Error("You cannot delete your own account.");
  }

  const usersTable = await requireUsersTable();
  const rolesTable = await requireRolesTable();
  const target = await queryOne<{ role: string; is_active: boolean }>(
    `select roles.name as role, users.is_active
     from ${usersTable} as users join ${rolesTable} as roles on users.role_id = roles.id
     where users.id = $1`,
    [input.id]
  );
  if (!target) {
    throw new Error("User not found.");
  }
  if (target.role.toLowerCase() === "administrator" && target.is_active) {
    const admins = await queryValue<number>(
      `select count(*)
       from ${usersTable} as users join ${rolesTable} as roles on users.role_id = roles.id
       where lower(roles.name) = 'admin' and users.is_active = true`
    );
    if (Number(admins ?? 0) <= 1) {
      throw new Error("Cannot delete the last active administrator.");
    }
  }

  await pool.query(`delete from ${usersTable} where id = $1`, [input.id]);
  await logAction(input.actorId, "Delete", "Users", `Deleted user ID ${input.id}`);
}

export async function createReport(input: {
  title: string;
  department: string;
  status?: string;
  dueDate?: string;
  actorId: number;
}) {
  const reportsTable = await requireReportsTable();
  const result = await pool.query(
    `insert into ${reportsTable} (title, department, status, due_date, created_at)
     values ($1, $2, $3, $4, current_timestamp)
     returning id`,
    [input.title, input.department, input.status ?? "Pending", input.dueDate || null]
  );
  await logAction(input.actorId, "Create", "Reports", `Generated report ${input.title}`);
  return result.rows[0]?.id as number;
}

export async function createWorkflowReport(input: {
  workflowKey: keyof typeof workflowTemplates;
  title?: string;
  department?: string;
  status?: string;
  dueDate?: string;
  actorId: number;
}) {
  const reportsTable = await requireReportsTable();
  const template = workflowTemplates[input.workflowKey];
  if (!template) {
    throw new Error("Unknown registrar workflow report template.");
  }

  const title = input.title?.trim() || `${template.title} - ${new Date().toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" })}`;
  const department = input.department?.trim() || template.department;
  const dueDate = input.dueDate?.trim() || new Date(Date.now() + template.dueInDays * 86400000).toISOString().slice(0, 10);

  const result = await pool.query(
    `insert into ${reportsTable} (title, department, status, due_date, created_at)
     values ($1, $2, $3, $4, current_timestamp)
     returning id`,
    [title, department, input.status ?? "Pending", dueDate]
  );
  await logAction(input.actorId, "Create", "Reports", `Generated workflow report ${title}`);
  return result.rows[0]?.id as number;
}

export async function updateReport(input: {
  id: number;
  title: string;
  department: string;
  status?: string;
  dueDate?: string;
  actorId: number;
}) {
  const reportsTable = await requireReportsTable();
  await pool.query(
    `update ${reportsTable} set title = $1, department = $2, status = $3, due_date = $4 where id = $5`,
    [input.title, input.department, input.status ?? "Pending", input.dueDate || null, input.id]
  );
  await logAction(input.actorId, "Update", "Reports", `Updated report ID ${input.id}`);
}

export async function deleteReport(input: { id: number; actorId: number }) {
  const reportsTable = await requireReportsTable();
  await pool.query(`delete from ${reportsTable} where id = $1`, [input.id]);
  await logAction(input.actorId, "Delete", "Reports", `Deleted report ID ${input.id}`);
}

export async function createAcademicReport(input: {
  studentId: number;
  title: string;
  coverage: string;
  reportType: string;
  semester: string;
  authorizationStatus: string;
  outputFormat: string;
  status: string;
  actorId: number;
}) {
  const academicReportsTable = await requireAcademicReportsTable();
  if (input.authorizationStatus !== "Approved") {
    throw new Error("Authorization must be approved before generating an academic report.");
  }
  const gpa = await computeGpa(input.studentId);
  const result = await pool.query(
    `insert into ${academicReportsTable}
      (title, coverage, status, created_at)
     values ($1, $2, $3, current_timestamp)
     returning id`,
    [input.title, input.coverage, input.status]
  );
  await logAction(input.actorId, "Generate", "Academic Reports", `Generated ${input.reportType} for student ID ${input.studentId} with GPA ${gpa ?? "N/A"}`);
  return result.rows[0]?.id as number;
}

export async function updateAcademicReport(input: {
  id: number;
  studentId: number;
  title: string;
  coverage: string;
  reportType: string;
  semester: string;
  authorizationStatus: string;
  outputFormat: string;
  status: string;
  actorId: number;
}) {
  const academicReportsTable = await requireAcademicReportsTable();
  if (input.authorizationStatus !== "Approved") {
    throw new Error("Authorization must be approved before generating an academic report.");
  }
  const gpa = await computeGpa(input.studentId);
  await pool.query(
    `update ${academicReportsTable}
     set title = $1, coverage = $2, status = $3
     where id = $4`,
    [input.title, input.coverage, input.status, input.id]
  );
  await logAction(input.actorId, "Update", "Academic Reports", `Updated academic report ID ${input.id} for student ID ${input.studentId}`);
}

export async function deleteAcademicReport(input: { id: number; actorId: number }) {
  const academicReportsTable = await requireAcademicReportsTable();
  await pool.query(`delete from ${academicReportsTable} where id = $1`, [input.id]);
  await logAction(input.actorId, "Delete", "Academic Reports", `Deleted academic report ID ${input.id}`);
}

export async function createIntegrationRecord(input: {
  studentNo: string;
  sourceOffice: string;
  recordType: string;
  referenceNo?: string;
  externalStatus?: string;
  title?: string;
  notes?: string;
  actorId: number | null;
}) {
  const integrationRecordsTable = await resolveTableName("integration_records");
  if (!integrationRecordsTable) {
    throw new Error("The integration_records table is not available in this database yet.");
  }
  const studentsTable = await requireStudentsTable();
  const student = await queryOne<{ id: number }>(`select id from ${studentsTable} where student_no = $1 limit 1`, [input.studentNo]);
  if (!student) {
    throw new Error("Student not found.");
  }

  const result = await pool.query(
    `insert into ${integrationRecordsTable}
      (student_id, record_type, source_office, reference_no, external_status, title, notes, payload_json, received_at, created_at)
     values ($1, $2, $3, $4, $5, $6, $7, '{}'::jsonb, current_timestamp, current_timestamp)
     returning id`,
    [student.id, input.recordType, input.sourceOffice, input.referenceNo ?? "", input.externalStatus ?? "Received", input.title ?? "", input.notes ?? ""]
  );

  await logAction(input.actorId, "Create", "Office Integrations", `Stored ${input.recordType} from ${input.sourceOffice}`);
  return result.rows[0]?.id as number;
}

export async function markNotificationRead(input: { id: number }) {
  const notificationsTable = await requireNotificationsTable();
  await pool.query(`update ${notificationsTable} set status = 'Read' where id = $1`, [input.id]);
}

export async function markAllNotificationsRead() {
  const notificationsTable = await requireNotificationsTable();
  await pool.query(`update ${notificationsTable} set status = 'Read' where status = 'Unread'`);
}
