"use client";

import { Children, isValidElement, ReactNode, useEffect, useMemo, useState } from "react";

function getNodeText(node: ReactNode): string {
  if (typeof node === "string" || typeof node === "number") {
    return String(node);
  }

  if (Array.isArray(node)) {
    return node.map(getNodeText).join(" ");
  }

  if (isValidElement(node)) {
    return getNodeText((node.props as { children?: ReactNode }).children);
  }

  return "";
}

export function DataTable({
  headers,
  children,
  pageSize = 8,
  emptyMessage = "No records available."
}: {
  headers: string[];
  children: ReactNode;
  pageSize?: number;
  emptyMessage?: string;
}) {
  const rows = useMemo(() => Children.toArray(children), [children]);
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const filteredRows = useMemo(() => {
    const query = search.trim().toLowerCase();
    if (!query) {
      return rows;
    }

    return rows.filter((row) => getNodeText(row).toLowerCase().includes(query));
  }, [rows, search]);
  const totalPages = Math.max(1, Math.ceil(filteredRows.length / pageSize));

  useEffect(() => {
    setPage(1);
  }, [filteredRows.length, pageSize, search]);

  const safePage = Math.min(page, totalPages);
  const startIndex = (safePage - 1) * pageSize;
  const visibleRows = filteredRows.slice(startIndex, startIndex + pageSize);

  return (
    <div className="dynamic-table">
      <div className="table-toolbar table-toolbar-inline">
        <label className="field table-search-field">
          <span className="field-label">Realtime Search</span>
          <div className="filter-input-shell">
            <span className="filter-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                <path d="M4 6h16" />
                <path d="M7 12h10" />
                <path d="M10 18h4" />
              </svg>
            </span>
            <input
              type="search"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder="Search current table records"
            />
          </div>
        </label>
      </div>

      <div className="table-shell">
        <table className="data-table">
          <thead>
            <tr>
              {headers.map((header) => (
                <th key={header}>{header}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {visibleRows.length > 0 ? (
              visibleRows
            ) : (
              <tr>
                <td className="table-empty" colSpan={headers.length}>
                  {emptyMessage}
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      <div className="table-meta">
        <div className="table-count">
          {filteredRows.length === 0
            ? "0 records"
            : `Showing ${startIndex + 1}-${Math.min(startIndex + pageSize, filteredRows.length)} of ${filteredRows.length}`}
        </div>

        {filteredRows.length > pageSize ? (
          <div className="table-pagination" aria-label="Table pagination">
            <button
              className="secondary compact-button"
              type="button"
              onClick={() => setPage((current) => Math.max(1, current - 1))}
              disabled={safePage === 1}
            >
              Previous
            </button>
            <span className="table-page-indicator">
              Page {safePage} of {totalPages}
            </span>
            <button
              className="secondary compact-button"
              type="button"
              onClick={() => setPage((current) => Math.min(totalPages, current + 1))}
              disabled={safePage === totalPages}
            >
              Next
            </button>
          </div>
        ) : null}
      </div>
    </div>
  );
}
