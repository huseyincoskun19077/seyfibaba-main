export async function fetchSalonCalendar(token) {
  const clean = String(token || "").trim();
  if (!/^[a-zA-Z0-9]{8,40}$/.test(clean)) {
    return null;
  }
  try {
    const res = await fetch(`/api/user/salon-crm/calendar/${encodeURIComponent(clean)}`, {
      cache: "no-store",
      headers: { Accept: "application/json" },
    });
    if (!res.ok) return null;
    return await res.json();
  } catch {
    return null;
  }
}
