import Link from "next/link";
import { AppShell } from "@/components/app-shell";
import { DataTable } from "@/components/data-table";
import { IntegrationSendPanel } from "@/components/integration-send-panel";
import { SectionCard } from "@/components/section-card";
import { StatsGrid } from "@/components/stats-grid";
import { StatusBadge } from "@/components/status-badge";
import { requireRole } from "@/lib/auth";
import {
  getRegistrarDepartmentFlowSummary,
  listRegistrarDepartmentFlowEvents,
  listRegistrarIncomingDepartments,
  listRegistrarOutgoingDepartments
} from "@/lib/department-integration";
import { listStudents } from "@/lib/data";

function formatDateTime(value: string | null) {
  if (!value) return "-";

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return date.toLocaleString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit"
  });
}

export default async function IntegrationsPage() {
  const user = await requireRole("Administrator");
  const [summary, outgoing, incoming, events, students] = await Promise.all([
    getRegistrarDepartmentFlowSummary(),
    listRegistrarOutgoingDepartments(),
    listRegistrarIncomingDepartments(),
    listRegistrarDepartmentFlowEvents(30),
    listStudents()
  ]);

  return (
    <AppShell
      user={user}
      title="Office Integrations"
      description="Manage registrar-to-department connections through the shared Supabase department flow registry."
    >
      <SectionCard
        title="HR staffing"
        description="Submit hiring requests to HR for Registrar roles and track request status."
      >
        <Link href="/admin/integrations/hr-staff-request" className="primary inline-button" style={{ textDecoration: "none" }}>
          Request Staff from HR
        </Link>
      </SectionCard>

      <StatsGrid
        stats={[
          {
            label: "Outgoing Departments",
            value: summary.outgoing_departments,
            note: `${summary.outgoing_routes} active registrar route(s).`
          },
          {
            label: "Incoming Departments",
            value: summary.incoming_departments,
            note: `${summary.incoming_routes} route(s) currently targeting registrar.`
          },
          {
            label: "Queued Events",
            value: summary.queued_events,
            note: "Queued, pending, or in-progress registrar department flows."
          },
          {
            label: "Completed Events",
            value: summary.completed_events,
            note: `${summary.failed_events} failed or blocked event(s) on record.`
          }
        ]}
      />

      {!summary.enabled ? (
        <div className="error-banner">
          The shared department integration tables are not available in this database yet, so the registrar cannot queue connected department flows.
        </div>
      ) : null}

      <div className="content-grid two-col">
        <SectionCard title="Outgoing Connections" description="Connected departments that Registrar can actively dispatch to.">
          {outgoing.length ? (
            <div className="status-stack">
              {outgoing.map((entry) => (
                <div key={entry.department_key} className="status-row">
                  <div>
                    <strong>{entry.department_name}</strong>
                    <div className="status-meta">{entry.purpose}</div>
                    <div className="status-meta">Primary route: {entry.routes[0]?.flow_name ?? "No active route"}</div>
                    <div className="status-meta">
                      Endpoint: <code>{entry.dispatch_endpoint}</code>
                    </div>
                  </div>
                  <StatusBadge value={entry.latest_status ?? "Ready"} />
                </div>
              ))}
            </div>
          ) : (
            <div className="status-meta">No outbound registrar routes are active yet.</div>
          )}
        </SectionCard>

        <SectionCard title="Incoming Connections" description="Departments that currently route shared flow events into Registrar.">
          {incoming.length ? (
            <div className="status-stack">
              {incoming.map((entry) => (
                <div key={entry.department_key} className="status-row">
                  <div>
                    <strong>{entry.department_name}</strong>
                    <div className="status-meta">{entry.purpose}</div>
                    <div className="status-meta">Routes into Registrar: {entry.route_count}</div>
                    <div className="status-meta">Latest event: {entry.latest_event_code ?? "None yet"}</div>
                  </div>
                  <StatusBadge value={entry.latest_status ?? "Waiting"} />
                </div>
              ))}
            </div>
          ) : (
            <div className="status-meta">No inbound department-flow routes are currently configured for Registrar.</div>
          )}
        </SectionCard>
      </div>

      <SectionCard title="Send Registrar Flows" description="Preview registrar payloads and queue them to the selected connected department.">
        <IntegrationSendPanel
          students={students as Array<{ id: number; student_no: string; first_name: string; last_name: string }>}
          outgoing={outgoing}
        />
      </SectionCard>

      <SectionCard title="Recent Department Flow Events" description="Latest registrar flow events recorded in the shared integration event ledger.">
        {events.length ? (
          <DataTable headers={["Flow", "Source", "Target", "Status", "Correlation", "Created"]}>
            {events.map((event) => (
              <tr key={event.id}>
                <td>{event.flow_name}</td>
                <td>{event.source_department_name}</td>
                <td>{event.target_department_name}</td>
                <td><StatusBadge value={event.status} /></td>
                <td>{event.correlation_id}</td>
                <td>{formatDateTime(event.created_at)}</td>
              </tr>
            ))}
          </DataTable>
        ) : (
          <div className="status-meta">No registrar department flow events have been recorded yet.</div>
        )}
      </SectionCard>
    </AppShell>
  );
}
