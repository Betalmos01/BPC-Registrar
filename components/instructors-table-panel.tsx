"use client";

import { useState } from "react";
import { ActionIconButton } from "@/components/action-icon-button";
import { DataTable } from "@/components/data-table";
import { createInstructorAction, deleteInstructorAction, updateInstructorAction } from "@/lib/actions";

type InstructorRecord = {
  id: number;
  employee_no: string;
  first_name: string;
  last_name: string;
  department: string | null;
};

export function InstructorsTablePanel({
  instructors
}: {
  instructors: InstructorRecord[];
}) {
  const [activeModal, setActiveModal] = useState<"add" | "view" | "edit" | null>(null);
  const [selectedInstructor, setSelectedInstructor] = useState<InstructorRecord | null>(null);

  function openModal(mode: "view" | "edit", instructor: InstructorRecord) {
    setSelectedInstructor(instructor);
    setActiveModal(mode);
  }

  return (
    <>
      <div className="actions-row table-top-actions">
        <button className="primary" type="button" onClick={() => setActiveModal("add")}>
          Add Instructor
        </button>
      </div>

      <DataTable headers={["Employee No", "Name", "Department", "Actions"]}>
        {instructors.map((instructor) => (
          <tr key={instructor.id}>
            <td>{String(instructor.employee_no)}</td>
            <td>{`${String(instructor.first_name)} ${String(instructor.last_name)}`}</td>
            <td>{String(instructor.department ?? "-")}</td>
            <td>
              <div className="actions-row action-icon-row">
                <ActionIconButton
                  kind="view"
                  label="View instructor"
                  type="button"
                  onClick={() => openModal("view", instructor)}
                />
                <ActionIconButton
                  kind="edit"
                  label="Edit instructor"
                  type="button"
                  onClick={() => openModal("edit", instructor)}
                />
                <form action={deleteInstructorAction}>
                  <input type="hidden" name="id" value={String(instructor.id)} />
                  <ActionIconButton kind="delete" label="Delete instructor" type="submit" />
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
                <div className="eyebrow">Faculty Directory</div>
                <h3>Add Instructor</h3>
                <p>Create a new faculty record from the table workflow.</p>
              </div>
              <button className="secondary compact-button" type="button" onClick={() => setActiveModal(null)}>Close</button>
            </div>
            <form className="form-grid top-gap" action={createInstructorAction}>
              <label>Employee No<input name="employee_no" required /></label>
              <label>First Name<input name="first_name" required /></label>
              <label>Last Name<input name="last_name" required /></label>
              <label className="span-2">Department<input name="department" /></label>
              <div className="span-2 modal-actions">
                <button className="secondary inline-button" type="button" onClick={() => setActiveModal(null)}>Cancel</button>
                <button className="primary inline-button" type="submit">Save Instructor</button>
              </div>
            </form>
          </div>
        </div>
      ) : null}

      {activeModal === "view" && selectedInstructor ? (
        <div className="modal-backdrop" role="presentation" onClick={() => setActiveModal(null)}>
          <div className="modal-card" role="dialog" aria-modal="true" onClick={(event) => event.stopPropagation()}>
            <div className="modal-head">
              <div>
                <div className="eyebrow">Faculty Directory</div>
                <h3>Instructor Details</h3>
                <p>Review the current faculty profile before making changes.</p>
              </div>
              <button className="secondary compact-button" type="button" onClick={() => setActiveModal(null)}>Close</button>
            </div>
            <div className="info-pairs top-gap">
              <p><strong>Employee No:</strong> {selectedInstructor.employee_no}</p>
              <p><strong>Name:</strong> {selectedInstructor.first_name} {selectedInstructor.last_name}</p>
              <p><strong>Department:</strong> {selectedInstructor.department ?? "-"}</p>
            </div>
            <div className="modal-actions top-gap">
              <button className="secondary inline-button" type="button" onClick={() => setActiveModal(null)}>Close</button>
              <button
                className="primary inline-button"
                type="button"
                onClick={() => setActiveModal("edit")}
              >
                Edit Instructor
              </button>
            </div>
          </div>
        </div>
      ) : null}

      {activeModal === "edit" && selectedInstructor ? (
        <div className="modal-backdrop" role="presentation" onClick={() => setActiveModal(null)}>
          <div className="modal-card" role="dialog" aria-modal="true" onClick={(event) => event.stopPropagation()}>
            <div className="modal-head">
              <div>
                <div className="eyebrow">Faculty Directory</div>
                <h3>Edit Instructor</h3>
                <p>Update the selected instructor directly from the directory table.</p>
              </div>
              <button className="secondary compact-button" type="button" onClick={() => setActiveModal(null)}>Close</button>
            </div>
            <form className="form-grid top-gap" action={updateInstructorAction}>
              <input type="hidden" name="id" value={String(selectedInstructor.id)} />
              <label>Employee No<input name="employee_no" required defaultValue={selectedInstructor.employee_no} /></label>
              <label>First Name<input name="first_name" required defaultValue={selectedInstructor.first_name} /></label>
              <label>Last Name<input name="last_name" required defaultValue={selectedInstructor.last_name} /></label>
              <label className="span-2">Department<input name="department" defaultValue={selectedInstructor.department ?? ""} /></label>
              <div className="span-2 modal-actions">
                <button className="secondary inline-button" type="button" onClick={() => setActiveModal(null)}>Cancel</button>
                <button className="primary inline-button" type="submit">Update Instructor</button>
              </div>
            </form>
          </div>
        </div>
      ) : null}
    </>
  );
}
