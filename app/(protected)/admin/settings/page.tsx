import { AppShell } from "@/components/app-shell";
import { SectionCard } from "@/components/section-card";
import { requireRole } from "@/lib/auth";
import { getUserProfile } from "@/lib/data";
import { updateProfileAction } from "@/lib/actions";

export default async function SettingsPage() {
  const user = await requireRole("Administrator");
  const profile = await getUserProfile(user.id);

  return (
    <AppShell user={user} title="System Settings" description="Administrative profile settings translated from the PHP module into TypeScript.">
      <div className="content-grid two-col">
        <SectionCard title="Admin Profile" description="Update the same account identity fields that were previously managed in the PHP settings page.">
          <form className="form-grid" action={updateProfileAction}>
            <label>
              First Name
              <input name="first_name" defaultValue={String(profile?.first_name ?? user.first_name)} required />
            </label>
            <label>
              Last Name
              <input name="last_name" defaultValue={String(profile?.last_name ?? user.last_name)} required />
            </label>
            <label>
              Display Name
              <input name="display_name" defaultValue={String(profile?.display_name ?? user.display_name ?? "")} />
            </label>
            <label>
              Profile Title
              <input name="profile_title" defaultValue={String(profile?.profile_title ?? user.profile_title ?? user.role)} />
            </label>
            <label className="span-2">
              Profile Accent
              <input name="profile_accent" type="color" defaultValue={String(profile?.profile_accent ?? user.profile_accent ?? "#2F6BD9")} />
            </label>
            <label className="span-2">
              Profile Bio
              <textarea name="profile_bio" rows={5} defaultValue={String(profile?.profile_bio ?? user.profile_bio ?? "")} />
            </label>
            <div className="span-2 actions-row">
              <button className="primary" type="submit">Save Profile</button>
            </div>
          </form>
        </SectionCard>

        <SectionCard title="Platform Status" description="The translated app now runs the live registrar workspace in Next.js with Supabase Postgres.">
          <div className="panel-stack">
            <div>
              <div className="eyebrow">Frontend</div>
              <p>Next.js App Router with TypeScript and protected server-rendered routes.</p>
            </div>
            <div>
              <div className="eyebrow">Database</div>
              <p>Supabase Postgres through server-side <code>pg</code> queries.</p>
            </div>
            <div>
              <div className="eyebrow">Account Preview</div>
              <p>{String(profile?.display_name || `${profile?.first_name ?? user.first_name} ${profile?.last_name ?? user.last_name}`)}</p>
            </div>
          </div>
        </SectionCard>
      </div>
    </AppShell>
  );
}
