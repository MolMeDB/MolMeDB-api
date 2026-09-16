import { post } from "@/lib/api/admin";
import { NextResponse } from "next/server";

export async function DELETE() {
  try {
    const response = await post("/api/notifications/clear", {}, "DELETE");

    return NextResponse.json({}, { status: response.status });
  } catch {
    return NextResponse.json(
      { message: "Failed to clear notifications." },
      { status: 500 },
    );
  }
}
