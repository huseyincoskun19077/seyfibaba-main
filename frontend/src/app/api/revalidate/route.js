import { revalidatePath } from "next/cache";
import { NextResponse } from "next/server";

export async function GET(request) {
  const { searchParams } = new URL(request.url);
  const secret = searchParams.get("secret");

  if (secret !== process.env.REVALIDATE_SECRET) {
    return NextResponse.json({ error: "Geçersiz token" }, { status: 401 });
  }

  const path = searchParams.get("path") || "/";

  try {
    revalidatePath(path);
    return NextResponse.json({ revalidated: true, path, now: Date.now() });
  } catch (err) {
    return NextResponse.json(
      { error: "Revalidation başarısız", detail: String(err) },
      { status: 500 }
    );
  }
}
