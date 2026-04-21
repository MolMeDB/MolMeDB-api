import { get } from "@/lib/api/admin";
import { NextResponse } from "next/server";

export async function GET() {
  try {
    const response = await get("/api/docs/article");
    const json = await response.json();

    return NextResponse.json(json, { status: response.status });
  } catch {
    return NextResponse.json(
      { message: "Failed to load documentation article." },
      { status: 500 },
    );
  }
}

