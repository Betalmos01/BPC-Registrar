import Link from "next/link";
import { ActionIconButton } from "@/components/action-icon-button";
import { AppShell } from "@/components/app-shell";
import { DataTable } from "@/components/data-table";
import { SectionCard } from "@/components/section-card";
import { requireRole } from "@/lib/auth";
import { createClassAction, deleteClassAction, updateClassAction } from "@/lib/actions";
import { getClassFilters, listClasses } from "@/lib/data";

export default async function ClassesPage({ searchParams }: { searchParams: Promise<{ course?: string }> }) {
  const user = await requireRole("Registrar Staff");
  const params = await searchParams;
  const [classes, filters] = await Promise.all([listClasses(params.course ?? ""), getClassFilters()]);

  return (
    <AppShell user={user} title="Manage Classes and Schedules" description="Class scheduling and maintenance now run fully inside the TypeScript app.">
      <div className="content-grid two-col">
        <SectionCard title="Class Offerings" description="Each row mirrors the old class and schedule listing, with delete handled directly here.">
          <form className="filter-bar compact-form" method="get">
            <label className="field">
              <span className="field-label">Course Filter</span>
              <select name="course" defaultValue={params.course ?? ""}>
                <option value="">All Courses</option>
                {filters.courses.map((course) => <option key={course}>{course}</option>)}
              </select>
            </label>
            <div className="actions-row align-end"><button className="secondary" type="submit">Filter</button><Link href="/staff/schedules" className="secondary inline-button">View Schedules</Link></div>
          </form>
          <DataTable headers={["Code", "Title", "Course", "Units", "Day", "Time", "Room", "Actions"]}>
            {classes.map((item: any) => (
              <tr key={`${item.id}-${String(item.day)}`}>
                <td>{String(item.class_code)}</td>
                <td>{String(item.title)}</td>
                <td>{String(item.course ?? "-")}</td>
                <td>{String(item.units ?? "-")}</td>
                <td>{String(item.day ?? "-")}</td>
                <td>{String(item.time ?? "-")}</td>
                <td>{String(item.room ?? "-")}</td>
                <td>
                  <form action={deleteClassAction}>
                    <input type="hidden" name="class_id" value={String(item.id)} />
                    <ActionIconButton kind="delete" label="Delete class" type="submit" />
                  </form>
                </td>
              </tr>
            ))}
          </DataTable>
        </SectionCard>

        <div className="panel-stack">
          <SectionCard title="Add Class" description="Create a class and its schedule in one step.">
            <form className="form-grid" action={createClassAction}>
              <div className="form-cluster span-2">
                <div className="cluster-title">Class & Schedule Setup</div>
              <label>Class Code<input name="class_code" required /></label>
              <label>Class Title<input name="class_title" required /></label>
              <label>Subject / Course<select name="course" defaultValue=""><option value="">Select course</option>{filters.courses.map((course) => <option key={course}>{course}</option>)}</select></label>
              <label>Units<input name="units" type="number" min="1" max="6" defaultValue="3" /></label>
              <label>Day<select name="day" defaultValue=""><option value="">Select day</option>{filters.days.map((day) => <option key={day}>{day}</option>)}</select></label>
              <label>Time<select name="time" defaultValue=""><option value="">Select time</option>{filters.times.map((time) => <option key={time}>{time}</option>)}</select></label>
              <label className="span-2">Room<select name="room" defaultValue=""><option value="">Select room</option>{filters.rooms.map((room) => <option key={room}>{room}</option>)}</select></label>
              </div>
              <div className="span-2"><button className="primary" type="submit">Save Class</button></div>
            </form>
          </SectionCard>

          <SectionCard title="Update Class" description="Edit an existing class schedule.">
            <form className="form-grid" action={updateClassAction}>
              <div className="form-cluster span-2">
                <div className="cluster-title">Edit Schedule</div>
              <label className="span-2">Class<select name="class_id" required defaultValue=""><option value="" disabled>Select class</option>{classes.map((item: any) => <option key={item.id} value={String(item.id)}>{String(item.class_code)} - {String(item.title)}</option>)}</select></label>
              <label>Class Code<input name="class_code" required /></label>
              <label>Class Title<input name="class_title" required /></label>
              <label>Subject / Course<select name="course" defaultValue=""><option value="">Select course</option>{filters.courses.map((course) => <option key={course}>{course}</option>)}</select></label>
              <label>Units<input name="units" type="number" min="1" max="6" defaultValue="3" /></label>
              <label>Day<select name="day" defaultValue=""><option value="">Select day</option>{filters.days.map((day) => <option key={day}>{day}</option>)}</select></label>
              <label>Time<select name="time" defaultValue=""><option value="">Select time</option>{filters.times.map((time) => <option key={time}>{time}</option>)}</select></label>
              <label className="span-2">Room<select name="room" defaultValue=""><option value="">Select room</option>{filters.rooms.map((room) => <option key={room}>{room}</option>)}</select></label>
              </div>
              <div className="span-2"><button className="primary" type="submit">Update Class</button></div>
            </form>
          </SectionCard>
        </div>
      </div>
    </AppShell>
  );
}
