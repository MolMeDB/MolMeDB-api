import { Cookie } from "@/lib/api/cookies";
import { NextResponse } from "next/server";

type AuthenticationResponse = {
  data?: {
    id?: number;
    name?: string;
    email?: string;
  };
  meta?: {
    session_expires_at?: string;
  };
  message?: string;
  errors?: Record<string, string[]>;
};

export async function authenticatedSessionResponse(
  response: Response,
): Promise<NextResponse> {
  const payload = (await response.json()) as AuthenticationResponse;

  if (
    response.ok &&
    payload.data?.id &&
    payload.data.name &&
    payload.data.email
  ) {
    await Cookie.setUserData(payload.data, {
      expiresAt: payload.meta?.session_expires_at,
    });

    const nextResponse = NextResponse.json(payload.data, {
      status: response.status,
    });
    nextResponse.cookies.set("molmedb_guest", "", { maxAge: 0, path: "/" });
    nextResponse.cookies.set("molmedb_guest_access", "", {
      httpOnly: true,
      maxAge: 0,
      path: "/",
    });

    return nextResponse;
  }

  return NextResponse.json(payload, { status: response.status });
}
