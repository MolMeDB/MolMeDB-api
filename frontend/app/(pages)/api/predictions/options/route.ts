import { get } from "@/lib/api/admin";
import { NextResponse } from "next/server";

export async function GET() {
  try {
    const response = await get("/api/predictions/options");

    let json: unknown = null;
    try {
      json = await response.json();
    } catch {
      json = null;
    }

    return NextResponse.json(
      json ?? { message: "Unexpected prediction options response." },
      { status: response.status },
    );
  } catch {
    return NextResponse.json(
      { message: "Unexpected error occurred while loading prediction options." },
      { status: 500 },
    );
  }
}
