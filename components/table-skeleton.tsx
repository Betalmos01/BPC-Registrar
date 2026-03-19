export function TableSkeleton({
  columns = 5,
  rows = 6,
  title = "Loading records"
}: {
  columns?: number;
  rows?: number;
  title?: string;
}) {
  return (
    <div className="section-card">
      <div className="section-head">
        <div>
          <h2>{title}</h2>
          <p>Fetching the latest registrar data.</p>
        </div>
      </div>

      <div className="table-shell skeleton-shell" aria-hidden="true">
        <table className="data-table">
          <thead>
            <tr>
              {Array.from({ length: columns }).map((_, index) => (
                <th key={`header-${index}`}>
                  <span className="skeleton skeleton-text short" />
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {Array.from({ length: rows }).map((_, rowIndex) => (
              <tr key={`row-${rowIndex}`}>
                {Array.from({ length: columns }).map((_, columnIndex) => (
                  <td key={`cell-${rowIndex}-${columnIndex}`}>
                    <span className="skeleton skeleton-text" />
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
