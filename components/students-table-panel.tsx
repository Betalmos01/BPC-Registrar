"use client";

import { useState } from "react";
import { ActionIconButton } from "@/components/action-icon-button";
import { createStudentAction, deleteStudentAction, updateStudentAction } from "@/lib/actions";
import { DataTable } from "@/components/data-table";
import { StatusBadge } from "@/components/status-badge";

type StudentRecord = {
  id: number;
  student_no: string;
  first_name: string;
  last_name: string;
  program: string | null;
  year_level: string | null;
  status: string;
};

type StudentFilters = {
  programs: string[];
  years: string[];
};

export function StudentsTablePanel({
  students,
  filters,
  params,
}: {
  students: StudentRecord[];
  filters: StudentFilters;
  params: { q?: string; program?: string; year?: string };
}) {
  const [activeModal, setActiveModal] = useState<"add" | "edit" | "send" | null>(null);
  const [selectedStudent, setSelectedStudent] = useState<StudentRecord | null>(null);
  const [sendPreview, setSendPreview] = useState("Click send to preview the student list payload that will be sent to CRAD.");
  const [sending, setSending] = useState(false);

  async function sendStudentList() {
    setSending(true);
    try {
      const response = await fetch("/api/integrations", {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          action: "deliver",
          resource: "student-list"
        })
      });
      const payload = await response.json();
      setSendPreview(JSON.stringify(payload, null, 2));
    } catch (error) {
      setSendPreview(
        JSON.stringify(
          { ok: false, error: error instanceof Error ? error.message : "Request failed." },
          null,
          2
        )
      );
    } finally {
      setSending(false);
    }
  }

  return (
    <>
      <form className="filter-bar compact-form" method="get">
        <label className="field">
          <span className="field-label">Realtime Search</span>
          <input name="q" defaultValue={params.q ?? ""} placeholder="Search student no or name" />
        </label>
        <label className="field">
          <span className="field-label">Course</span>
          <select name="program" defaultValue={params.program ?? ""}>
            <option value="">All Courses</option>
            {filters.programs.map((program) => (
              <option key={program}>{program}</option>
            ))}
          </select>
        </label>
        <label className="field">
          <span className="field-label">Year Level</span>
          <select name="year" defaultValue={params.year ?? ""}>
            <option value="">All Years</option>
            {filters.years.map((year) => (
              <option key={year}>{year}</option>
            ))}
          </select>
        </label>
        <div className="actions-row align-end">
          <button className="secondary" type="submit">Filter Records</button>
          <button className="secondary" type="button" onClick={() => setActiveModal("send")}>Send Student List to CRAD</button>
          <button className="primary" type="button" onClick={() => setActiveModal("add")}>Add Student</button>
        </div>
      </form>

      <DataTable headers={["Student No", "Name", "Program", "Year", "Status", "Actions"]}>
        {students.map((student) => (
          <tr key={student.id}>
            <td>{String(student.student_no)}</td>
            <td>{`${String(student.first_name)} ${String(student.last_name)}`}</td>
            <td>{String(student.program ?? "-")}</td>
            <td>{String(student.year_level ?? "-")}</td>
            <td><StatusBadge value={String(student.status)} /></td>
            <td>
              <div className="actions-row action-icon-row">
                <ActionIconButton
                  kind="edit"
                  label="Edit student"
                  type="button"
                  onClick={() => {
                    setSelectedStudent(student);
                    setActiveModal("edit");
                  }}
                />
                <form action={deleteStudentAction}>
                  <input type="hidden" name="id" value={String(student.id)} />
                  <ActionIconButton kind="delete" label="Delete student" type="submit" />
                </form>
              </div>
            </td>
          </tr>
        ))}
      </DataTable>

      {activeModal === "add" ? (
        <div className="modal-backdrop" role="presentation" onClick={() => setActiveModal(null)}>
          <div className="modal-card" role="dialog" aria-modal="true" onClick={(event) => event.stopPropagation()}>
            <div className="modal-head">
              <div>
                <div className="eyebrow">Student Records</div>
                <h3>Add Student</h3>
                <p>Create a new student record without leaving the table view.</p>
              </div>
              <button className="secondary compact-button" type="button" onClick={() => setActiveModal(null)}>Close</button>
            </div>
            <form className="form-grid top-gap" action={createStudentAction}>
              <label>Student No<input name="student_no" required /></label>
              <label>First Name<input name="first_name" required /></label>
              <label>Last Name<input name="last_name" required /></label>
              <label>
                Course
                <select name="program" defaultValue="">
                  <option value="">Select course</option>
                  {filters.programs.map((program) => <option key={program}>{program}</option>)}
                </select>
              </label>
              <label>
                Year Level
                <select name="year_level" defaultValue="">
                  <option value="">Select year</option>
                  {filters.years.map((year) => <option key={year}>{year}</option>)}
                </select>
              </label>
              <label>
                Status
                <select name="status" defaultValue="Active">
                  <option>Active</option>
                  <option>Inactive</option>
                  <option>On Hold</option>
                </select>
              </label>
              <div className="span-2 modal-actions">
                <button className="secondary inline-button" type="button" onClick={() => setActiveModal(null)}>Cancel</button>
                <button className="primary inline-button" type="submit">Save Student</button>
              </div>
            </form>
          </div>
        </div>
      ) : null}

      {activeModal === "edit" && selectedStudent ? (
        <div className="modal-backdrop" role="presentation" onClick={() => setActiveModal(null)}>
          <div className="modal-card" role="dialog" aria-modal="true" onClick={(event) => event.stopPropagation()}>
            <div className="modal-head">
              <div>
                <div className="eyebrow">Student Records</div>
                <h3>Edit Student</h3>
                <p>Update the selected student directly from the master list.</p>
              </div>
              <button className="secondary compact-button" type="button" onClick={() => setActiveModal(null)}>Close</button>
            </div>
            <form className="form-grid top-gap" action={updateStudentAction}>
              <input type="hidden" name="id" value={String(selectedStudent.id)} />
              <label>Student No<input name="student_no" required defaultValue={selectedStudent.student_no} /></label>
              <label>First Name<input name="first_name" required defaultValue={selectedStudent.first_name} /></label>
              <label>Last Name<input name="last_name" required defaultValue={selectedStudent.last_name} /></label>
              <label>
                Course
                <select name="program" defaultValue={selectedStudent.program ?? ""}>
                  <option value="">Select course</option>
                  {filters.programs.map((program) => <option key={program}>{program}</option>)}
                </select>
              </label>
              <label>
                Year Level
                <select name="year_level" defaultValue={selectedStudent.year_level ?? ""}>
                  <option value="">Select year</option>
                  {filters.years.map((year) => <option key={year}>{year}</option>)}
                </select>
              </label>
              <label>
                Status
                <select name="status" defaultValue={selectedStudent.status}>
                  <option>Active</option>
                  <option>Inactive</option>
                  <option>On Hold</option>
                </select>
              </label>
              <div className="span-2 modal-actions">
                <button className="secondary inline-button" type="button" onClick={() => setActiveModal(null)}>Cancel</button>
                <button className="primary inline-button" type="submit">Update Student</button>
              </div>
            </form>
          </div>
        </div>
      ) : null}

      {activeModal === "send" ? (
        <div className="modal-backdrop" role="presentation" onClick={() => setActiveModal(null)}>
          <div className="modal-card" role="dialog" aria-modal="true" onClick={(event) => event.stopPropagation()}>
            <div className="modal-head">
              <div>
                <div className="eyebrow">CRAD Integration</div>
                <h3>Send Student List to CRAD</h3>
                <p>Send the registrar student list feed and review the payload before CRAD consumes it.</p>
              </div>
              <button className="secondary compact-button" type="button" onClick={() => setActiveModal(null)}>Close</button>
            </div>
            <div className="top-gap">
              <pre className="integration-preview">{sendPreview}</pre>
            </div>
            <div className="modal-actions top-gap">
              <button className="secondary inline-button" type="button" onClick={() => setActiveModal(null)}>Cancel</button>
              <button className="primary inline-button" type="button" onClick={sendStudentList} disabled={sending}>
                {sending ? "Sending..." : "Send Student List"}
              </button>
            </div>
          </div>
        </div>
      ) : null}
    </>
  );
}
