import { pool, queryOne, queryValue, resolveTableName } from "@/lib/db";
import { listIntegrationRecords, listStudents } from "@/lib/data";

export async function getIntegrationPayload(resource: string, options: { studentNo?: string; studentId?: number } = {}) {
  const studentNo = String(options.studentNo ?? "").trim();
  const studentId = Number(options.studentId ?? 0);
  const studentsTable = await resolveTableName("registrar_students", "students");
  const enrollmentsTable = await resolveTableName("registrar_enrollments", "enrollments");
  const classesTable = await resolveTableName("registrar_classes", "classes");
  const gradesTable = await resolveTableName("registrar_grades", "grades");
  const incomingRecordTypeMap: Record<string, string> = {
    "payment-confirmations": "payment_confirmation",
    "medical-clearances": "medical_clearance",
    "counseling-reports": "counseling_report",
    "discipline-records": "discipline_record",
    "activity-participation-records": "activity_participation_record"
  };

  if (resource in incomingRecordTypeMap) {
    return {
      records: await listIntegrationRecords({
        recordType: incomingRecordTypeMap[resource],
        studentNo,
        limit: 50
      })
    };
  }

  if (resource === "student-list") {
    const students = await listStudents();
    return { students };
  }

  if (resource === "enrollment-statistics") {
    const [students, enrollments, classes] = await Promise.all([
      studentsTable ? queryValue<number>(`select count(*) from ${studentsTable}`) : Promise.resolve(0),
      enrollmentsTable ? queryValue<number>(`select count(*) from ${enrollmentsTable}`) : Promise.resolve(0),
      classesTable ? queryValue<number>(`select count(*) from ${classesTable}`) : Promise.resolve(0)
    ]);
    return { students: Number(students ?? 0), enrollments: Number(enrollments ?? 0), classes: Number(classes ?? 0) };
  }

  const student = studentNo
    ? studentsTable ? await queryOne(`select * from ${studentsTable} where student_no = $1 limit 1`, [studentNo]) : null
    : studentId > 0
      ? studentsTable ? await queryOne(`select * from ${studentsTable} where id = $1 limit 1`, [studentId]) : null
      : null;

  if (!student) {
    throw new Error("Student not found.");
  }

  if (resource === "student-personal-info") {
    return { student };
  }
  if (resource === "enrollment-data") {
    if (!enrollmentsTable || !classesTable) {
      return { student, enrollments: [] };
    }
    const rows = await pool.query(
      `select enrollments.*, classes.class_code, classes.title
       from ${enrollmentsTable} as enrollments join ${classesTable} as classes on classes.id = enrollments.class_id
       where enrollments.student_id = $1
       order by enrollments.created_at desc`,
      [student.id]
    );
    return { student, enrollments: rows.rows };
  }
  if (resource === "student-academic-records") {
    if (!gradesTable || !classesTable) {
      return { student, grades: [] };
    }
    const grades = await pool.query(
      `select grades.*, classes.class_code, classes.title
       from ${gradesTable} as grades join ${classesTable} as classes on classes.id = grades.class_id
       where grades.student_id = $1
       order by grades.created_at desc`,
      [student.id]
    );
    return { student, grades: grades.rows };
  }

  throw new Error("Unsupported integration resource or method.");
}
