"use client";

import { useMemo, useState } from "react";
import { DataTable } from "@/components/data-table";

type StudentOption = {
  id: number | string;
  student_no: string;
  first_name: string;
  last_name: string;
};

type OutgoingEntry = {
  key: string;
  label: string;
  office: string;
  description: string;
  consumers: string[];
  path: string;
};

type PreviewState = {
  endpoint: string;
  columns: string[];
  rows: Array<Record<string, string>>;
  raw: string;
};

function titleCase(value: string) {
  return value
    .replace(/_/g, " ")
    .replace(/\b\w/g, (match) => match.toUpperCase());
}

function stringifyCell(value: unknown) {
  if (value === null || value === undefined || value === "") {
    return "-";
  }

  if (typeof value === "object") {
    return JSON.stringify(value);
  }

  return String(value);
}

function buildPreview(payload: any, endpoint: string): PreviewState {
  const data = payload?.data ?? {};
  const candidates = Object.entries(data).find(([, value]) => Array.isArray(value));

  if (candidates && Array.isArray(candidates[1])) {
    const rows = candidates[1] as Array<Record<string, unknown>>;
    const sample = rows[0] ?? {};
    const columns = Object.keys(sample).slice(0, 6);

    return {
      endpoint,
      columns: columns.length ? columns : ["result"],
      rows: rows.map((row) =>
        (columns.length ? columns : ["result"]).reduce<Record<string, string>>((accumulator, column) => {
          accumulator[column] = columns.length ? stringifyCell(row[column]) : stringifyCell(row);
          return accumulator;
        }, {})
      ),
      raw: JSON.stringify({ endpoint, ...payload }, null, 2)
    };
  }

  if (data.student && typeof data.student === "object") {
    const student = data.student as Record<string, unknown>;
    const columns = ["student_no", "first_name", "last_name", "program", "year_level", "status"];

    return {
      endpoint,
      columns,
      rows: [
        columns.reduce<Record<string, string>>((accumulator, column) => {
          accumulator[column] = stringifyCell(student[column]);
          return accumulator;
        }, {})
      ],
      raw: JSON.stringify({ endpoint, ...payload }, null, 2)
    };
  }

  const rows = Object.entries(data).map(([key, value]) => ({
    metric: titleCase(key),
    value: stringifyCell(value)
  }));

  return {
    endpoint,
    columns: ["metric", "value"],
    rows,
    raw: JSON.stringify({ endpoint, ...payload }, null, 2)
  };
}

