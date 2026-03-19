import { ActionIconButton } from "@/components/action-icon-button";
import { AppShell } from "@/components/app-shell";
import { DataTable } from "@/components/data-table";
import { SectionCard } from "@/components/section-card";
import { StatusBadge } from "@/components/status-badge";
import { WorkflowReportModalGrid } from "@/components/workflow-report-modal-grid";
import { requireRole } from "@/lib/auth";
import { createReportAction, deleteReportAction, updateReportAction } from "@/lib/actions";
import { resolveTableName } from "@/lib/db";
import { getReportMetrics, listReports, workflowTemplates } from "@/lib/data";

export default async function ReportsPage() {
  const user = await requireRole("Administrator");
  const [reports, metrics, reportsTable] = await Promise.all([listReports(), getReportMetrics(), resolveTableName("reports", "registrar_reports")]);
  const reportsEnabled = Boolean(reportsTable);

  return (
    <AppShell user={user} title="Reports" description="Workflow reports, exports, and report queue maintenance now run fully in TypeScript.">
      <SectionCard title="Workflow Report Generator" description="Open exports or generate a workflow report record directly in the translated app.">
        {!reportsEnabled ? (
          <div className="error-banner">The `reports` table is not available in this database yet, so report creation is temporarily disabled.</div>
        ) : null}
        <WorkflowReportModalGrid
          templates={Object.entries(workflowTemplates).map(([key, template]) => ({
            key,
            ...template
          }))}
          workflowStats={metrics.workflowStats as Record<string, number>}
          reportsEnabled={reportsEnabled}
        />
      </SectionCard>

      <div className="content-grid two-col">
        <SectionCard title="Generated Reports" description="Current reporting records from Supabase Postgres.">
          <DataTable headers={["Title", "Department", "Status", "Due Date", "Actions"]}>
            {reports.map((report: any) => (
              <tr key={report.id}>
                <td>{String(report.title)}</td>
                <td>{String(report.department)}</td>
                <td><StatusBadge value={String(report.status)} /></td>
                <td>{String(report.due_date ?? "-")}</td>
                <td><form action={deleteReportAction}><input type="hidden" name="id" value={String(report.id)} /><ActionIconButton kind="delete" label="Delete report" type="submit" /></form></td>
              </tr>
            ))}
          </DataTable>
        </SectionCard>

        <div className="panel-stack">
          <SectionCard title="Create Report" description="Create a manual report queue entry.">
            <form className="form-grid" action={createReportAction}>
              <label>Title<input name="title" required disabled={!reportsEnabled} /></label>
              <label>Department<input name="department" required disabled={!reportsEnabled} /></label>
              <label>Status<select name="status" defaultValue="Pending" disabled={!reportsEnabled}><option>Pending</option><option>In Review</option><option>Completed</option></select></label>
              <label>Due Date<input name="due_date" type="date" disabled={!reportsEnabled} /></label>
              <div className="span-2"><button className="primary" type="submit" disabled={!reportsEnabled}>Create Report</button></div>
            </form>
          </SectionCard>
          <SectionCard title="Update Report" description="Edit an existing report queue entry.">
            <form className="form-grid" action={updateReportAction}>
              <label className="span-2">Report<select name="id" required defaultValue="" disabled={!reportsEnabled}><option value="" disabled>Select report</option>{reports.map((report: any) => <option key={report.id} value={String(report.id)}>{String(report.title)}</option>)}</select></label>
              <label>Title<input name="title" required disabled={!reportsEnabled} /></label>
              <label>Department<input name="department" required disabled={!reportsEnabled} /></label>
              <label>Status<select name="status" defaultValue="Pending" disabled={!reportsEnabled}><option>Pending</option><option>In Review</option><option>Completed</option></select></label>
              <label>Due Date<input name="due_date" type="date" disabled={!reportsEnabled} /></label>
              <div className="span-2"><button className="primary" type="submit" disabled={!reportsEnabled}>Update Report</button></div>
            </form>
          </SectionCard>
        </div>
      </div>
    </AppShell>
  );
}
