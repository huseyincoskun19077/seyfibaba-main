import { NextResponse } from "next/server";
import { submitIndexNowUrls } from "@/lib/indexnow";

export async function POST(request) {
  const expectedSecret = process.env.INDEXNOW_SECRET;
  const providedSecret =
    request.headers.get("x-indexnow-secret") ||
    request.headers.get("authorization")?.replace(/^Bearer\s+/i, "");

  if (expectedSecret && providedSecret !== expectedSecret) {
    return NextResponse.json(
      { message: "Yetkisiz IndexNow istegi." },
      { status: 401 }
    );
  }

  let payload = {};

  try {
    payload = await request.json();
  } catch (error) {
    return NextResponse.json(
      { message: "Gecersiz JSON payload." },
      { status: 400 }
    );
  }

  const result = await submitIndexNowUrls(payload?.urls || []);

  if (!result.ok && !result.skipped) {
    return NextResponse.json(
      {
        message: "IndexNow gonderimi basarisiz oldu.",
        ...result,
      },
      { status: result.status || 502 }
    );
  }

  return NextResponse.json(result, {
    status: result.ok ? 200 : 202,
  });
}

