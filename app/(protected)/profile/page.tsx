import { AppShell } from "@/components/app-shell";
import { SectionCard } from "@/components/section-card";
import { requireUser } from "@/lib/auth";
import { getUserProfile } from "@/lib/data";

export default async function ProfilePage() {
  const user = await requireUser();
  const profile = await getUserProfile(user.id);
  const displayName = String(profile?.display_name || `${profile?.first_name ?? user.first_name} ${profile?.last_name ?? user.last_name}`);

  return (
    <AppShell user={user} title="My Profile" description="Account profile translated from the PHP user profile view into the TypeScript app.">
      <div className="content-grid two-col">
        <SectionCard title="Identity" description="Current signed-in account details from Supabase Postgres.">
          <div className="panel-stack info-pairs">
            <div>
              <div className="eyebrow">Display Name</div>
              <p>{displayName}</p>
            </div>
            <div>
              <div className="eyebrow">Username</div>
              <p>{String(profile?.username ?? user.username)}</p>
            </div>
            <div>
              <div className="eyebrow">Role</div>
              <p>{String(profile?.role ?? user.role)}</p>
            </div>
            <div>
              <div className="eyebrow">Profile Title</div>
              <p>{String(profile?.profile_title ?? user.profile_title ?? user.role)}</p>
            </div>
          </div>
        </SectionCard>

        <SectionCard title="Profile Notes" description="Supplemental account information carried over from the PHP profile card.">
          <div className="panel-stack info-pairs">
            <div>
              <div className="eyebrow">Bio</div>
              <p>{String(profile?.profile_bio ?? user.profile_bio ?? "No profile bio saved yet.")}</p>
            </div>
            <div>
              <div className="eyebrow">Accent</div>
              <div className="accent-preview">
                <span className="accent-swatch" style={{ backgroundColor: String(profile?.profile_accent ?? user.profile_accent ?? "#2F6BD9") }} />
                <p>{String(profile?.profile_accent ?? user.profile_accent ?? "#2F6BD9")}</p>
              </div>
            </div>
          </div>
        </SectionCard>
      </div>
    </AppShell>
  );
}
