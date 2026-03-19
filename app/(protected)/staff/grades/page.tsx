import { ActionIconButton } from "@/components/action-icon-button";
import { AppShell } from "@/components/app-shell";
import { DataTable } from "@/components/data-table";
import { SectionCard } from "@/components/section-card";
import { requireRole } from "@/lib/auth";
import { createGradeAction, deleteGradeAction, updateGradeAction } from "@/lib/actions";
import { listClasses, listGrades, listStudents } from "@/lib/data";

export default async function GradesPage() {
  const user = await requireRole("Registrar Staff");
  const [grades, students, classes] = await Promise.all([listGrades(), listStudents(), listClasses()]);

  return (
    <AppShell user={user} title="Grade Records" description="Grade creation and maintenance now run fully in the TypeScript registrar module.">
      <div className="content-grid two-col">
        <SectionCard title="Grade Ledger" description="Live grade data with deletion available directly in the translated UI.">
          <DataTable headers={["Student", "Class", "Semester", "Grade", "Remarks", "Actions"]}>
            {grades.map((grade: any) => (
              <tr key={grade.id}>
                <td>{`${String(grade.student_no)} - ${String(grade.last_name)}, ${String(grade.first_name)}`}</td>
                <td>{`${String(grade.class_code)} - ${String(grade.title)}`}</td>
                <td>{String(grade.semester)}</td>
                <td>{String(grade.grade)}</td>
                <td>{String(grade.remarks ?? "-")}</td>
                <td><form action={deleteGradeAction}><input type="hidden" name="id" value={String(grade.id)} /><ActionIconButton kind="delete" label="Delete grade" type="submit" /></form></td>
              </tr>
            ))}
          </DataTable>
        </SectionCard>
        <div className="panel-stack">
          <SectionCard title="Record Grade" description="Add a new grade record.">
            <form className="form-grid" action={createGradeAction}>
              <label className="span-2">Student<select name="student_id" required defaultValue=""><option value="" disabled>Select student</option>{students.map((s: any) => <option key={s.id} value={String(s.id)}>{String(s.student_no)} - {String(s.last_name)}, {String(s.first_name)}</option>)}</select></label>
              <label className="span-2">Class<select name="class_id" required defaultValue=""><option value="" disabled>Select class</option>{classes.map((c: any) => <option key={c.id} value={String(c.id)}>{String(c.class_code)} - {String(c.title)}</option>)}</select></label>
              <label>Semester<input name="semester" required placeholder="1st Semester" /></label>
              <label>Grade<input name="grade" required placeholder="1.25" /></label>
              <label className="span-2">Remarks<input name="remarks" placeholder="Passed / Incomplete" /></label>
              <div className="span-2"><button className="primary" type="submit">Save Grade</button></div>
            </form>
          </SectionCard>
          <SectionCard title="Update Grade" description="Revise an existing grade entry.">
            <form className="form-grid" action={updateGradeAction}>
              <label className="span-2">Grade Record<select name="id" required defaultValue=""><option value="" disabled>Select grade record</option>{grades.map((g: any) => <option key={g.id} value={String(g.id)}>{String(g.student_no)} - {String(g.class_code)} - {String(g.semester)}</option>)}</select></label>
              <label>Grade<input name="grade" required /></label>
              <label className="span-2">Remarks<input name="remarks" /></label>
              <div className="span-2"><button className="primary" type="submit">Update Grade</button></div>
            </form>
          </SectionCard>
        </div>
      </div>
    </AppShell>
  );
}
