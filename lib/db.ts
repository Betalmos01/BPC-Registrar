import { Pool, type QueryResultRow } from "pg";
import { env } from "./env";

const globalForDb = globalThis as typeof globalThis & { pool?: Pool };
const tableExistsCache = new Map<string, boolean>();

export const pool =
  globalForDb.pool ??
  new Pool({
    connectionString: env.DATABASE_URL,
    ssl: { rejectUnauthorized: false }
  });

if (process.env.NODE_ENV !== "production") {
  globalForDb.pool = pool;
}

export async function query<T extends QueryResultRow = QueryResultRow>(
  sql: string,
  params: unknown[] = []
): Promise<T[]> {
  const result = await pool.query<T>(sql, params);
  return result.rows;
}

export async function queryOne<T extends QueryResultRow = QueryResultRow>(
  sql: string,
  params: unknown[] = []
): Promise<T | null> {
  const rows = await query<T>(sql, params);
  return rows[0] ?? null;
}

export async function queryValue<T = unknown>(
  sql: string,
  params: unknown[] = []
): Promise<T | null> {
  const rows = await query<Record<string, T>>(sql, params);
  const row = rows[0];
  if (!row) return null;
  const firstKey = Object.keys(row)[0];
  return firstKey ? row[firstKey] : null;
}

export async function hasTable(tableName: string, schema = "public"): Promise<boolean> {
  const cacheKey = `${schema}.${tableName}`;
  const cached = tableExistsCache.get(cacheKey);
  if (typeof cached === "boolean") {
    return cached;
  }

  const exists = await queryValue<string>(
    "select to_regclass($1) as table_name",
    [`${schema}.${tableName}`]
  );
  const present = Boolean(exists);
  tableExistsCache.set(cacheKey, present);
  return present;
}

export async function resolveTableName(...tableNames: string[]): Promise<string | null> {
  for (const tableName of tableNames) {
    if (await hasTable(tableName)) {
      return tableName;
    }
  }

  return null;
}
