import apiRoutes from "@/appConfig/apiRoutes";
import auth from "@/utils/auth";
import { cookies } from "next/headers";
import { redirect } from "next/navigation";

export default async function orderDetails(id) {
  // Get user token - try cookie first, then localStorage
  let accessToken = null;
  
  // Server-side: try cookie
  try {
    const cookieStore = await cookies();
    accessToken = cookieStore.get("access_token")?.value;
  } catch (e) {
    // Cookie access failed, continue
  }
  
  // If no cookie token, try localStorage (client-side)
  if (!accessToken && typeof window !== "undefined") {
    try {
      const userData = auth();
      accessToken = userData?.access_token;
    } catch (e) {
      // Auth access failed
    }
  }

  if (!accessToken) {
    redirect(`/login?redirect=/order/${id}`);
  }

  // Build URL - remove trailing slash from baseUrl to avoid double slash
  const baseUrl = (process.env.NEXT_PUBLIC_BASE_URL || 'http://localhost:8000').replace(/\/$/, '');
  const apiUrl = `${baseUrl}/api/user/order-show/${id}`;
  
  try {
    const res = await fetch(apiUrl, {
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${accessToken}`,
      },
      cache: "no-store",
    });

    if (!res.ok) {
      return { order: null, error: 'Order not found' };
    }

    const data = await res.json();
    return data;
  } catch (error) {
    console.error("Order details error:", error);
    return { order: null, error: error.message };
  }
}