import Link from "next/link";
import { AppShell } from "@/components/app-shell";
import { DataTable } from "@/components/data-table";
import { SectionCard } from "@/components/section-card";
import { requireRole } from "@/lib/auth";
import { getClassFilters, listSchedules } from "@/lib/data";

export default async function SchedulesPage({ searchParams }: { searchParams: Promise<{ q?: string; day?: string; room?: string }> }) {
  const user = await requireRole("Registrar Staff");
  const params = await searchParams;
  const [schedules, filters] = await Promise.all([
    listSchedules(params.q ?? "", params.day ?? "", params.room ?? ""),
    getClassFilters()
  ]);

  return (
    <AppShell user={user} title="Class Schedules" description="The old PHP schedules review screen is now available in the TypeScript app.">
      <div className="content-grid two-col">
        <SectionCard title="Schedule List" description="Review schedules for students and instructors.">
          <form className="form-grid compact-form" method="get">
            <label>Search<input name="q" defaultValue={params.q ?? ""} placeholder="Class code, title, room" /></label>
            <label>Day<select name="day" defaultValue={params.day ?? ""}><option value="">All Days</option>{filters.days.map((day) => <option key={day}>{day}</option>)}</select></label>
            <label>Room<select name="room" defaultValue={params.room ?? ""}><option value="">All Rooms</option>{filters.rooms.map((room) => <option key={room}>{room}</option>)}</select></label>
            <div className="actions-row align-end"><button className="secondary" type="submit">Apply Filters</button><Link href="/staff/classes" className="secondary inline-button">Edit Schedules</Link></div>
          </form>
          <DataTable headers={["Class Code", "Title", "Course", "Day", "Time", "Room"]}>
            {schedules.map((schedule: any) => (
              <tr key={schedule.id}>
                <td>{String(schedule.class_code)}</td>
                <td>{String(schedule.title)}</td>
                <td>{String(schedule.course ?? "-")}</td>
                <td>{String(schedule.day ?? "-")}</td>
                <td>{String(schedule.time ?? "-")}</td>
                <td>{String(schedule.room ?? "-")}</td>
              </tr>
            ))}
          </DataTable>
        </SectionCard>
        <SectionCard title="Schedule Summary" description="Quick operational metrics for active schedules.">
          <div className="panel-stack info-pairs">
            <div><div className="eyebrow">Schedules</div><p>{schedules.length}</p></div>
            <div><div className="eyebrow">Days Used</div><p>{filters.days.length}</p></div>
            <div><div className="eyebrow">Rooms Used</div><p>{filters.rooms.length}</p></div>
          </div>
        </SectionCard>
      </div>
    </AppShell>
  );
}
