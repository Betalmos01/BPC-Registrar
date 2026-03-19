import { ActionIconButton } from "@/components/action-icon-button";
import { AppShell } from "@/components/app-shell";
import { DataTable } from "@/components/data-table";
import { IntegrationReceivePanel } from "@/components/integration-receive-panel";
import { IntegrationSendPanel } from "@/components/integration-send-panel";
import { SectionCard } from "@/components/section-card";
import { StatusBadge } from "@/components/status-badge";
import { requireRole } from "@/lib/auth";
import { createEnrollmentAction, deleteEnrollmentAction, updateEnrollmentAction } from "@/lib/actions";
import { resolveTableName } from "@/lib/db";
import { buildIntegrationManifest } from "@/lib/integration-catalog";
import { listClasses, listEnrollments, listStudents } from "@/lib/data";

export default async function EnrollmentsPage() {
  const user = await requireRole("Registrar Staff");
  const [enrollments, students, classes, integrationRecordsTable] = await Promise.all([
    listEnrollments(),
    listStudents(),
    listClasses(),
    resolveTableName("integration_records")
  ]);
  const manifest = buildIntegrationManifest("/api/integrations");
  const incomingValidation = manifest.incoming.filter((entry) =>
    [
      "payment-confirmations",
      "medical-clearances",
      "counseling-reports",
      "discipline-records",
      "activity-participation-records"
    ].includes(entry.key)
  );
  const outgoingEnrollment = manifest.outgoing.filter((entry) =>
    ["enrollment-data", "enrollment-statistics"].includes(entry.key)
  );
  const integrationsEnabled = Boolean(integrationRecordsTable);

  return (
    <AppShell user={user} title="Enrollment Monitoring" description="Enrollment creation, updates, and cleanup now run fully in TypeScript.">
      <div className="content-grid">
        <IntegrationReceivePanel
          title="Enrollment Validation Integrations"
          description="Enrollment prerequisites should be received automatically from the connected offices."
          incoming={incomingValidation}
          enabled={integrationsEnabled}
        />

        <SectionCard title="Enrollment Outgoing Feeds" description="Enrollment payloads are sent directly from the enrollment workflow.">
          <IntegrationSendPanel
            students={students as Array<{ id: number; student_no: string; first_name: string; last_name: string }>}
            outgoing={outgoingEnrollment}
          />
        </SectionCard>
      </div>

      <div className="content-grid two-col">
        <SectionCard title="Enrollment Records" description="Current enrollment data from Supabase Postgres.">
          <DataTable headers={["Student", "Class", "Status", "Created", "Actions"]}>
            {enrollments.map((item: any) => (
              <tr key={item.id}>
                <td>{`${String(item.student_no)} - ${String(item.last_name)}, ${String(item.first_name)}`}</td>
                <td>{`${String(item.class_code)} - ${String(item.title)}`}</td>
                <td><StatusBadge value={String(item.status)} /></td>
                <td>{String(item.created_at)}</td>
                <td>
                  <form action={deleteEnrollmentAction}><input type="hidden" name="id" value={String(item.id)} /><ActionIconButton kind="delete" label="Delete enrollment" type="submit" /></form>
                </td>
              </tr>
            ))}
          </DataTable>
        </SectionCard>
        <div className="panel-stack">
          <SectionCard title="Add Enrollment" description="Assign a student to a class.">
            <form className="form-grid" action={createEnrollmentAction}>
              <div className="form-cluster span-2">
                <div className="cluster-title">Enrollment Workflow</div>
              <label className="span-2">Student<select name="student_id" required defaultValue=""><option value="" disabled>Select student</option>{students.map((s: any) => <option key={s.id} value={String(s.id)}>{String(s.student_no)} - {String(s.last_name)}, {String(s.first_name)}</option>)}</select></label>
              <label>Academic Year<select name="academic_year" defaultValue="2025-2026"><option>2024-2025</option><option>2025-2026</option><option>2026-2027</option></select></label>
              <label>Semester<select name="semester" defaultValue="1st Semester"><option>1st Semester</option><option>2nd Semester</option><option>Summer</option></select></label>
              <label className="span-2">Class<select name="class_id" required defaultValue=""><option value="" disabled>Select class</option>{classes.map((c: any) => <option key={c.id} value={String(c.id)}>{String(c.class_code)} - {String(c.title)}</option>)}</select></label>
              <label>Status<select name="status" defaultValue="Enrolled"><option>Enrolled</option><option>Pending</option><option>Waitlisted</option></select></label>
              <div className="span-2 status-row"><div><strong>Validation Gate</strong><div className="status-meta">Enrollment buttons should stay disabled when payment or clearance holds exist.</div></div><StatusBadge value="Pending" /></div>
              </div>
              <div className="actions-row align-end"><button className="primary" type="submit">Save Enrollment</button></div>
            </form>
          </SectionCard>
          <SectionCard title="Update Enrollment" description="Change the status of an existing enrollment.">
            <form className="form-grid" action={updateEnrollmentAction}>
              <label className="span-2">Enrollment<select name="id" required defaultValue=""><option value="" disabled>Select enrollment</option>{enrollments.map((item: any) => <option key={item.id} value={String(item.id)}>{String(item.student_no)} - {String(item.class_code)}</option>)}</select></label>
              <label>Status<select name="status" defaultValue="Enrolled"><option>Enrolled</option><option>Pending</option><option>Waitlisted</option></select></label>
              <div className="actions-row align-end"><button className="primary" type="submit">Update Enrollment</button></div>
            </form>
          </SectionCard>
        </div>
      </div>
    </AppShell>
  );
}
