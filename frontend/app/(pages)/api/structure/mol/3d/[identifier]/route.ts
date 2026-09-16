import { get } from "@/lib/api/admin";
import { NextResponse } from "next/server";

export async function GET(
  _request: Request,
  { params }: { params: Promise<{ identifier: string }> },
) {
  try {
    const { identifier } = await params;

    const backendResponse = await get(
      `/api/structure/mol/3d/${identifier}`,
      {},
      { auth: false, revalidate: 86400 },
    );

    const body = await (backendResponse as Response).arrayBuffer();
    const headers = new Headers();

    const contentType = (backendResponse as Response).headers.get("content-type") || "chemical/x-mdl-molfile";
    headers.set("content-type", contentType);
    headers.set("cache-control", "public, max-age=86400");

    return new NextResponse(body, {
      status: (backendResponse as Response).status,
      headers,
    });
  } catch {
    return NextResponse.json(
      { message: "Failed to load 3D structure." },
      { status: 500 },
    );
  }
}
