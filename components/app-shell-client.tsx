"use client";

import Image from "next/image";
import Link from "next/link";
import { ReactNode, useState } from "react";
import logo from "@/lib/Logo/logo.png";
import { ShellTopbar } from "@/components/shell-topbar";
import type { SessionUser } from "@/lib/session";
import type { NavItem } from "@/lib/navigation";

type NavSection = { section: string; items: NavItem[] };

function NavIcon({ icon }: { icon: string }) {
  const common = {
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: "currentColor",
    strokeWidth: 1.8,
    strokeLinecap: "round" as const,
    strokeLinejoin: "round" as const,
    "aria-hidden": true
  };

  switch (icon) {
    case "dashboard":
      return <svg {...common}><rect x="3" y="3" width="7" height="7" rx="1.5" /><rect x="14" y="3" width="7" height="4" rx="1.5" /><rect x="14" y="10" width="7" height="11" rx="1.5" /><rect x="3" y="13" width="7" height="8" rx="1.5" /></svg>;
    case "users":
    case "directory":
      return <svg {...common}><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" /><circle cx="9.5" cy="7" r="3.5" /><path d="M20 8v6" /><path d="M17 11h6" /></svg>;
    case "students":
      return <svg {...common}><path d="M12 4 3 8l9 4 9-4-9-4Z" /><path d="M7 10.5V15c0 1.7 2.2 3 5 3s5-1.3 5-3v-4.5" /></svg>;
    case "faculty":
      return <svg {...common}><path d="M4 19.5V8.5L12 4l8 4.5v11" /><path d="M9 22v-5h6v5" /><path d="M8 11h.01" /><path d="M16 11h.01" /></svg>;
    case "classes":
      return <svg {...common}><rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 10h18" /><path d="M8 3v4" /><path d="M16 3v4" /></svg>;
    case "list":
      return <svg {...common}><path d="M9 6h11" /><path d="M9 12h11" /><path d="M9 18h11" /><path d="M4 6h.01" /><path d="M4 12h.01" /><path d="M4 18h.01" /></svg>;
    case "calendar":
      return <svg {...common}><rect x="3" y="5" width="18" height="16" rx="2" /><path d="M16 3v4" /><path d="M8 3v4" /><path d="M3 11h18" /></svg>;
    case "enrollment":
      return <svg {...common}><path d="M4 19h16" /><path d="M6 16V8l6-4 6 4v8" /><path d="M9 12h6" /></svg>;
    case "grades":
    case "academic":
      return <svg {...common}><path d="M7 4h10l3 3v13H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" /><path d="M14 4v4h4" /><path d="M9 13h6" /><path d="M9 17h4" /></svg>;
    case "documents":
    case "reports":
      return <svg {...common}><path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" /><path d="M14 3v5h5" /></svg>;
    case "logs":
      return <svg {...common}><path d="M8 6h13" /><path d="M8 12h13" /><path d="M8 18h13" /><path d="M3 6h.01" /><path d="M3 12h.01" /><path d="M3 18h.01" /></svg>;
    case "profile":
      return <svg {...common}><circle cx="12" cy="8" r="4" /><path d="M5 20a7 7 0 0 1 14 0" /></svg>;
    case "settings":
      return <svg {...common}><circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21a2 2 0 1 1-4 0v-.09a1.7 1.7 0 0 0-.4-1.1 1.7 1.7 0 0 0-1-.6 1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H2.9a2 2 0 1 1 0-4H3a1.7 1.7 0 0 0 1.1-.4 1.7 1.7 0 0 0 .6-1 1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V2.9a2 2 0 1 1 4 0V3a1.7 1.7 0 0 0 .4 1.1 1.7 1.7 0 0 0 1 .6 1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.27.3.6.5 1 .6h.1a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.1.4 1.7 1.7 0 0 0-.6 1Z" /></svg>;
    default:
      return <svg {...common}><circle cx="12" cy="12" r="8" /></svg>;
  }
}

export function AppShellClient({
  user,
  title,
  description,
  children,
  nav,
  notifications,
  unreadCount
}: {
  user: SessionUser;
  title: string;
  description: string;
  children: ReactNode;
  nav: NavSection[];
  notifications: Array<{ id: number | string; title: string; message: string; status: string; created_at: string }>;
  unreadCount: number;
}) {
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);

  return (
    <div className={`app-shell ${sidebarCollapsed ? "sidebar-collapsed" : ""}`}>
      <aside className="sidebar">
        <div className="sidebar-inner">
          <div className="brand">
            <Image className="brand-logo" src={logo} alt="Bestlink College of the Philippines Registrar logo" priority />
            <div className="brand-copy">
              <div className="brand-title">Bestlink College</div>
              <div className="brand-sub">Registrar System</div>
            </div>
          </div>

          <div className="sidebar-nav">
            {nav.map((section) => (
              <div key={section.section} className="nav-section">
                <div className="nav-heading">{section.section}</div>
                <div className="nav-list">
                  {section.items.map((item) => (
                    <Link key={item.href} href={item.href} className="nav-link" title={item.label}>
                      <span className="nav-icon"><NavIcon icon={item.icon} /></span>
                      <span className="nav-link-label">{item.label}</span>
                    </Link>
                  ))}
                </div>
              </div>
            ))}
          </div>

          <footer className="sidebar-footer">
            <div className="sidebar-footer-title">Bestlink College of the Philippines</div>
            <div className="sidebar-footer-copy">Registrar portal migrated to the Next.js workspace.</div>
          </footer>
        </div>
      </aside>

      <main className="main">
        <ShellTopbar
          user={user}
          title={title}
          description={description}
          nav={nav}
          notifications={notifications}
          unreadCount={unreadCount}
          onToggleSidebar={() => setSidebarCollapsed((value) => !value)}
        />
        {children}
      </main>
    </div>
  );
}
