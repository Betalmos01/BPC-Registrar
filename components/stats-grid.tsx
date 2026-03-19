export function StatsGrid({
  stats
}: {
  stats: Array<{ label: string; value: number | string; note: string }>;
}) {
  return (
    <section className="stats-grid">
      {stats.map((stat) => (
        <article key={stat.label} className="stat-card">
          <div className="stat-label">{stat.label}</div>
          <div className="stat-value">{stat.value}</div>
          <div className="stat-note">{stat.note}</div>
        </article>
      ))}
    </section>
  );
}
