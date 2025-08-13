import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";
import { post } from "./lib/api/admin";

const SESSION_KEY = process.env.COOKIES_BACKEND_SESSION_KEY as string;
const XSRF_KEY = process.env.COOKIES_BACKEND_XSRF_KEY as string;

// This function can be marked `async` if using `await` inside
export async function middleware(request: NextRequest) {
  // Handle logout requests
  if (request.nextUrl.pathname == "/logout") {
    // Destroy remote session
    post("/logout");
    // Destroy local session
    request.cookies.set(SESSION_KEY, "");
    request.cookies.set(XSRF_KEY, "");
    // Redirect to login
    const redirect = NextResponse.redirect(new URL("/login", request.url));
    return redirect;
  }

  const requestHeaders = new Headers(request.headers);
  return NextResponse.next({
    request: {
      headers: requestHeaders,
    },
  });
}

// See "Matching Paths" below to learn more
export const config = {
  matcher: [
    {
      source: "/((?!_next/static|_next/image|assets|favicon.ico|sw.js).*)",
      missing: [
        { type: "header", key: "next-router-prefetch" },
        { type: "header", key: "purpose", value: "prefetch" },
      ],
    },
  ],
};
