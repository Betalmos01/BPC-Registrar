import { AppShell } from "@/components/app-shell";
import { DataTable } from "@/components/data-table";
import { SectionCard } from "@/components/section-card";
import { requireRole } from "@/lib/auth";
import { listAuditLogs } from "@/lib/data";

export default async function ActivityPage() {
  const user = await requireRole("Administrator");
  const logs = await listAuditLogs();

  return (
    <AppShell user={user} title="System Logs" description="Audit trail translated into the TypeScript admin area.">
      <SectionCard title="Audit Logs" description="Recent system actions from the registrar database.">
        <DataTable headers={["When", "User", "Action", "Module", "Details"]}>
          {logs.map((log: any) => (
            <tr key={log.id}>
              <td>{String(log.created_at)}</td>
              <td>{String(log.username ?? "-")}</td>
              <td>{String(log.action)}</td>
              <td>{String(log.module)}</td>
              <td>{String(log.details)}</td>
            </tr>
          ))}
        </DataTable>
      </SectionCard>
    </AppShell>
  );
}
