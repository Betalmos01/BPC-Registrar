import Link from "next/link";
import { AppShell } from "@/components/app-shell";
import { DataTable } from "@/components/data-table";
import { SectionCard } from "@/components/section-card";
import { requireRole } from "@/lib/auth";
import { listClasses } from "@/lib/data";

export default async function ClassListsPage() {
  const user = await requireRole("Registrar Staff");
  const classes = await listClasses();

  return (
    <AppShell user={user} title="Class Lists" description="Detailed roster tracking translated from the PHP class list module.">
      <SectionCard title="Published Class Lists" description="Open any class to review its current roster, schedule block, and recorded grades.">
        <DataTable headers={["Code", "Title", "Course", "Schedule", "Roster"]}>
          {classes.map((item: any) => (
            <tr key={`${item.id}-${String(item.day)}`}>
              <td>{String(item.class_code)}</td>
              <td>
                <div>{String(item.title)}</div>
                <Link href={`/staff/class-lists/${String(item.id)}`} className="inline-link">
                  View roster
                </Link>
              </td>
              <td>{String(item.course)}</td>
              <td>{`${String(item.day ?? "-")} ${String(item.time ?? "")} ${String(item.room ?? "")}`}</td>
              <td>
                <Link href={`/staff/class-lists/${String(item.id)}`} className="secondary inline-button">
                  Open Class List
                </Link>
              </td>
            </tr>
          ))}
        </DataTable>
      </SectionCard>
    </AppShell>
  );
}
