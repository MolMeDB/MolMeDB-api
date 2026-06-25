import { postForm } from "@/lib/api/admin";
import { NextResponse } from "next/server";

export async function POST(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  try {
    const { id } = await params;
    const payload = await request.formData();
    const response = await postForm(`/api/lab/upload/${id}/enqueue`, payload);

    const json = await response.json();
    return NextResponse.json(json, { status: response.status });
  } catch {
    return NextResponse.json(
      { message: "Failed to send record to queue." },
      { status: 500 },
    );
  }
}

