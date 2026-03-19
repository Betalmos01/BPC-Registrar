import Image from "next/image";

import { LoginForm } from "@/components/login-form";
import logo from "@/lib/Logo/logo.png";

export default async function LoginPage({
  searchParams
}: {
  searchParams: Promise<{ error?: string }>;
}) {
  const params = await searchParams;
  const errorMessages: Record<string, string> = {
    username_required: "Username is required.",
    username_short: "Username must be at least 3 characters.",
    password_required: "Password is required.",
    password_short: "Password must be at least 6 characters.",
    invalid_credentials: "Invalid username or password."
  };
  const errorMessage = params.error ? errorMessages[params.error] ?? "Unable to sign in." : "";

  return (
    <main className="login-page">
      <section className="login-card">
        <div className="login-brand">
          <Image className="login-brand-logo" src={logo} alt="BPC Registrar logo" priority />
          <div className="eyebrow">BPC Registrar</div>
        </div>
        {errorMessage ? <div className="error-banner">{errorMessage}</div> : null}
        <LoginForm />
      </section>
    </main>
  );
}
