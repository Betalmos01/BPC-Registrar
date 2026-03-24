import { revalidatePath } from "next/cache";
import { AppShell } from "@/components/app-shell";
import { requireRole } from "@/lib/auth";
import { getHrStaffRequestStatus, getHrStaffRequests, createHrStaffRequest, type RegistrarRoleType } from "@/lib/hr-staff-requests";
import HrStaffRequestClient from "./HrStaffRequestClient";

export const dynamic = "force-dynamic";

interface PageProps {
  searchParams?: Promise<{ search?: string; status?: string; page?: string }>;
}

export default async function HrStaffRequestPage({ searchParams }: PageProps) {
  const user = await requireRole("Administrator");
  const sp = await (searchParams ?? Promise.resolve({}));
  const search = sp.search ?? "";
  const status = sp.status ?? "all";
  const page = Math.max(1, parseInt(sp.page ?? "1", 10));

  const [statusData, pagedResult] = await Promise.all([
    getHrStaffRequestStatus().catch(() => null),
    getHrStaffRequests({ search, status, page, perPage: 10 }).catch(() => ({ items: [], total: 0 })),
  ]);

  async function handleCreate(formData: FormData) {
    "use server";
    const roleType = formData.get("roleType") as RegistrarRoleType;
    const requestedCount = parseInt(formData.get("requestedCount") as string ?? "1", 10);
    const requestedBy = (formData.get("requestedBy") as string) || "Registrar Admin";
    const requestNotes = (formData.get("requestNotes") as string) || undefined;
    await createHrStaffRequest({ roleType, requestedCount, requestedBy, requestNotes });
    revalidatePath("/admin/integrations/hr-staff-request");
  }

  return (
    <AppShell
      user={user}
      title="Request Staff from HR"
      description="Submit staffing requests to HR and track approval status for Registrar roles."
    >
      <HrStaffRequestClient
        statusData={statusData}
        requests={pagedResult.items}
        total={pagedResult.total}
        currentPage={page}
        currentSearch={search}
        currentStatus={status}
        createAction={handleCreate}
      />
    </AppShell>
  );
}
