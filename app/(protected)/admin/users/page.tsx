import { ActionIconButton } from "@/components/action-icon-button";
import { AppShell } from "@/components/app-shell";
import { DataTable } from "@/components/data-table";
import { SectionCard } from "@/components/section-card";
import { StatusBadge } from "@/components/status-badge";
import { requireRole } from "@/lib/auth";
import { createUserAction, deleteUserAction, resetUserPasswordAction, toggleUserAction, updateUserAction } from "@/lib/actions";
import { listRoles, listUsers } from "@/lib/data";

export default async function UsersPage() {
  const user = await requireRole("Administrator");
  const [users, roles] = await Promise.all([listUsers(), listRoles()]);

  return (
    <AppShell user={user} title="User Management" description="Administrative access control is now fully manageable inside the TypeScript app.">
      <div className="content-grid two-col">
        <SectionCard title="System Users" description="Users and roles from Supabase Postgres." >
          <div className="table-toolbar">
            <div className="field">
              <span className="field-label">Role View</span>
              <div className="segmented">
                <span className="segment active">All Accounts</span>
                <span className="segment">Admins</span>
                <span className="segment">Staff</span>
              </div>
            </div>
          </div>
          <DataTable headers={["Username", "Name", "Role", "Active", "Actions"]}>
            {users.map((item: any) => (
              <tr key={item.id}>
                <td>{String(item.username)}</td>
                <td>{`${String(item.first_name)} ${String(item.last_name)}`}</td>
                <td>{String(item.role)}</td>
                <td><StatusBadge value={item.is_active ? "Active" : "Disabled"} /></td>
                <td>
                  <div className="actions-row action-icon-row">
                    {Number(item.id) !== Number(user.id) ? (
                      <>
                        <form action={toggleUserAction}><input type="hidden" name="id" value={String(item.id)} /><input type="hidden" name="is_active" value={item.is_active ? "0" : "1"} /><ActionIconButton kind="edit" label={item.is_active ? "Disable user" : "Enable user"} type="submit" /></form>
                        <form action={deleteUserAction}><input type="hidden" name="id" value={String(item.id)} /><ActionIconButton kind="delete" label="Delete user" type="submit" /></form>
                      </>
                    ) : <span className="badge">You</span>}
                  </div>
                </td>
              </tr>
            ))}
          </DataTable>
        </SectionCard>
        <div className="panel-stack">
          <SectionCard title="Add User" description="Create a new system account.">
            <form className="form-grid" action={createUserAction}>
              <div className="form-cluster span-2">
                <div className="cluster-title">Account Identity</div>
              <label>First Name<input name="first_name" required /></label>
              <label>Last Name<input name="last_name" required /></label>
              <label>Email / Username<input name="username" required /></label>
              <label>Role<select name="role_id" required defaultValue=""><option value="" disabled>Select role</option>{roles.map((role: any) => <option key={role.id} value={String(role.id)}>{String(role.name)}</option>)}</select></label>
              <label className="span-2">Password<input name="password" type="password" required /></label>
              </div>
              <div className="span-2"><button className="primary" type="submit">Create User</button></div>
            </form>
          </SectionCard>
          <SectionCard title="Update / Reset User" description="Edit role assignments and reset passwords without PHP dialogs.">
            <form className="form-grid" action={updateUserAction}>
              <div className="form-cluster span-2">
                <div className="cluster-title">Edit User Account</div>
              <label className="span-2">User<select name="id" required defaultValue=""><option value="" disabled>Select user</option>{users.map((item: any) => <option key={item.id} value={String(item.id)}>{String(item.username)} - {String(item.role)}</option>)}</select></label>
              <label>First Name<input name="first_name" required /></label>
              <label>Last Name<input name="last_name" required /></label>
              <label>Email / Username<input name="username" required /></label>
              <label>Role<select name="role_id" required defaultValue=""><option value="" disabled>Select role</option>{roles.map((role: any) => <option key={role.id} value={String(role.id)}>{String(role.name)}</option>)}</select></label>
              </div>
              <div className="span-2"><button className="primary" type="submit">Update User</button></div>
            </form>
            <form className="form-grid top-gap" action={resetUserPasswordAction}>
              <label>User<select name="id" required defaultValue=""><option value="" disabled>Select user</option>{users.filter((item: any) => Number(item.id) !== Number(user.id)).map((item: any) => <option key={item.id} value={String(item.id)}>{String(item.username)}</option>)}</select></label>
              <label>New Password<input name="password" type="password" required /></label>
              <div className="actions-row align-end"><button className="secondary" type="submit">Reset Password</button></div>
            </form>
          </SectionCard>
        </div>
      </div>
    </AppShell>
  );
}
