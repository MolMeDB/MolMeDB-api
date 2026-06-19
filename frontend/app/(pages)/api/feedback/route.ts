import { Cookie } from "@/lib/api/cookies";
import { post } from "@/lib/api/admin";
import { UserSession } from "@/lib/api/admin/interfaces/User";
import { NextResponse } from "next/server";

export async function POST(request: Request) {
  try {
    const payload = await request.json();
    const user = (await Cookie.getUserData()) as UserSession | undefined;
    const endpoint = user?.email ? "/api/feedback/authenticated" : "/api/feedback";
    const response = await post(endpoint, payload);
    const json = await response.json();

    return NextResponse.json(json, { status: response.status });
  } catch {
    return NextResponse.json(
      { message: "Failed to send feedback." },
      { status: 500 },
    );
  }
}
