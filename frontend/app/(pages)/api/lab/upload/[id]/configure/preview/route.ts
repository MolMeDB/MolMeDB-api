import { get } from "@/lib/api/admin";
import { NextResponse } from "next/server";

export async function GET(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  try {
    const { id } = await params;
    const url = new URL(request.url);
    const response = await get(
      `/api/lab/upload/${id}/configure/preview`,
      url.searchParams.toString(),
    );

    const json = await response.json();
    return NextResponse.json(json, { status: response.status });
  } catch {
    return NextResponse.json(
      { message: "Failed to load upload preview." },
      { status: 500 },
    );
  }
}

