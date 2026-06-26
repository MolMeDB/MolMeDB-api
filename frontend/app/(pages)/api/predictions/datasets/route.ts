import { post } from "@/lib/api/admin";
import { NextResponse } from "next/server";

export async function POST(request: Request) {
  try {
    const payload = await request.json();
    const response = await post("/api/predictions/datasets", payload);

    let json: unknown = null;
    try {
      json = await response.json();
    } catch {
      json = null;
    }

    return NextResponse.json(
      json ?? { message: "Unexpected prediction submit response." },
      { status: response.status },
    );
  } catch {
    return NextResponse.json(
      { message: "Unexpected error occurred while submitting calculations." },
      { status: 500 },
    );
  }
}
