import { ReactNode } from "react";
import { getNavigation } from "@/lib/navigation";
import { getUnreadNotificationCount, listNotifications } from "@/lib/data";
import { AppShellClient } from "@/components/app-shell-client";
import { getSuccessFlash, type SessionUser } from "@/lib/session";

export async function AppShell({
  user,
  title,
  description,
  children
}: {
  user: SessionUser;
  title: string;
  description: string;
  children: ReactNode;
}) {
  const nav = getNavigation(user.role);
  const [notifications, unreadCount, successMessage] = await Promise.all([
    listNotifications().catch(() => []),
    getUnreadNotificationCount().catch(() => 0),
    getSuccessFlash().catch(() => "")
  ]);

  return (
    <AppShellClient
      user={user}
      title={title}
      description={description}
      nav={nav}
      notifications={notifications as Array<{ id: number | string; title: string; message: string; status: string; created_at: string }>}
      unreadCount={unreadCount}
      successMessage={successMessage}
    >
      {children}
    </AppShellClient>
  );
}
