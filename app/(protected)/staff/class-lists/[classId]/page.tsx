import Link from "next/link";
import { notFound } from "next/navigation";
import { AppShell } from "@/components/app-shell";
import { DataTable } from "@/components/data-table";
import { SectionCard } from "@/components/section-card";
import { StatusBadge } from "@/components/status-badge";
import { requireRole } from "@/lib/auth";
import { getClassRoster } from "@/lib/data";

export default async function ClassRosterPage({ params }: { params: Promise<{ classId: string }> }) {
  const user = await requireRole("Registrar Staff");
  const { classId } = await params;
  const rosterView = await getClassRoster(Number(classId));

  if (!rosterView.classInfo) {
    notFound();
  }

  return (
    <AppShell
      user={user}
      title={`Class List: ${String(rosterView.classInfo.class_code)}`}
      description="Roster detail translated from the PHP class list view into a TypeScript page."
    >
      <div className="content-grid two-col">
        <SectionCard title="Class Summary" description="Schedule and offering details for the selected subject block.">
          <div className="panel-stack info-pairs">
            <div>
              <div className="eyebrow">Subject</div>
              <p>{String(rosterView.classInfo.title)}</p>
            </div>
            <div>
              <div className="eyebrow">Course</div>
              <p>{String(rosterView.classInfo.course ?? "-")}</p>
            </div>
            <div>
              <div className="eyebrow">Units</div>
              <p>{String(rosterView.classInfo.units ?? "-")}</p>
            </div>
            <div>
              <div className="eyebrow">Schedule</div>
              <p>{`${String(rosterView.classInfo.day ?? "-")} ${String(rosterView.classInfo.time ?? "")} ${String(rosterView.classInfo.room ?? "")}`}</p>
            </div>
          </div>
        </SectionCard>

        <SectionCard title="Roster Metrics" description="Quick roster counts carried into the translated workspace.">
          <div className="metric-grid">
            <div className="stat-card compact">
              <div className="stat-label">Enrolled</div>
              <div className="stat-value">{rosterView.roster.length}</div>
              <div className="stat-note">Students currently attached to this class.</div>
            </div>
            <div className="stat-card compact">
              <div className="stat-label">With Grades</div>
              <div className="stat-value">{rosterView.roster.filter((row: any) => row.grade).length}</div>
              <div className="stat-note">Roster entries that already have a recorded grade.</div>
            </div>
          </div>
        </SectionCard>
      </div>

      <SectionCard title="Official Class Roster" description="Student roster with enrollment status and any grade already posted for this class.">
        <DataTable headers={["Student No", "Student Name", "Program", "Year", "Enrollment", "Grade", "Action"]}>
          {rosterView.roster.map((row: any) => (
            <tr key={row.enrollment_id}>
              <td>{String(row.student_no)}</td>
              <td>{`${String(row.last_name)}, ${String(row.first_name)}`}</td>
              <td>{String(row.program ?? "-")}</td>
              <td>{String(row.year_level ?? "-")}</td>
              <td><StatusBadge value={String(row.enrollment_status ?? "Active")} /></td>
              <td>{String(row.grade ?? "Pending")}</td>
              <td>
                <Link href="/staff/grades" className="secondary inline-button">
                  {row.grade ? "Review Grade" : "Record Grade"}
                </Link>
              </td>
            </tr>
          ))}
        </DataTable>
      </SectionCard>
    </AppShell>
  );
}
