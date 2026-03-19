import { env } from "@/lib/env";
import { getIntegrationPayload } from "@/lib/integration-payload";

type DeliveryTarget = {
  consumer: string;
  url: string;
};

function readTargets(resource: string): DeliveryTarget[] {
  const targetMap: Record<string, DeliveryTarget[]> = {
    "student-list": [
      env.CRAD_STUDENT_LIST_ENDPOINT ? { consumer: "CRADManagement", url: env.CRAD_STUDENT_LIST_ENDPOINT } : null,
      env.COMLAB_STUDENT_LIST_ENDPOINT ? { consumer: "Computer Laboratory", url: env.COMLAB_STUDENT_LIST_ENDPOINT } : null
    ].filter(Boolean) as DeliveryTarget[],
    "student-personal-info": [
      env.PREFECT_STUDENT_PERSONAL_INFO_ENDPOINT ? { consumer: "PrefectManagementsSystem", url: env.PREFECT_STUDENT_PERSONAL_INFO_ENDPOINT } : null,
      env.CLINIC_STUDENT_PERSONAL_INFO_ENDPOINT ? { consumer: "Clinic", url: env.CLINIC_STUDENT_PERSONAL_INFO_ENDPOINT } : null,
      env.GUIDANCE_STUDENT_PERSONAL_INFO_ENDPOINT ? { consumer: "Guidance", url: env.GUIDANCE_STUDENT_PERSONAL_INFO_ENDPOINT } : null
    ].filter(Boolean) as DeliveryTarget[],
    "enrollment-data": [
      env.CASHIER_ENROLLMENT_DATA_ENDPOINT ? { consumer: "Cashier", url: env.CASHIER_ENROLLMENT_DATA_ENDPOINT } : null
    ].filter(Boolean) as DeliveryTarget[],
    "student-academic-records": [
      env.GUIDANCE_STUDENT_ACADEMIC_RECORDS_ENDPOINT ? { consumer: "Guidance", url: env.GUIDANCE_STUDENT_ACADEMIC_RECORDS_ENDPOINT } : null
    ].filter(Boolean) as DeliveryTarget[],
    "enrollment-statistics": [
      env.PMED_ENROLLMENT_STATISTICS_ENDPOINT ? { consumer: "PMED", url: env.PMED_ENROLLMENT_STATISTICS_ENDPOINT } : null
    ].filter(Boolean) as DeliveryTarget[]
  };

  return targetMap[resource] ?? [];
}

async function parseResponse(response: Response) {
  const text = await response.text();
  try {
    return text ? JSON.parse(text) : null;
  } catch {
    return text;
  }
}

export async function deliverIntegrationResource(resource: string, options: { studentNo?: string; studentId?: number }) {
  const payload = await getIntegrationPayload(resource, options);
  const targets = readTargets(resource);

  if (targets.length === 0) {
    return {
      ok: false,
      message: "No delivery endpoint is configured for this integration resource yet.",
      payload,
      deliveries: []
    };
  }

  const deliveries = await Promise.all(
    targets.map(async (target) => {
      try {
        const response = await fetch(target.url, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            ...(env.INTEGRATION_API_KEY ? { "x-integration-key": env.INTEGRATION_API_KEY } : {})
          },
          body: JSON.stringify({
            resource,
            source: "Registrar",
            sent_at: new Date().toISOString(),
            data: payload
          }),
          cache: "no-store"
        });

        return {
          consumer: target.consumer,
          url: target.url,
          ok: response.ok,
          status: response.status,
          response: await parseResponse(response)
        };
      } catch (error) {
        return {
          consumer: target.consumer,
          url: target.url,
          ok: false,
          status: 0,
          response: {
            error: error instanceof Error ? error.message : "Request failed."
          }
        };
      }
    })
  );

  return {
    ok: deliveries.every((delivery) => delivery.ok),
    message: deliveries.every((delivery) => delivery.ok)
      ? "Integration delivery completed."
      : "One or more integration deliveries failed.",
    payload,
    deliveries
  };
}
