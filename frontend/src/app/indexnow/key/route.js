import { NextResponse } from "next/server";

export function GET() {
  const key = process.env.INDEXNOW_KEY || "";

  if (!key) {
    return new NextResponse("INDEXNOW_KEY tanimli degil.", {
      status: 404,
      headers: {
        "Content-Type": "text/plain; charset=utf-8",
      },
    });
  }

  return new NextResponse(key, {
    status: 200,
    headers: {
      "Content-Type": "text/plain; charset=utf-8",
      "Cache-Control": "public, max-age=300",
    },
  });
}

