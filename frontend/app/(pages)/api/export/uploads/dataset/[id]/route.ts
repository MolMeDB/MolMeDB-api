import { get } from "@/lib/api/admin";
import { NextResponse } from "next/server";

export async function GET(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  try {
    const { id } = await params;
    const url = new URL(request.url);

    const backendResponse = await get(
      `/export/upload-queue/raw/${id}`,
      url.searchParams.toString(),
    );

    const body = await backendResponse.arrayBuffer();

    const headers = new Headers();
    const contentType =
      backendResponse.headers.get("content-type") || "application/octet-stream";
    const contentDisposition = backendResponse.headers.get(
      "content-disposition",
    );
    const cacheControl = backendResponse.headers.get("cache-control");

    headers.set("content-type", contentType);

    if (contentDisposition) {
      headers.set("content-disposition", contentDisposition);
    }

    if (cacheControl) {
      headers.set("cache-control", cacheControl);
    }

    return new NextResponse(body, {
      status: backendResponse.status,
      headers,
    });
  } catch {
    return NextResponse.json(
      { message: "Failed to export dataset file." },
      { status: 500 },
    );
  }
}
