import { format } from "node:util";
import { requireRole } from "@/lib/auth";
import { getExportRows } from "@/lib/data";

const workflowTemplates: Record<string, { summary: string }> = {
  "access-setup": {
    summary: "Review user provisioning, role assignments, and account readiness across the registrar office."
  },
  "student-intake": {
    summary: "Summarize newly encoded student records, intake readiness, and pending validation items."
  },
  "class-planning": {
    summary: "Track prepared subjects, room schedules, and section readiness before enrollment starts."
  },
  "enrollment-validation": {
    summary: "Measure confirmed registrations, pending validations, and load assignment progress."
  },
  "grade-posting": {
    summary: "Review subject grade completion, posting progress, and release readiness by semester."
  },
  "completion-services": {
    summary: "Monitor transcript, certification, and other registrar release services for completed records."
  },
  "compliance-reports": {
    summary: "Roll up audit, workflow, and institutional reporting checkpoints into one operational view."
  }
};

function escapeHtml(value: unknown) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/\"/g, "&quot;");
}

function getRowValues(row: Record<string, unknown>) {
  return Object.values(row).map((value) => String(value ?? ""));
}

export async function GET(request: Request) {
  await requireRole("Administrator");

  const { searchParams } = new URL(request.url);
  const formatValue = String(searchParams.get("format") ?? "excel").toLowerCase();
  const workflowKey = String(searchParams.get("workflow_key") ?? "").trim();
  const report = await getExportRows(workflowKey);
  const summary = workflowTemplates[workflowKey]?.summary ?? "Generated registrar report queue export.";
  const generatedAt = new Intl.DateTimeFormat("en-PH", {
    dateStyle: "long",
    timeStyle: "short"
  }).format(new Date());

  if (formatValue === "excel") {
    const tableRows = report.rows.length
      ? report.rows
          .map((row: Record<string, unknown>) => `<tr>${getRowValues(row).map((value) => `<td>${escapeHtml(value)}</td>`).join("")}</tr>`)
          .join("")
      : `<tr><td colspan="${report.columns.length}">No data available.</td></tr>`;

    const content = `
      <table border="1">
        <tr><th colspan="${report.columns.length}">${escapeHtml(report.title)}</th></tr>
        <tr><td colspan="${report.columns.length}">${escapeHtml(summary)}</td></tr>
        <tr>${report.columns.map((column) => `<th>${escapeHtml(column)}</th>`).join("")}</tr>
        ${tableRows}
      </table>
    `;

    return new Response(content, {
      headers: {
        "Content-Type": "application/vnd.ms-excel; charset=utf-8",
        "Content-Disposition": `attachment; filename="${report.title.toLowerCase().replace(/[^a-z0-9]+/g, "-")}-${Date.now()}.xls"`
      }
    });
  }

  const tableRows = report.rows.length
    ? report.rows
        .map((row: Record<string, unknown>) => `<tr>${getRowValues(row).map((value) => `<td>${escapeHtml(value)}</td>`).join("")}</tr>`)
        .join("")
    : `<tr><td colspan="${report.columns.length}">No data available.</td></tr>`;

  const html = `
    <!doctype html>
    <html lang="en">
      <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>${escapeHtml(report.title)} PDF Export</title>
        <style>
          body { font-family: Arial, sans-serif; margin: 32px; color: #1b2230; }
          h1 { margin: 0 0 8px; font-size: 24px; }
          p { margin: 0 0 18px; color: #5f6f8d; line-height: 1.5; }
          table { width: 100%; border-collapse: collapse; font-size: 13px; }
          th, td { border: 1px solid #d7deec; padding: 10px 12px; text-align: left; vertical-align: top; }
          th { background: #eff4fd; text-transform: uppercase; font-size: 12px; letter-spacing: 0.04em; }
          .print-note { margin-top: 16px; font-size: 12px; color: #6c7a92; }
          @media print { .print-note { display: none; } body { margin: 14px; } }
        </style>
      </head>
      <body>
        <h1>${escapeHtml(report.title)}</h1>
        <p>${escapeHtml(summary)}</p>
        <p>Generated on ${escapeHtml(generatedAt)}</p>
        <table>
          <thead>
            <tr>${report.columns.map((column) => `<th>${escapeHtml(column)}</th>`).join("")}</tr>
          </thead>
          <tbody>
            ${tableRows}
          </tbody>
        </table>
        <div class="print-note">This view opens ready for browser Print. Choose "Save as PDF" in the print dialog to download as PDF.</div>
        <script>window.print();</script>
      </body>
    </html>
  `;

  return new Response(html, {
    headers: {
      "Content-Type": "text/html; charset=utf-8"
    }
  });
}
