"use client";

import { useState } from "react";

import { loginAction } from "@/lib/actions";

function UserIcon() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path
        d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4.418 0-8 2.239-8 5v1h16v-1c0-2.761-3.582-5-8-5Z"
        fill="currentColor"
      />
    </svg>
  );
}

function LockIcon() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path
        d="M17 9h-1V7a4 4 0 1 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 6.732V17a1 1 0 0 0 2 0v-1.268a2 2 0 1 0-2 0ZM10 9V7a2 2 0 1 1 4 0v2Z"
        fill="currentColor"
      />
    </svg>
  );
}

function EyeIcon({ open }: { open: boolean }) {
  return open ? (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path
        d="M12 5c5.5 0 9.5 4.5 10.7 6-.633.79-2.081 2.388-4.145 3.733l1.432 1.432-1.414 1.414-16-16 1.414-1.414 2.565 2.565A12.078 12.078 0 0 1 12 5Zm6.836 6.029C17.5 9.748 14.977 7 12 7a9.11 9.11 0 0 0-3.951.91l1.69 1.69A4 4 0 0 1 14.4 14.26Zm-2.264 6.22A10.962 10.962 0 0 1 12 19C6.5 19 2.5 14.5 1.3 13c.539-.673 1.649-1.91 3.197-3.075l1.447 1.447A9.254 9.254 0 0 0 3.164 12.97C4.5 14.252 7.023 17 12 17c1.293 0 2.464-.185 3.511-.515ZM12 9a3.98 3.98 0 0 0-.792.08l4.712 4.712A3.998 3.998 0 0 0 12 9Zm-4 4c0-.274.028-.541.08-.8l-1.633-1.633A5.97 5.97 0 0 0 6 13a6 6 0 0 0 8.433 5.48l-1.655-1.655A3.99 3.99 0 0 1 8 13Z"
        fill="currentColor"
      />
    </svg>
  ) : (
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <path
        d="M12 5c5.5 0 9.5 4.5 10.7 6-1.2 1.5-5.2 6-10.7 6S2.5 12.5 1.3 11C2.5 9.5 6.5 5 12 5Zm0 2C8.977 7 6.455 9.748 5.164 11.03 6.5 12.312 9.023 15 12 15s5.5-2.688 6.836-3.97C17.5 9.748 14.977 7 12 7Zm0 2a2 2 0 1 1-2 2 2 2 0 0 1 2-2Zm0 1.5a.5.5 0 1 0 .5.5.5.5 0 0 0-.5-.5Z"
        fill="currentColor"
      />
    </svg>
  );
}

export function LoginForm() {
  const [showPassword, setShowPassword] = useState(false);
  const [errors, setErrors] = useState<{ username?: string; password?: string }>({});

  function validate(values: { username: string; password: string }) {
    const nextErrors: { username?: string; password?: string } = {};

    if (!values.username.trim()) {
      nextErrors.username = "Username is required.";
    } else if (values.username.trim().length < 3) {
      nextErrors.username = "Username must be at least 3 characters.";
    }

    if (!values.password) {
      nextErrors.password = "Password is required.";
    } else if (values.password.length < 6) {
      nextErrors.password = "Password must be at least 6 characters.";
    }

    return nextErrors;
  }

  function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    const form = event.currentTarget;
    const formData = new FormData(form);
    const nextErrors = validate({
      username: String(formData.get("username") ?? ""),
      password: String(formData.get("password") ?? "")
    });

    setErrors(nextErrors);

    if (Object.keys(nextErrors).length > 0) {
      event.preventDefault();
    }
  }

  function clearFieldError(field: "username" | "password") {
    setErrors((current) => {
      if (!current[field]) return current;
      return { ...current, [field]: undefined };
    });
  }

  return (
    <form className="login-form" action={loginAction} noValidate onSubmit={handleSubmit}>
      <label>
        Username
        <div className="input-shell">
          <span className="input-icon">
            <UserIcon />
          </span>
          <input
            className={`with-leading-icon${errors.username ? " input-invalid" : ""}`}
            name="username"
            type="text"
            required
            minLength={3}
            aria-invalid={Boolean(errors.username)}
            aria-describedby={errors.username ? "login-username-error" : undefined}
            onChange={() => clearFieldError("username")}
          />
        </div>
        {errors.username ? <span className="field-error" id="login-username-error">{errors.username}</span> : null}
      </label>
      <label>
        Password
        <div className="input-shell">
          <span className="input-icon">
            <LockIcon />
          </span>
          <input
            className={`with-leading-icon with-trailing-icon${errors.password ? " input-invalid" : ""}`}
            name="password"
            type={showPassword ? "text" : "password"}
            required
            minLength={6}
            aria-invalid={Boolean(errors.password)}
            aria-describedby={errors.password ? "login-password-error" : undefined}
            onChange={() => clearFieldError("password")}
          />
          <button
            className="input-action"
            type="button"
            onClick={() => setShowPassword((value) => !value)}
            aria-label={showPassword ? "Hide password" : "Show password"}
            aria-pressed={showPassword}
          >
            <EyeIcon open={showPassword} />
          </button>
        </div>
        {errors.password ? <span className="field-error" id="login-password-error">{errors.password}</span> : null}
      </label>
      <button className="primary" type="submit">
        Sign In
      </button>
    </form>
  );
}
