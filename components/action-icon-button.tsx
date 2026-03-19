"use client";

import { ButtonHTMLAttributes } from "react";

type ActionKind = "view" | "edit" | "delete";

function ActionIcon({ kind }: { kind: ActionKind }) {
  if (kind === "view") {
    return (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" />
        <circle cx="12" cy="12" r="3" />
      </svg>
    );
  }

  if (kind === "edit") {
    return (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <path d="M12 20h9" />
        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z" />
      </svg>
    );
  }

  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M3 6h18" />
      <path d="M8 6V4h8v2" />
      <path d="M19 6l-1 14H6L5 6" />
      <path d="M10 11v6" />
      <path d="M14 11v6" />
    </svg>
  );
}

export function ActionIconButton({
  kind,
  label,
  className = "",
  ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & {
  kind: ActionKind;
  label: string;
}) {
  const tone = kind === "delete" ? "danger" : kind === "view" ? "neutral" : "primary";

  return (
    <button
      {...props}
      className={`action-icon-button action-icon-${tone} ${className}`.trim()}
      title={label}
      aria-label={label}
    >
      <ActionIcon kind={kind} />
      <span className="sr-only">{label}</span>
    </button>
  );
}
