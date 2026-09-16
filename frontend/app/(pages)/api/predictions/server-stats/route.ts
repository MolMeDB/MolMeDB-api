import { getJson } from "@/lib/api/admin";
import { NextResponse } from "next/server";

export async function GET() {
  try {
    const response = await getJson("/api/predictions/server-stats");

    return NextResponse.json(response?.data ?? null, {
      status: response?.code ?? 500,
    });
  } catch {
    return NextResponse.json({ message: "Unexpected error." }, { status: 500 });
  }
}
