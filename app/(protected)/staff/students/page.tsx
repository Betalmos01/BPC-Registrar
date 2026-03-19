import { AppShell } from "@/components/app-shell";
import { IntegrationSendPanel } from "@/components/integration-send-panel";
import { SectionCard } from "@/components/section-card";
import { StudentsTablePanel } from "@/components/students-table-panel";
import { requireRole } from "@/lib/auth";
import { buildIntegrationManifest } from "@/lib/integration-catalog";
import { getStudentFilters, listStudents } from "@/lib/data";

export default async function StudentsPage({
  searchParams
}: {
  searchParams: Promise<{ q?: string; program?: string; year?: string }>;
}) {
  const user = await requireRole("Registrar Staff");
  const params = await searchParams;
  const [students, filters] = await Promise.all([
    listStudents(params.q ?? "", params.program ?? "", params.year ?? ""),
    getStudentFilters()
  ]);
  const manifest = buildIntegrationManifest("/api/integrations");
  const studentOutgoing = manifest.outgoing.filter((entry) =>
    ["student-personal-info", "student-list"].includes(entry.key)
  );

  return (
    <AppShell user={user} title="Student Management" description="Student intake and maintenance fully handled in the translated TypeScript app.">
      <div className="stats-grid">
        <div className="stat-card"><div className="stat-label">Intake Queue</div><div className="stat-value">{students.length}</div><div className="stat-note">Current student records in view.</div></div>
        <div className="stat-card"><div className="stat-label">Enrollment Ready</div><div className="stat-value">{filters.activeStudents}</div><div className="stat-note">Students marked active.</div></div>
        <div className="stat-card"><div className="stat-label">Needs Review</div><div className="stat-value">{filters.onHoldStudents}</div><div className="stat-note">Records on hold for validation.</div></div>
        <div className="stat-card"><div className="stat-label">Programs</div><div className="stat-value">{filters.programs.length}</div><div className="stat-note">Program filters available.</div></div>
      </div>

      <div className="content-grid">
        <SectionCard title="Student Master List" description="This list remains the intake endpoint for the rest of the registrar workflow.">
          <StudentsTablePanel
            students={students as Array<{ id: number; student_no: string; first_name: string; last_name: string; program: string | null; year_level: string | null; status: string }>}
            filters={filters}
            params={params}
          />
        </SectionCard>

        <SectionCard title="Student Data Integrations" description="Student profile feeds are handled directly from the student records page.">
          <IntegrationSendPanel
            students={students as Array<{ id: number; student_no: string; first_name: string; last_name: string }>}
            outgoing={studentOutgoing}
          />
        </SectionCard>
      </div>
    </AppShell>
  );
}
