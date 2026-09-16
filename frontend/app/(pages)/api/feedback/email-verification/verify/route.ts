import { authenticatedSessionResponse } from "@/lib/authenticatedSessionResponse";
import { post } from "@/lib/api/admin";
import { NextResponse } from "next/server";

export async function POST(request: Request) {
  try {
    const payload = await request.json();
    const response = await post("/api/feedback/email-verification/verify", payload);
    return authenticatedSessionResponse(response);
  } catch {
    return NextResponse.json(
      { message: "Failed to verify email." },
      { status: 500 },
    );
  }
}
