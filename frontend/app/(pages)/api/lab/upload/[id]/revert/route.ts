import { postForm } from "@/lib/api/admin";
import { NextResponse } from "next/server";

export async function POST(
  _request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  try {
    const { id } = await params;
    const payload = new FormData();
    const response = await postForm(`/api/lab/upload/${id}/revert`, payload);

    const json = await response.json();
    return NextResponse.json(json, { status: response.status });
  } catch {
    return NextResponse.json(
      { message: "Failed to revert upload record." },
      { status: 500 },
    );
  }
}
