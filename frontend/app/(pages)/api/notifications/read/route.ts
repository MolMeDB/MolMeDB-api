import { post } from "@/lib/api/admin";
import { NextResponse } from "next/server";

export async function POST() {
  try {
    const response = await post("/api/notifications/read", {});
    const json = await response.json();

    return NextResponse.json(json, { status: response.status });
  } catch {
    return NextResponse.json(
      { message: "Failed to mark notifications as read." },
      { status: 500 },
    );
  }
}
