import { ActionIconButton } from "@/components/action-icon-button";
import { AppShell } from "@/components/app-shell";
import { DataTable } from "@/components/data-table";
import { SectionCard } from "@/components/section-card";
import { StatusBadge } from "@/components/status-badge";
import { requireRole } from "@/lib/auth";
import { createDocumentAction, deleteDocumentAction, updateDocumentAction } from "@/lib/actions";
import { resolveTableName } from "@/lib/db";
import { listDocuments, listStudents } from "@/lib/data";

export default async function DocumentsPage() {
  const user = await requireRole("Registrar Staff");
  const [documents, students, documentsTable] = await Promise.all([listDocuments(), listStudents(), resolveTableName("documents", "registrar_documents")]);
  const documentsEnabled = Boolean(documentsTable);

  return (
    <AppShell user={user} title="Document Requests" description="Registrar document requests now support full TypeScript-side maintenance.">
      <div className="content-grid two-col">
        <SectionCard title="Request Queue" description="Current transcript and document requests.">
          {!documentsEnabled ? (
            <div className="error-banner">The `documents` table is not available in this database yet, so document requests are temporarily disabled.</div>
          ) : null}
          <DataTable headers={["Student", "Document", "Status", "Requested", "Completed", "Actions"]}>
            {documents.map((doc: any) => (
              <tr key={doc.id}>
                <td>{`${String(doc.student_no)} - ${String(doc.last_name)}, ${String(doc.first_name)}`}</td>
                <td>{String(doc.doc_type)}</td>
                <td><StatusBadge value={String(doc.status)} /></td>
                <td>{String(doc.requested_at)}</td>
                <td>{String(doc.completed_at ?? "-")}</td>
                <td><form action={deleteDocumentAction}><input type="hidden" name="doc_id" value={String(doc.id)} /><ActionIconButton kind="delete" label="Delete document request" type="submit" /></form></td>
              </tr>
            ))}
          </DataTable>
        </SectionCard>
        <div className="panel-stack">
          <SectionCard title="Create Request" description="File a new registrar document request.">
            <form className="form-grid" action={createDocumentAction}>
              <div className="form-cluster span-2">
                <div className="cluster-title">Document Request Form</div>
              <label className="span-2">Student<select name="student_id" required defaultValue="" disabled={!documentsEnabled}><option value="" disabled>Select student</option>{students.map((s: any) => <option key={s.id} value={String(s.id)}>{String(s.student_no)} - {String(s.last_name)}, {String(s.first_name)}</option>)}</select></label>
              <label>Document Type<select name="doc_type" defaultValue="Transcript of Records" disabled={!documentsEnabled}><option>Transcript of Records</option><option>Certificate of Enrollment</option><option>Good Moral Certificate</option><option>Certification</option></select></label>
              <label>Purpose<select name="purpose" defaultValue="School Requirement" disabled={!documentsEnabled}><option>School Requirement</option><option>Employment</option><option>Transfer</option><option>Scholarship</option></select></label>
              </div>
              <div className="span-2"><button className="primary" type="submit" disabled={!documentsEnabled}>Save Request</button></div>
            </form>
          </SectionCard>
          <SectionCard title="Update Status" description="Mark a request as pending, processing, or completed.">
            <form className="form-grid" action={updateDocumentAction}>
              <label className="span-2">Request<select name="doc_id" required defaultValue="" disabled={!documentsEnabled}><option value="" disabled>Select request</option>{documents.map((doc: any) => <option key={doc.id} value={String(doc.id)}>{String(doc.student_no)} - {String(doc.doc_type)}</option>)}</select></label>
              <label>Status<select name="status" defaultValue="Pending" disabled={!documentsEnabled}><option>Pending</option><option>Processing</option><option>Completed</option></select></label>
              <div className="actions-row align-end"><button className="primary" type="submit" disabled={!documentsEnabled}>Update Request</button></div>
            </form>
          </SectionCard>
        </div>
      </div>
    </AppShell>
  );
}
