import { post } from "@/lib/api/admin";
import { NextResponse } from "next/server";

export async function POST(request: Request) {
  try {
    const payload = await request.json();
    const response = await post("/api/feedback/email-verification", payload);
    const json = await response.json();

    return NextResponse.json(json, { status: response.status });
  } catch {
    return NextResponse.json(
      { message: "Failed to send verification code." },
      { status: 500 },
    );
  }
}
