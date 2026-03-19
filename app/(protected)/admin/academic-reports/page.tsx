import { ActionIconButton } from "@/components/action-icon-button";
import { AppShell } from "@/components/app-shell";
import { DataTable } from "@/components/data-table";
import { IntegrationSendPanel } from "@/components/integration-send-panel";
import { SectionCard } from "@/components/section-card";
import { StatusBadge } from "@/components/status-badge";
import { requireRole } from "@/lib/auth";
import { createAcademicReportAction, deleteAcademicReportAction, updateAcademicReportAction } from "@/lib/actions";
import { buildIntegrationManifest } from "@/lib/integration-catalog";
import { listAcademicReports, listStudents } from "@/lib/data";

export default async function AcademicReportsPage() {
  const user = await requireRole("Administrator");
  const [reports, students] = await Promise.all([listAcademicReports(), listStudents()]);
  const manifest = buildIntegrationManifest("/api/integrations");
  const academicOutgoing = manifest.outgoing.filter((entry) => entry.key === "student-academic-records");

  return (
    <AppShell user={user} title="Academic Reports" description="Academic report generation and maintenance are now fully handled in TypeScript.">
      <SectionCard title="Academic Record Integrations" description="Guidance-facing academic record feeds are handled from this page.">
        <IntegrationSendPanel
          students={students as Array<{ id: number; student_no: string; first_name: string; last_name: string }>}
          outgoing={academicOutgoing}
        />
      </SectionCard>

      <div className="content-grid two-col">
        <SectionCard title="Academic Report Ledger" description="Current academic report records.">
          <DataTable headers={["Student", "Title", "Type", "Semester", "Status", "GPA", "Actions"]}>
            {reports.map((report: any) => (
              <tr key={report.id}>
                <td>{report.student_no ? `${String(report.student_no)} - ${String(report.last_name)}, ${String(report.first_name)}` : "-"}</td>
                <td>{String(report.title)}</td>
                <td>{String(report.report_type)}</td>
                <td>{String(report.semester)}</td>
                <td><StatusBadge value={String(report.status)} /></td>
                <td>{String(report.gpa ?? "-")}</td>
                <td><form action={deleteAcademicReportAction}><input type="hidden" name="id" value={String(report.id)} /><ActionIconButton kind="delete" label="Delete academic report" type="submit" /></form></td>
              </tr>
            ))}
          </DataTable>
        </SectionCard>
        <div className="panel-stack">
          <SectionCard title="Generate Academic Report" description="Create a new transcript or academic report.">
            <form className="form-grid" action={createAcademicReportAction}>
              <label className="span-2">Student<select name="student_id" required defaultValue=""><option value="" disabled>Select student</option>{students.map((s: any) => <option key={s.id} value={String(s.id)}>{String(s.student_no)} - {String(s.last_name)}, {String(s.first_name)}</option>)}</select></label>
              <label>Title<input name="title" required /></label>
              <label>Coverage<input name="coverage" required placeholder="AY 2025-2026" /></label>
              <label>Report Type<input name="report_type" defaultValue="Transcript" /></label>
              <label>Semester<input name="semester" required defaultValue="1st Semester" /></label>
              <label>Authorization<select name="authorization_status" defaultValue="Approved"><option>Approved</option><option>Pending</option></select></label>
              <label>Output Format<input name="output_format" defaultValue="Transcript / Grade Report" /></label>
              <label>Status<select name="status" defaultValue="Draft"><option>Draft</option><option>Released</option><option>Archived</option></select></label>
              <div className="span-2"><button className="primary" type="submit">Generate Report</button></div>
            </form>
          </SectionCard>
          <SectionCard title="Update Academic Report" description="Revise an existing report entry.">
            <form className="form-grid" action={updateAcademicReportAction}>
              <label className="span-2">Report<select name="id" required defaultValue=""><option value="" disabled>Select report</option>{reports.map((report: any) => <option key={report.id} value={String(report.id)}>{String(report.title)} - {String(report.semester)}</option>)}</select></label>
              <label className="span-2">Student<select name="student_id" required defaultValue=""><option value="" disabled>Select student</option>{students.map((s: any) => <option key={s.id} value={String(s.id)}>{String(s.student_no)} - {String(s.last_name)}, {String(s.first_name)}</option>)}</select></label>
              <label>Title<input name="title" required /></label>
              <label>Coverage<input name="coverage" required /></label>
              <label>Report Type<input name="report_type" defaultValue="Transcript" /></label>
              <label>Semester<input name="semester" required /></label>
              <label>Authorization<select name="authorization_status" defaultValue="Approved"><option>Approved</option><option>Pending</option></select></label>
              <label>Output Format<input name="output_format" defaultValue="Transcript / Grade Report" /></label>
              <label>Status<select name="status" defaultValue="Draft"><option>Draft</option><option>Released</option><option>Archived</option></select></label>
              <div className="span-2"><button className="primary" type="submit">Update Report</button></div>
            </form>
          </SectionCard>
        </div>
      </div>
    </AppShell>
  );
}
