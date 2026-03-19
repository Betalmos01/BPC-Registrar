import { StatusBadge } from "@/components/status-badge";

type IncomingEntry = {
  key: string;
  label: string;
  office: string;
  description: string;
  path: string;
};

export function IntegrationReceivePanel({
  title,
  description,
  incoming,
  enabled
}: {
  title: string;
  description: string;
  incoming: IncomingEntry[];
  enabled: boolean;
}) {
  return (
    <div className="section-card soft-panel">
      <div className="section-head">
        <div>
          <h2>{title}</h2>
          <p>{description}</p>
        </div>
      </div>

      {!enabled ? (
        <div className="error-banner">
          Automatic integration logging is not active yet because the `integration_records` table is missing.
        </div>
      ) : (
        <div className="success-banner">
          <div>
            <strong>Automatic sync is active.</strong> These records should be received from connected office endpoints without manual entry.
          </div>
        </div>
      )}

      <div className="status-stack">
        {incoming.map((entry) => (
          <div key={entry.key} className="status-row">
            <div>
              <strong>{entry.label}</strong>
              <div className="status-meta">{entry.office} {"->"} Registrar</div>
              <div className="status-meta">{entry.description}</div>
              <div className="status-meta">Endpoint: <code>{entry.path}</code></div>
            </div>
            <StatusBadge value={enabled ? "Connected" : "Pending"} />
          </div>
        ))}
      </div>
    </div>
  );
}
