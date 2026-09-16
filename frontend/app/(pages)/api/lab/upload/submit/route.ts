import { postForm } from "@/lib/api/admin";
import { NextResponse } from "next/server";

export async function POST(request: Request) {
  try {
    const payload = await request.formData();
    const response = await postForm("/api/lab/upload", payload);

    let json: unknown = null;
    try {
      json = await response.json();
    } catch {
      json = null;
    }

    return NextResponse.json(
      json ?? { message: "Unexpected upload response." },
      { status: response.status },
    );
  } catch {
    return NextResponse.json(
      { message: "Unexpected error occurred while uploading data." },
      { status: 500 },
    );
  }
}
