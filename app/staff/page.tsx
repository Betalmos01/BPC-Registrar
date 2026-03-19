import { redirect } from "next/navigation";
import { requireRole } from "@/lib/auth";

export default async function StaffEntryPage() {
  await requireRole("Registrar Staff");
  redirect("/staff/dashboard");
}
