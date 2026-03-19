const toneMap: Record<string, "success" | "warning" | "danger" | "neutral"> = {
  active: "success",
  approved: "success",
  completed: "success",
  released: "success",
  cleared: "success",
  paid: "success",
  connected: "success",
  available: "success",
  ready: "success",
  passed: "success",
  enrolled: "success",
  open: "success",
  none: "success",
  pending: "warning",
  processing: "warning",
  "in review": "warning",
  review: "warning",
  waitlisted: "warning",
  unpaid: "warning",
  draft: "warning",
  queued: "warning",
  partial: "warning",
  scheduled: "warning",
  inactive: "danger",
  disabled: "danger",
  failed: "danger",
  "not cleared": "danger",
  "not connected": "danger",
  "has record": "danger",
  rejected: "danger",
  overdue: "danger",
  blocked: "danger",
  hold: "danger",
  closed: "danger",
  cancelled: "danger",
  denied: "danger"
};

export function getStatusTone(value: string) {
  return toneMap[value.trim().toLowerCase()] ?? "neutral";
}

export function StatusBadge({
  value
}: {
  value: string;
}) {
  return <span className={`badge badge-${getStatusTone(value)}`}>{value}</span>;
}
