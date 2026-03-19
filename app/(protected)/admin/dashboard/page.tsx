import { AppShell } from "@/components/app-shell";
import { DataTable } from "@/components/data-table";
import { SectionCard } from "@/components/section-card";
import { StatsGrid } from "@/components/stats-grid";
import { StatusBadge } from "@/components/status-badge";
import { requireRole } from "@/lib/auth";
import { getDashboardStats, listReports } from "@/lib/data";

export default async function AdminDashboardPage() {
  const user = await requireRole("Administrator");
  const stats = await getDashboardStats();
  const reports = await listReports();

  return (
    <AppShell
      user={user}
      title="Registrar Administration"
      description="Generate reports, monitor system activity, and enforce access control in the translated TypeScript app."
    >
      <section className="dashboard-hero">
        <article className="hero-panel">
          <div className="eyebrow">Admin Command Center</div>
          <h2>Registrar Management System</h2>
          <p>Monitor reports, user access, workflow health, and registrar operations from one modern administrative workspace.</p>
          <div className="hero-metrics">
            <div className="hero-metric">
              <div className="stat-label">Total Students</div>
              <div className="hero-metric-value">{stats.students}</div>
            </div>
            <div className="hero-metric">
              <div className="stat-label">Enrolled</div>
              <div className="hero-metric-value">{stats.enrollments}</div>
            </div>
            <div className="hero-metric">
              <div className="stat-label">Pending Docs</div>
              <div className="hero-metric-value">{stats.pendingDocuments}</div>
            </div>
          </div>
        </article>
        <aside className="hero-aside">
          <div className="eyebrow">Workflow Snapshot</div>
          <div className="mini-chart">
            <div className="mini-chart-row"><span>Reports</span><div className="mini-chart-bar"><div className="mini-chart-fill" style={{ width: `${Math.min(100, stats.reports * 10)}%` }} /></div><strong>{stats.reports}</strong></div>
            <div className="mini-chart-row"><span>Audit Logs</span><div className="mini-chart-bar"><div className="mini-chart-fill" style={{ width: `${Math.min(100, stats.auditLogs)}%` }} /></div><strong>{stats.auditLogs}</strong></div>
            <div className="mini-chart-row"><span>Grades</span><div className="mini-chart-bar"><div className="mini-chart-fill" style={{ width: `${Math.min(100, stats.grades * 5)}%` }} /></div><strong>{stats.grades}</strong></div>
          </div>
        </aside>
      </section>

      <StatsGrid
        stats={[
          { label: "Generated Reports", value: stats.reports, note: "Academic and system reports." },
          { label: "Audit Logs", value: stats.auditLogs, note: "Recorded system actions." },
          { label: "Pending Documents", value: stats.pendingDocuments, note: "Awaiting registrar action." },
          { label: "Grade Records", value: stats.grades, note: "Filed by instructors." }
        ]}
      />

      <div className="content-grid two-col">
        <SectionCard
          title="Recent Reports"
          description="The translated admin dashboard keeps the same operational queue concept from the PHP version."
        >
          <DataTable headers={["Title", "Department", "Status", "Due Date"]}>
            {reports.slice(0, 8).map((report: any) => (
              <tr key={report.id}>
                <td>{String(report.title)}</td>
                <td>{String(report.department)}</td>
                <td><StatusBadge value={String(report.status)} /></td>
                <td>{String(report.due_date ?? "-")}</td>
              </tr>
            ))}
          </DataTable>
        </SectionCard>

        <SectionCard
          title="Translation Status"
          description="This Next.js version now owns the shared shell, authentication, dashboard metrics, and live database tables."
        >
          <div className="panel-stack">
            <div>
              <div className="eyebrow">Working Today</div>
              <p>Admin dashboards, staff dashboards, students, integrations, and module list pages are live in TypeScript.</p>
            </div>
            <div>
              <div className="eyebrow">Data Source</div>
              <p>All metrics on this screen are read directly from Supabase Postgres through the Next.js server.</p>
            </div>
          </div>
        </SectionCard>
      </div>
    </AppShell>
  );
}
