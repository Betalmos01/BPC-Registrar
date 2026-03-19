import { AppShell } from "@/components/app-shell";
import { SectionCard } from "@/components/section-card";
import { StatsGrid } from "@/components/stats-grid";
import { requireRole } from "@/lib/auth";
import { getDashboardStats } from "@/lib/data";

export default async function StaffDashboardPage() {
  const user = await requireRole("Registrar Staff");
  const stats = await getDashboardStats();

  return (
    <AppShell
      user={user}
      title="Registrar Operations"
      description="Manage students, enrollments, schedules, and grade records from the translated TypeScript workspace."
    >
      <section className="dashboard-hero">
        <article className="hero-panel">
          <div className="eyebrow">Registrar Staff Workspace</div>
          <h2>Daily Enrollment and Records Flow</h2>
          <p>Work through student records, class schedules, grades, and document requests with structured tools and faster task navigation.</p>
          <div className="hero-metrics">
            <div className="hero-metric">
              <div className="stat-label">Students</div>
              <div className="hero-metric-value">{stats.students}</div>
            </div>
            <div className="hero-metric">
              <div className="stat-label">Classes</div>
              <div className="hero-metric-value">{stats.classes}</div>
            </div>
            <div className="hero-metric">
              <div className="stat-label">Enrollments</div>
              <div className="hero-metric-value">{stats.enrollments}</div>
            </div>
          </div>
        </article>
        <aside className="hero-aside">
          <div className="eyebrow">Workflow Status</div>
          <div className="segmented">
            <span className="segment active">Students</span>
            <span className="segment">Enrollment</span>
            <span className="segment">Documents</span>
          </div>
          <div className="mini-chart">
            <div className="mini-chart-row"><span>Students</span><div className="mini-chart-bar"><div className="mini-chart-fill" style={{ width: `${Math.min(100, stats.students)}%` }} /></div><strong>{stats.students}</strong></div>
            <div className="mini-chart-row"><span>Enrollments</span><div className="mini-chart-bar"><div className="mini-chart-fill" style={{ width: `${Math.min(100, stats.enrollments)}%` }} /></div><strong>{stats.enrollments}</strong></div>
            <div className="mini-chart-row"><span>Pending Docs</span><div className="mini-chart-bar"><div className="mini-chart-fill" style={{ width: `${Math.min(100, stats.pendingDocuments * 10)}%` }} /></div><strong>{stats.pendingDocuments}</strong></div>
          </div>
        </aside>
      </section>

      <StatsGrid
        stats={[
          { label: "Students", value: stats.students, note: "Active student records." },
          { label: "Classes", value: stats.classes, note: "Current schedule offerings." },
          { label: "Enrollments", value: stats.enrollments, note: "Validated enrollments." },
          { label: "Pending Documents", value: stats.pendingDocuments, note: "Awaiting processing." }
        ]}
      />

      <div className="content-grid two-col">
        <SectionCard title="Today’s Priorities" description="Same registrar workflow, rebuilt in TypeScript.">
          <div className="panel-stack">
            <div>
              <div className="eyebrow">Student Records</div>
              <p>Update new admissions and complete missing profile data before downstream processing.</p>
            </div>
            <div>
              <div className="eyebrow">Class Lists</div>
              <p>Verify class rosters before instructor release and grade posting.</p>
            </div>
            <div>
              <div className="eyebrow">Authorization Check</div>
              <p>Keep document, enrollment, and academic transactions aligned with the student record state.</p>
            </div>
          </div>
        </SectionCard>

        <SectionCard title="Service Status" description="Live registrar counts from Supabase Postgres.">
          <div className="panel-stack">
            <div>
              <div className="eyebrow">Document Queue</div>
              <p>{stats.pendingDocuments} requests are currently waiting for registrar action.</p>
            </div>
            <div>
              <div className="eyebrow">Enrollment Volume</div>
              <p>{stats.enrollments} enrollment records are available in the translated system.</p>
            </div>
          </div>
        </SectionCard>
      </div>
    </AppShell>
  );
}
