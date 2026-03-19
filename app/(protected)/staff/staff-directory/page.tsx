import Link from "next/link";
import { AppShell } from "@/components/app-shell";
import { DataTable } from "@/components/data-table";
import { SectionCard } from "@/components/section-card";
import { StatusBadge } from "@/components/status-badge";
import { requireRole } from "@/lib/auth";
import { getStaffDirectoryMetrics, listStaffDirectory } from "@/lib/data";

export default async function StaffDirectoryPage({ searchParams }: { searchParams: Promise<{ q?: string }> }) {
  const user = await requireRole("Registrar Staff");
  const params = await searchParams;
  const [staff, metrics] = await Promise.all([listStaffDirectory(params.q ?? ""), getStaffDirectoryMetrics()]);

  return (
    <AppShell user={user} title="Staff Directory" description="Registrar account directory now lives inside the TypeScript app.">
      <div className="stats-grid">
        <div className="stat-card"><div className="stat-label">Accounts</div><div className="stat-value">{staff.length}</div><div className="stat-note">System users with registrar access.</div></div>
        <div className="stat-card"><div className="stat-label">Active</div><div className="stat-value">{metrics.activeCount}</div><div className="stat-note">Accounts currently enabled.</div></div>
        <div className="stat-card"><div className="stat-label">Administrators</div><div className="stat-value">{metrics.adminCount}</div><div className="stat-note">Full administrative accounts.</div></div>
        <div className="stat-card"><div className="stat-label">Admin Tools</div><div className="stat-value">{user.role.toLowerCase() === "administrator" ? "Open" : "View"}</div><div className="stat-note">Jump to the user management screen when needed.</div></div>
      </div>
      <SectionCard title="Registrar Staff Directory" description="Active staff and administrative accounts.">
        <form className="form-grid compact-form" method="get">
          <label>Search<input name="q" defaultValue={params.q ?? ""} placeholder="Name, username, or role" /></label>
          <div className="actions-row align-end"><button className="secondary" type="submit">Search</button>{user.role.toLowerCase() === "administrator" ? <Link href="/admin/users" className="secondary inline-button">Manage Users</Link> : null}</div>
        </form>
        <DataTable headers={["Name", "Role", "Username", "Status"]}>
          {staff.map((member: any) => (
            <tr key={member.id}>
              <td>{`${String(member.first_name)} ${String(member.last_name)}`}</td>
              <td>{String(member.role)}</td>
              <td>{String(member.username)}</td>
              <td><StatusBadge value={member.is_active ? "Active" : "Inactive"} /></td>
            </tr>
          ))}
        </DataTable>
      </SectionCard>
    </AppShell>
  );
}
