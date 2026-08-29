import { NextResponse } from "next/server";

export async function POST(request) {
  try {
    const body = await request.json();
    console.error("[CLIENT_ERROR]", JSON.stringify(body));
  } catch {}
  return NextResponse.json({ ok: true });
}
