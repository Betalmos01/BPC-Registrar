import { TableSkeleton } from "@/components/table-skeleton";

export default function ProtectedLoading() {
  return (
    <div className="app-shell app-shell-loading" aria-busy="true" aria-live="polite">
      <aside className="sidebar sidebar-loading">
        <div className="brand-block">
          <span className="skeleton skeleton-avatar" />
          <div className="brand-copy">
            <span className="skeleton skeleton-text medium" />
            <span className="skeleton skeleton-text short" />
          </div>
        </div>

        <div className="sidebar-loading-nav">
          {Array.from({ length: 10 }).map((_, index) => (
            <span key={index} className="skeleton skeleton-pill" />
          ))}
        </div>
      </aside>

      <main className="main-content">
        <div className="page-header loading-header">
          <div className="page-copy">
            <span className="skeleton skeleton-text short" />
            <span className="skeleton skeleton-text medium" />
            <span className="skeleton skeleton-text long" />
          </div>
          <div className="header-actions">
            <span className="skeleton skeleton-input" />
            <span className="skeleton skeleton-avatar small" />
            <span className="skeleton skeleton-avatar small" />
          </div>
        </div>

        <div className="stats-grid">
          {Array.from({ length: 4 }).map((_, index) => (
            <div key={index} className="stat-card">
              <span className="skeleton skeleton-text short" />
              <span className="skeleton skeleton-text medium" />
              <span className="skeleton skeleton-text long" />
            </div>
          ))}
        </div>

        <div className="content-grid">
          <TableSkeleton title="Loading primary table" columns={6} rows={6} />
          <TableSkeleton title="Loading secondary table" columns={4} rows={5} />
        </div>
      </main>
    </div>
  );
}
