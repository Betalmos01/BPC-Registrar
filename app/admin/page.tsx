import { redirect } from "next/navigation";
import { requireRole } from "@/lib/auth";

export default async function AdminEntryPage() {
  await requireRole("Administrator");
  redirect("/admin/dashboard");
}
