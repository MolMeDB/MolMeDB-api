import { postForm } from "@/lib/api/admin";
import { NextResponse } from "next/server";

export async function POST(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  try {
    const { id } = await params;
    const payload = await request.formData();
    const response = await postForm(`/api/lab/upload/${id}/reupload`, payload);

    let json: unknown = null;
    try {
      json = await response.json();
    } catch {
      json = null;
    }

    return NextResponse.json(
      json ?? { message: "Unexpected reupload response." },
      { status: response.status },
    );
  } catch {
    return NextResponse.json(
      { message: "Unexpected error occurred while reuploading data." },
      { status: 500 },
    );
  }
}