export function IntegrationSendPanel({
  students,
  outgoing
}: {
  students: StudentOption[];
  outgoing: OutgoingEntry[];
}) {
  const [selectedStudentNo, setSelectedStudentNo] = useState(students[0]?.student_no ?? "");
  const [busyKey, setBusyKey] = useState("");
  const [responseText, setResponseText] = useState("Choose an outgoing feed and click Send to open a confirmation preview.");
  const [activeEntry, setActiveEntry] = useState<OutgoingEntry | null>(null);
  const [previewState, setPreviewState] = useState<PreviewState | null>(null);
  const [filterText, setFilterText] = useState("");

  const studentRequiredKeys = useMemo(
    () => new Set(["enrollment-data", "student-personal-info", "student-academic-records"]),
    []
  );

  const studentListPreview = useMemo<PreviewState>(
    () => ({
      endpoint: "/api/integrations?resource=student-list",
      columns: ["student_no", "first_name", "last_name"],
      rows: students.map((student) => ({
        student_no: student.student_no,
        first_name: student.first_name,
        last_name: student.last_name
      })),
      raw: JSON.stringify(
        {
          endpoint: "/api/integrations?resource=student-list",
          ok: true,
          data: { students }
        },
        null,
        2
      )
    }),
    [students]
  );

  async function fetchPayload(entry: OutgoingEntry) {
    const requiresStudent = studentRequiredKeys.has(entry.key);
    const url = new URL(entry.path, window.location.origin);

    if (requiresStudent && selectedStudentNo) {
      url.searchParams.set("student_no", selectedStudentNo);
    }

    const response = await fetch(url.toString(), {
      credentials: "same-origin"
    });
    const payload = await response.json();
    return buildPreview(payload, url.toString());
  }

  async function openSendModal(entry: OutgoingEntry) {
    setActiveEntry(entry);
    setFilterText("");
    if (entry.key === "student-list") {
      setPreviewState(studentListPreview);
      return;
    }

    setBusyKey(entry.key);

    try {
      const preview = await fetchPayload(entry);
      setPreviewState(preview);
    } catch (error) {
      setPreviewState({
        endpoint: entry.path,
        columns: ["error"],
        rows: [{ error: error instanceof Error ? error.message : "Request failed." }],
        raw: JSON.stringify(
          {
            ok: false,
            error: error instanceof Error ? error.message : "Request failed."
          },
          null,
          2
        )
      });
    } finally {
      setBusyKey("");
    }
  }

  async function confirmSend() {
    if (!activeEntry) return;

    setBusyKey(activeEntry.key);
    try {
      const response = await fetch("/api/integrations", {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          action: "deliver",
          resource: activeEntry.key,
          student_no: selectedStudentNo
        })
      });
      const payload = await response.json();
      const deliveryPreview = buildPreview(payload, `/api/integrations [deliver:${activeEntry.key}]`);
      setPreviewState(deliveryPreview);
      setResponseText(JSON.stringify(payload, null, 2));
      setActiveEntry(null);
    } catch (error) {
      const raw = JSON.stringify(
        {
          ok: false,
          error: error instanceof Error ? error.message : "Request failed."
        },
        null,
        2
      );
      setResponseText(raw);
      setPreviewState({
        endpoint: activeEntry.path,
        columns: ["error"],
        rows: [{ error: error instanceof Error ? error.message : "Request failed." }],
        raw
      });
    } finally {
      setBusyKey("");
    }
  }

  const filteredRows = useMemo(() => {
    if (!previewState) return [];

    const needle = filterText.trim().toLowerCase();
    if (!needle) return previewState.rows;

    return previewState.rows.filter((row) =>
      Object.values(row).some((value) => value.toLowerCase().includes(needle))
    );
  }, [filterText, previewState]);

  return (
    <>
      <div className="content-grid two-col">
        <div className="panel-stack">
          <div className="form-cluster">
            <div className="cluster-title">Outgoing Feed Controls</div>
            <label>
              Student
              <select value={selectedStudentNo} onChange={(event) => setSelectedStudentNo(event.target.value)}>
                {students.map((student) => (
                  <option key={student.id} value={student.student_no}>
                    {student.student_no} - {student.last_name}, {student.first_name}
                  </option>
                ))}
              </select>
            </label>
            <div className="field-hint">
              Student selection is used for enrollment data, personal information, and academic records.
            </div>
          </div>

          <div className="integration-grid">
            {outgoing.map((entry) => (
              <article key={entry.key} className="integration-card">
                <div className="eyebrow">{entry.office}</div>
                <h3>{entry.label}</h3>
                <p className="stat-note">{entry.description}</p>
                <div className="status-meta">Consumers: {entry.consumers.join(", ")}</div>
                <div className="status-meta">Endpoint: <code>{entry.path}</code></div>
                <div className="top-gap">
                  <button className="primary" type="button" onClick={() => openSendModal(entry)} disabled={busyKey !== ""}>
                    {busyKey === entry.key ? "Loading..." : `Send ${entry.label}`}
                  </button>
                </div>
              </article>
            ))}
          </div>
        </div>

        <div className="section-card soft-panel">
          <div className="section-head">
            <div>
              <h2>Last Sent Payload</h2>
              <p>The most recent confirmed outgoing response appears here.</p>
            </div>
          </div>
          <pre className="integration-preview">{responseText}</pre>
        </div>
      </div>

      {activeEntry ? (
        <div className="modal-backdrop" role="presentation" onClick={() => busyKey === "" && setActiveEntry(null)}>
          <div className="modal-card modal-card-wide" role="dialog" aria-modal="true" onClick={(event) => event.stopPropagation()}>
            <div className="modal-head">
              <div>
                <div className="eyebrow">{activeEntry.office}</div>
                <h3>Confirm {activeEntry.label}</h3>
                <p>Review the outgoing data below before sending it to the connected system.</p>
              </div>
              <button className="secondary compact-button" type="button" onClick={() => setActiveEntry(null)} disabled={busyKey !== ""}>
                Close
              </button>
            </div>

            {previewState ? (
              <>
                <div className="table-toolbar top-gap">
                  <label className="field">
                    <span className="field-label">Preview Filter</span>
                    <div className="filter-input-shell">
                      <span className="filter-icon" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="none">
                          <path d="M3 5h14l-5.4 6.3v3.9l-3.2 1.8v-5.7L3 5Z" stroke="currentColor" strokeWidth="1.5" strokeLinejoin="round" />
                        </svg>
                      </span>
                      <input
                        value={filterText}
                        onChange={(event) => setFilterText(event.target.value)}
                        placeholder="Filter preview rows"
                      />
                    </div>
                  </label>
                  <div className="field">
                    <span className="field-label">Endpoint</span>
                    <div className="status-meta"><code>{previewState.endpoint}</code></div>
                  </div>
                </div>

                <DataTable headers={previewState.columns.map((column) => titleCase(column))}>
                  {filteredRows.map((row, index) => (
                    <tr key={`${index}-${Object.values(row).join("-")}`}>
                      {previewState.columns.map((column) => (
                        <td key={column}>{row[column] ?? "-"}</td>
                      ))}
                    </tr>
                  ))}
                </DataTable>
              </>
            ) : (
              <div className="integration-preview top-gap">Loading preview table...</div>
            )}

            <div className="modal-actions top-gap">
              <button className="secondary inline-button" type="button" onClick={() => setActiveEntry(null)} disabled={busyKey !== ""}>
                Cancel
              </button>
              <button className="primary inline-button" type="button" onClick={confirmSend} disabled={busyKey !== ""}>
                {busyKey === activeEntry.key ? "Sending..." : `Confirm Send`}
              </button>
            </div>
          </div>
        </div>
      ) : null}
    </>
  );
}
