"use client";

import Link from "next/link";
import { useMemo, useState } from "react";
import { createWorkflowReportAction } from "@/lib/actions";

type WorkflowTemplate = {
  key: string;
  label: string;
  title: string;
  department: string;
  summary: string;
};

type WorkflowReportModalGridProps = {
  templates: WorkflowTemplate[];
  workflowStats: Record<string, number>;
  reportsEnabled: boolean;
};

const reportStatuses = ["Pending", "In Review", "Completed"] as const;

function getTodayDate() {
  return new Date().toISOString().slice(0, 10);
}

export function WorkflowReportModalGrid({
  templates,
  workflowStats,
  reportsEnabled
}: WorkflowReportModalGridProps) {
  const [activeKey, setActiveKey] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);

  const activeTemplate = useMemo(
    () => templates.find((template) => template.key === activeKey) ?? null,
    [activeKey, templates]
  );

  return (
    <>
      {successMessage ? (
        <div className="success-banner" role="status">
          <div>
            <strong>Report generated.</strong> {successMessage}
          </div>
          <button
            className="secondary compact-button"
            type="button"
            onClick={() => setSuccessMessage("")}
          >
            Dismiss
          </button>
        </div>
      ) : null}

      <div className="report-grid">
        {templates.map((template) => (
          <article key={template.key} className="report-card">
            <div className="eyebrow">{template.label}</div>
            <h3>{template.title}</h3>
            <p>{template.summary}</p>
            <div className="report-meta">
              {template.department} - {workflowStats[template.key] ?? 0} records
            </div>
            <div className="actions-row">
              <Link
                href={`/admin/reports/export?format=pdf&workflow_key=${template.key}`}
                className="secondary inline-button"
                target="_blank"
              >
                PDF
              </Link>
              <Link
                href={`/admin/reports/export?format=excel&workflow_key=${template.key}`}
                className="secondary inline-button"
              >
                Excel
              </Link>
              <button
                className="primary inline-button"
                type="button"
                disabled={!reportsEnabled}
                onClick={() => setActiveKey(template.key)}
              >
                Open Report
              </button>
            </div>
          </article>
        ))}
      </div>

      {activeTemplate ? (
        <div
          className="modal-backdrop"
          role="presentation"
          onClick={() => !isSubmitting && setActiveKey(null)}
        >
          <div
            className="modal-card"
            role="dialog"
            aria-modal="true"
            aria-labelledby="workflow-report-modal-title"
            onClick={(event) => event.stopPropagation()}
          >
            <div className="modal-head">
              <div>
                <div className="eyebrow">{activeTemplate.label}</div>
                <h3 id="workflow-report-modal-title">{activeTemplate.title}</h3>
                <p>{activeTemplate.summary}</p>
              </div>
              <button
                className="secondary compact-button"
                type="button"
                onClick={() => setActiveKey(null)}
                disabled={isSubmitting}
              >
                Close
              </button>
            </div>

            <form
              className="form-grid top-gap"
              action={async (formData) => {
                setIsSubmitting(true);
                setSuccessMessage("");
                try {
                  await createWorkflowReportAction(formData);
                  setSuccessMessage(`${activeTemplate.title} has been added to the report queue.`);
                  setActiveKey(null);
                } finally {
                  setIsSubmitting(false);
                }
              }}
            >
              <input type="hidden" name="workflow_key" value={activeTemplate.key} />
              <label>
                Title
                <input name="title" defaultValue={activeTemplate.title} disabled={!reportsEnabled || isSubmitting} />
              </label>
              <label>
                Department
                <input
                  name="department"
                  defaultValue={activeTemplate.department}
                  disabled={!reportsEnabled || isSubmitting}
                />
              </label>
              <label>
                Status
                <select name="status" defaultValue="Pending" disabled={!reportsEnabled || isSubmitting}>
                  {reportStatuses.map((status) => (
                    <option key={status}>{status}</option>
                  ))}
                </select>
              </label>
              <label>
                Due Date
                <input
                  name="due_date"
                  type="date"
                  defaultValue={getTodayDate()}
                  disabled={!reportsEnabled || isSubmitting}
                />
              </label>
              <div className="span-2 modal-actions">
                <button
                  className="secondary inline-button"
                  type="button"
                  onClick={() => setActiveKey(null)}
                  disabled={isSubmitting}
                >
                  Cancel
                </button>
                <button className="primary inline-button" type="submit" disabled={!reportsEnabled || isSubmitting}>
                  {isSubmitting ? "Generating..." : `Generate ${activeTemplate.label}`}
                </button>
              </div>
            </form>
          </div>
        </div>
      ) : null}
    </>
  );
}
