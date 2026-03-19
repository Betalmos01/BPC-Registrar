import { AppShell } from "@/components/app-shell";
import { InstructorsTablePanel } from "@/components/instructors-table-panel";
import { SectionCard } from "@/components/section-card";
import { requireRole } from "@/lib/auth";
import { listInstructors } from "@/lib/data";

export default async function InstructorsPage() {
  const user = await requireRole("Registrar Staff");
  const instructors = await listInstructors();

  return (
    <AppShell user={user} title="Faculty / Instructor Management" description="Instructor records and maintenance now live fully in TypeScript.">
      <div className="content-grid">
        <SectionCard title="Instructor Directory" description="Live instructor rows from Supabase Postgres.">
          <InstructorsTablePanel
            instructors={instructors as Array<{ id: number; employee_no: string; first_name: string; last_name: string; department: string | null }>}
          />
        </SectionCard>
      </div>
    </AppShell>
  );
}
