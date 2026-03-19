import Link from "next/link";

export default function AccessDeniedPage() {
  return (
    <main className="login-page">
      <section className="login-card">
        <div className="eyebrow">Access Control</div>
        <h1>Access denied</h1>
        <p>This route is protected. Sign in with the right registrar role to continue.</p>
        <div className="actions-row top-gap">
          <Link href="/" className="primary inline-button">Back to Login</Link>
          <Link href="/profile" className="secondary inline-button">My Profile</Link>
        </div>
      </section>
    </main>
  );
}
