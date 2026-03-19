export type IntegrationDirection = "incoming" | "outgoing";

export type IntegrationCatalogEntry = {
  key: string;
  label: string;
  direction: IntegrationDirection;
  office: string;
  method: "GET" | "POST";
  description: string;
  query?: string;
  consumers: string[];
  systemFolders: string[];
  uiMode: "api" | "folder" | "hybrid";
};

export const integrationCatalog: IntegrationCatalogEntry[] = [
  {
    key: "payment-confirmations",
    label: "Payment Confirmation",
    direction: "incoming",
    office: "Cashier",
    method: "POST",
    description: "Registrar receives payment confirmations before enrollment validation proceeds.",
    consumers: ["Registrar"],
    systemFolders: ["Registrar"],
    uiMode: "api"
  },
  {
    key: "medical-clearances",
    label: "Medical Clearance",
    direction: "incoming",
    office: "Clinic",
    method: "POST",
    description: "Clinic sends clearance status for enrollment and document release checks.",
    consumers: ["Registrar"],
    systemFolders: ["Registrar"],
    uiMode: "api"
  },
  {
    key: "counseling-reports",
    label: "Counseling Report",
    direction: "incoming",
    office: "Guidance",
    method: "POST",
    description: "Guidance sends counseling completion or hold information into registrar records.",
    consumers: ["Registrar"],
    systemFolders: ["Registrar"],
    uiMode: "api"
  },
  {
    key: "discipline-records",
    label: "Discipline Record",
    direction: "incoming",
    office: "Prefect",
    method: "POST",
    description: "Prefect sends discipline holds and incident-linked clearance status into registrar.",
    consumers: ["Registrar", "PrefectManagementsSystem"],
    systemFolders: ["Registrar", "PrefectManagementsSystem"],
    uiMode: "folder"
  },
  {
    key: "activity-participation-records",
    label: "Activity Participation Record",
    direction: "incoming",
    office: "CRAD",
    method: "POST",
    description: "CRAD sends participation or completion records that registrar can reference.",
    consumers: ["Registrar", "CRADManagement"],
    systemFolders: ["Registrar", "CRADManagement"],
    uiMode: "folder"
  },
  {
    key: "enrollment-data",
    label: "Student Enrollment Data",
    direction: "outgoing",
    office: "Cashier",
    method: "GET",
    query: "student_no=2025-0001",
    description: "Cashier pulls the student's latest enrollment rows and class references.",
    consumers: ["Cashier"],
    systemFolders: ["Registrar"],
    uiMode: "api"
  },
  {
    key: "student-personal-info",
    label: "Student Personal Information",
    direction: "outgoing",
    office: "Clinic / Guidance / Prefect",
    method: "GET",
    query: "student_no=2025-0001",
    description: "Consumer systems pull core student identity data for verification workflows.",
    consumers: ["Clinic", "Guidance", "Prefect", "PrefectManagementsSystem"],
    systemFolders: ["Registrar", "PrefectManagementsSystem"],
    uiMode: "hybrid"
  },
  {
    key: "student-academic-records",
    label: "Student Academic Records",
    direction: "outgoing",
    office: "Guidance",
    method: "GET",
    query: "student_no=2025-0001",
    description: "Guidance can pull grades and subject details for advising or intervention workflows.",
    consumers: ["Guidance"],
    systemFolders: ["Registrar"],
    uiMode: "api"
  },
  {
    key: "student-list",
    label: "Student List",
    direction: "outgoing",
    office: "Computer Laboratory / CRAD",
    method: "GET",
    description: "Shared student list feed for external systems that need registrar roster data.",
    consumers: ["Computer Laboratory", "CRAD", "CRADManagement"],
    systemFolders: ["Registrar", "CRADManagement"],
    uiMode: "hybrid"
  },
  {
    key: "enrollment-statistics",
    label: "Enrollment Statistics",
    direction: "outgoing",
    office: "PMED",
    method: "GET",
    description: "PMED can pull aggregate enrollment, student, and class counts from registrar.",
    consumers: ["PMED"],
    systemFolders: ["Registrar"],
    uiMode: "api"
  }
];

export function buildIntegrationManifest(basePath = "/api/integrations") {
  const entries = integrationCatalog.map((entry) => ({
    ...entry,
    path: entry.query ? `${basePath}?resource=${entry.key}&${entry.query}` : `${basePath}?resource=${entry.key}`
  }));

  return {
    incoming: entries.filter((entry) => entry.direction === "incoming"),
    outgoing: entries.filter((entry) => entry.direction === "outgoing")
  };
}
