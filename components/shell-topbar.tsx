"use client";

import Link from "next/link";
import { useMemo, useState } from "react";
import { logoutAction, markAllNotificationsReadAction, markNotificationReadAction } from "@/lib/actions";
import type { SessionUser } from "@/lib/session";

type NavSection = {
  section: string;
  items: Array<{ label: string; href: string; icon: string }>;
};

type NotificationItem = {
  id: number | string;
  title: string;
  message: string;
  status: string;
  created_at: string;
};

function SearchIcon() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path
        d="M10.5 4a6.5 6.5 0 1 0 4.03 11.6l4.43 4.43 1.41-1.41-4.43-4.43A6.5 6.5 0 0 0 10.5 4Zm0 2a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9Z"
        fill="currentColor"
      />
    </svg>
  );
}

function BellIcon() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path
        d="M12 3a5 5 0 0 0-5 5v2.2c0 .79-.19 1.57-.57 2.27L5 15v1h14v-1l-1.43-2.53A4.57 4.57 0 0 1 17 10.2V8a5 5 0 0 0-5-5Zm0 19a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Z"
        fill="currentColor"
      />
    </svg>
  );
}

function ChevronIcon() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="m7 10 5 5 5-5" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

function HamburgerIcon() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path d="M4 7h16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
      <path d="M4 12h16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
      <path d="M4 17h16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
    </svg>
  );
}

function getInitials(user: SessionUser) {
  const source = `${user.first_name} ${user.last_name}`.trim() || user.username;
  return source
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? "")
    .join("");
}

export function ShellTopbar({
  user,
  title,
  description,
  nav,
  notifications,
  unreadCount,
  onToggleSidebar
}: {
  user: SessionUser;
  title: string;
  description: string;
  nav: NavSection[];
  notifications: NotificationItem[];
  unreadCount: number;
  onToggleSidebar?: () => void;
}) {
  const [query, setQuery] = useState("");
  const [notificationsOpen, setNotificationsOpen] = useState(false);
  const [profileOpen, setProfileOpen] = useState(false);

  const searchResults = useMemo(() => {
    const value = query.trim().toLowerCase();
    if (!value) return [];

    return nav
      .flatMap((section) =>
        section.items.map((item) => ({
          ...item,
          section: section.section
        }))
      )
      .filter((item) => `${item.label} ${item.section}`.toLowerCase().includes(value))
      .slice(0, 8);
  }, [nav, query]);

  const displayName = user.display_name || `${user.first_name} ${user.last_name}`.trim() || user.username;

  return (
    <header className="topbar shell-topbar">
      <div className="topbar-copy shell-topbar-copy">
        {onToggleSidebar ? (
          <button className="header-icon-button sidebar-hamburger" type="button" onClick={onToggleSidebar} aria-label="Toggle sidebar">
            <HamburgerIcon />
          </button>
        ) : null}
        <div>
        <div className="eyebrow">{user.role}</div>
        <h1>{title}</h1>
        <p>{description}</p>
        </div>
      </div>

      <div className="header-tools">
        <div className="header-search">
          <span className="header-search-icon">
            <SearchIcon />
          </span>
          <input
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            placeholder="Search dashboard, users, reports, classes..."
            aria-label="Realtime page search"
          />
          {query.trim() ? (
            <div className="header-dropdown search-dropdown">
              {searchResults.length > 0 ? (
                searchResults.map((result) => (
                  <Link key={result.href} href={result.href} className="search-result" onClick={() => setQuery("")}>
                    <span className="search-result-label">{result.label}</span>
                    <span className="search-result-meta">{result.section}</span>
                  </Link>
                ))
              ) : (
                <div className="dropdown-empty">No matching pages found.</div>
              )}
            </div>
          ) : null}
        </div>

        <div className="header-action-group">
          <div className="header-popover">
            <button
              className="header-icon-button"
              type="button"
              onClick={() => {
                setNotificationsOpen((value) => !value);
                setProfileOpen(false);
              }}
              aria-label="Toggle notifications"
              aria-expanded={notificationsOpen}
            >
              <BellIcon />
              {unreadCount > 0 ? <span className="header-badge">{unreadCount > 9 ? "9+" : unreadCount}</span> : null}
            </button>
            {notificationsOpen ? (
              <div className="header-dropdown notifications-dropdown">
                <div className="dropdown-head">
                  <div>
                    <div className="eyebrow">Notifications</div>
                    <p>{unreadCount > 0 ? `${unreadCount} unread updates` : "All caught up"}</p>
                  </div>
                  <form action={markAllNotificationsReadAction}>
                    <button className="secondary compact-button" type="submit" disabled={unreadCount === 0}>
                      Mark All Read
                    </button>
                  </form>
                </div>
                <div className="dropdown-scroll">
                  {notifications.length > 0 ? (
                    notifications.map((note) => (
                      <form
                        key={note.id}
                        action={markNotificationReadAction}
                        className={`notification-item ${String(note.status).toLowerCase() === "unread" ? "unread" : ""}`}
                      >
                        <input type="hidden" name="id" value={String(note.id)} />
                        <div className="notification-title">{String(note.title)}</div>
                        <p>{String(note.message)}</p>
                        <div className="notification-meta">{String(note.created_at)}</div>
                        {String(note.status).toLowerCase() === "unread" ? (
                          <button className="secondary inline-button compact-button" type="submit">
                            Mark Read
                          </button>
                        ) : null}
                      </form>
                    ))
                  ) : (
                    <div className="dropdown-empty">No notifications available.</div>
                  )}
                </div>
              </div>
            ) : null}
          </div>

          <div className="header-popover">
            <button
              className="profile-trigger"
              type="button"
              onClick={() => {
                setProfileOpen((value) => !value);
                setNotificationsOpen(false);
              }}
              aria-label="Open profile menu"
              aria-expanded={profileOpen}
            >
              <span className="profile-avatar">{getInitials(user)}</span>
              <span className="profile-summary">
                <span className="profile-name">{displayName}</span>
                <span className="profile-role">{user.role}</span>
              </span>
              <span className="profile-chevron">
                <ChevronIcon />
              </span>
            </button>
            {profileOpen ? (
              <div className="header-dropdown profile-dropdown">
                <div className="profile-card">
                  <span className="profile-avatar large">{getInitials(user)}</span>
                  <div>
                    <div className="profile-name">{displayName}</div>
                    <div className="profile-role">{user.username}</div>
                  </div>
                </div>
                <div className="profile-menu">
                  <Link href="/profile" className="profile-link" onClick={() => setProfileOpen(false)}>
                    Open Profile
                  </Link>
                  <form action={logoutAction}>
                    <button className="profile-link danger-link" type="submit">
                      Logout
                    </button>
                  </form>
                </div>
              </div>
            ) : null}
          </div>
        </div>
      </div>
    </header>
  );
}
