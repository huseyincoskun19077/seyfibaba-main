import { NextResponse } from "next/server";
import {
  isMarketplaceHost,
  isSecondHandHost,
  isSecondHandPublicPath,
  isSecondHandSubdomainEnabled,
  MARKETPLACE_ORIGIN,
  SECOND_HAND_ORIGIN,
} from "@/utils/secondHandSite";

export function proxy(request) {
  const host =
    request.headers.get("x-forwarded-host") ||
    request.headers.get("host") ||
    "";
  const { pathname, search } = request.nextUrl;

  if (isSecondHandSubdomainEnabled()) {
    if (isMarketplaceHost(host) && isSecondHandPublicPath(pathname)) {
      return NextResponse.redirect(
        `${SECOND_HAND_ORIGIN}${pathname}${search}`,
        301
      );
    }

    if (isSecondHandHost(host)) {
      if (pathname === "/") {
        const url = request.nextUrl.clone();
        url.pathname = "/ikinci-el";
        return NextResponse.rewrite(url);
      }

      if (isSecondHandPublicPath(pathname)) {
        return NextResponse.next();
      }

      return NextResponse.redirect(
        `${MARKETPLACE_ORIGIN}${pathname}${search}`,
        302
      );
    }
  }

  const accessToken = request.cookies.get("access_token")?.value;
  const isValidToken = accessToken && accessToken.length > 10;
  const privateRoutes = [
    "/become-seller",
    "/profile",
    "/tracking-order",
    "/wishlist",
    "/products-compare",
  ];
  const isPrivateRoute = privateRoutes.some((route) =>
    pathname.startsWith(route)
  );
  const isLoginPage = pathname === "/login";

  if (!isValidToken && isPrivateRoute) {
    const loginUrl = new URL("/login", request.url);
    return NextResponse.redirect(loginUrl);
  }

  if (isValidToken && isLoginPage) {
    const homeUrl = new URL("/", request.url);
    return NextResponse.redirect(homeUrl);
  }

  return NextResponse.next();
}

export const config = {
  matcher: [
    "/((?!_next/|favicon.ico|robots.txt|sitemap.xml|uploads/).*)",
  ],
};
