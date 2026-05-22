import { post } from "@/lib/api/admin";
import { NextResponse } from "next/server";
import { cookies } from "next/headers";

const SESSION_KEY = process.env.COOKIES_FRONTEND_SESSION_KEY as string;
const USER_KEY = process.env.COOKIES_FRONTEND_SESSION_USER_KEY as string;
const XSRF_KEY = process.env.COOKIES_BACKEND_XSRF_KEY as string;

export async function GET(request: Request) {
  await post("/logout");

  const res = NextResponse.redirect(new URL("/login", request.url));

  const cookieStore = await cookies();
  cookieStore.delete(SESSION_KEY);
  cookieStore.delete(USER_KEY);
  cookieStore.delete(XSRF_KEY);

  res.cookies.set(SESSION_KEY, "", { maxAge: 0, path: "/" });
  res.cookies.set(USER_KEY, "", { maxAge: 0, path: "/" });
  res.cookies.set(XSRF_KEY, "", { maxAge: 0, path: "/" });

  return res;
}
