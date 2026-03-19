import { AppShell } from "@/components/app-shell";
import { DataTable } from "@/components/data-table";
import { IntegrationSendPanel } from "@/components/integration-send-panel";
import { SectionCard } from "@/components/section-card";
import { StatsGrid } from "@/components/stats-grid";
import { StatusBadge } from "@/components/status-badge";
import { requireRole } from "@/lib/auth";
import { resolveTableName } from "@/lib/db";
import { buildIntegrationManifest } from "@/lib/integration-catalog";
import { getIntegrationSummary, listIntegrationRecords, listStudents } from "@/lib/data";

export default async function IntegrationsPage() {
  const user = await requireRole("Administrator");
  const [summary, records, students, integrationRecordsTable] = await Promise.all([
    getIntegrationSummary(),
    listIntegrationRecords(),
    listStudents(),
    resolveTableName("integration_records")
  ]);
  const integrationsEnabled = Boolean(integrationRecordsTable);
  const manifest = buildIntegrationManifest("/api/integrations");

  return (
    <AppShell
      user={user}
      title="Office Integrations"
      description="The PHP integration console is now translated into a TypeScript admin page with Supabase-backed records."
    >
      <StatsGrid
        stats={[
          { label: "Incoming Offices", value: summary.incomingOffices, note: "Detected offices with recorded transactions." },
          { label: "Outgoing Feeds", value: summary.outgoingFeeds, note: "Prepared external data feeds." },
          { label: "Records Received", value: summary.recordsReceived, note: "Stored integration transactions." },
          { label: "Translation", value: "Live", note: "This module is running in the Next.js app." }
        ]}
      />

      <section className="integration-grid">
        <article className="integration-card">
          <div className="eyebrow">External Validation Panels</div>
          <h3>Student Clearance Status</h3>
          <div className="status-stack">
            <div className="status-row"><div><strong>Payment Status</strong><div className="status-meta">Cashier feed</div></div><StatusBadge value="Paid" /></div>
            <div className="status-row"><div><strong>Medical Clearance</strong><div className="status-meta">Clinic integration</div></div><StatusBadge value="Cleared" /></div>
            <div className="status-row"><div><strong>Discipline Record</strong><div className="status-meta">Prefect office</div></div><StatusBadge value="None" /></div>
            <div className="status-row"><div><strong>Counseling</strong><div className="status-meta">Guidance workflow</div></div><StatusBadge value="Pending" /></div>
          </div>
        </article>
        <article className="integration-card">
          <div className="eyebrow">Connectivity</div>
          <h3>Integration Health</h3>
          <div className="status-stack">
            <div className="status-row"><div><strong>Cashier</strong><div className="status-meta">Last sync: Today</div></div><StatusBadge value="Connected" /></div>
            <div className="status-row"><div><strong>Clinic</strong><div className="status-meta">Last sync: Today</div></div><StatusBadge value="Connected" /></div>
            <div className="status-row"><div><strong>Guidance</strong><div className="status-meta">Last sync: Yesterday</div></div><StatusBadge value="Pending" /></div>
            <div className="status-row"><div><strong>Prefect</strong><div className="status-meta">Last sync: Today</div></div><StatusBadge value="Connected" /></div>
          </div>
        </article>
      </section>

      <div className="content-grid two-col">
        <SectionCard title="Automatic Incoming Sync" description="Incoming records should arrive through connected office endpoints automatically.">
          {!integrationsEnabled ? (
            <div className="error-banner">The `integration_records` table is not available in this database yet, so automatic incoming logging is not active.</div>
          ) : (
            <div className="success-banner">
              <div>
                <strong>Automatic sync is enabled.</strong> Connected systems should post directly to the registrar integration endpoints without manual staff encoding.
              </div>
            </div>
          )}
          <div className="status-stack">
            {manifest.incoming.map((entry) => (
              <div key={entry.key} className="status-row">
                <div>
                  <strong>{entry.label}</strong>
                  <div className="status-meta">{entry.office} {"->"} Registrar</div>
                  <div className="status-meta">Endpoint: <code>{entry.path}</code></div>
                </div>
                <StatusBadge value={integrationsEnabled ? "Connected" : "Pending"} />
              </div>
            ))}
          </div>
        </SectionCard>

        <SectionCard title="Integration Notes" description="The richer outbound preview tooling from PHP can be ported next.">
          <div className="panel-stack">
            <div>
              <div className="eyebrow">Receives</div>
              <p>Payment confirmation, medical clearance, counseling reports, discipline records, and activity participation records.</p>
            </div>
            <div>
              <div className="eyebrow">Sends</div>
              <p>Enrollment data, student personal information, student academic records, student list, and enrollment statistics.</p>
            </div>
          </div>
        </SectionCard>
      </div>

      <SectionCard title="Send Outgoing Data" description="Use the send buttons below to transmit registrar payloads to connected offices.">
        <IntegrationSendPanel
          students={students as Array<{ id: number; student_no: string; first_name: string; last_name: string }>}
          outgoing={manifest.outgoing}
        />
      </SectionCard>

      <SectionCard title="Endpoint Directory" description="Registrar integration endpoints and folder ownership across connected systems.">
        <div className="integration-grid">
          <article className="integration-card">
            <div className="eyebrow">Incoming To Registrar</div>
            <h3>Receiving Endpoints</h3>
            <div className="status-stack">
              {manifest.incoming.map((entry) => (
                <div key={entry.key} className="status-row">
                  <div>
                    <strong>{entry.label}</strong>
                    <div className="status-meta">{entry.office} - <code>{entry.path}</code></div>
                    <div className="status-meta">Folders: {entry.systemFolders.join(", ")}</div>
                  </div>
                  <StatusBadge value={entry.uiMode === "folder" ? "Connected" : "Ready"} />
                </div>
              ))}
            </div>
          </article>

          <article className="integration-card">
            <div className="eyebrow">Outgoing From Registrar</div>
            <h3>Consumer Endpoints</h3>
            <div className="status-stack">
              {manifest.outgoing.map((entry) => (
                <div key={entry.key} className="status-row">
                  <div>
                    <strong>{entry.label}</strong>
                    <div className="status-meta">{entry.office} - <code>{entry.path}</code></div>
                    <div className="status-meta">Consumers: {entry.consumers.join(", ")}</div>
                  </div>
                  <StatusBadge value={entry.uiMode === "api" ? "Active" : "Connected"} />
                </div>
              ))}
            </div>
          </article>
        </div>
      </SectionCard>

      <SectionCard title="Recent Integration Records" description="Latest incoming transactions from connected offices.">
        {!integrationsEnabled ? (
          <div className="error-banner">Recent integration records are unavailable because the `integration_records` table has not been created yet.</div>
        ) : (
          <DataTable headers={["Office", "Record Type", "Student", "Status", "Reference", "Received"]}>
            {records.map((record: any) => (
              <tr key={record.id}>
                <td>{String(record.source_office)}</td>
                <td>{String(record.record_type)}</td>
                <td>{record.student_no ? `${String(record.student_no)} - ${String(record.last_name)}, ${String(record.first_name)}` : "-"}</td>
                <td><StatusBadge value={String(record.external_status)} /></td>
                <td>{String(record.reference_no)}</td>
                <td>{String(record.received_at)}</td>
              </tr>
            ))}
          </DataTable>
        )}
      </SectionCard>
    </AppShell>
  );
}
