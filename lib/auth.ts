import { redirect } from "next/navigation";
import { getSessionUser } from "./session";

function normalizeRequestedRole(role: string) {
  const normalized = role.trim().toLowerCase();
  if (normalized === "administrator" || normalized === "admin") {
    return "admin";
  }
  return "staff";
}

export async function requireUser() {
  const user = await getSessionUser();
  if (!user) {
    redirect("/");
  }
  return user;
}

export async function requireRole(role: "Administrator" | "Registrar Staff" | "admin" | "staff") {
  const user = await requireUser();
  const userRole = normalizeRequestedRole(user.role);
  const needed = normalizeRequestedRole(role);

  if (needed === "staff" && userRole === "admin") {
    return user;
  }

  if (userRole !== needed) {
    redirect(userRole === "admin" ? "/admin/dashboard" : "/staff/dashboard");
  }

  return user;
}
