import { getJson, post } from "@/lib/api/admin";
import { NextResponse } from "next/server";

export async function GET(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  try {
    const { id } = await params;
    const { searchParams } = new URL(request.url);
    const token = searchParams.get("token");
    const path = token
      ? `/api/predictions/datasets/${id}?token=${encodeURIComponent(token)}`
      : `/api/predictions/datasets/${id}`;
    const response = await getJson(path);

    return NextResponse.json(response?.data ?? null, {
      status: response?.code ?? 500,
    });
  } catch {
    return NextResponse.json({ message: "Unexpected error." }, { status: 500 });
  }
}

export async function PATCH(
  request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  try {
    const { id } = await params;
    const { searchParams } = new URL(request.url);
    const token = searchParams.get("token");
    const path = token
      ? `/api/predictions/datasets/${id}?token=${encodeURIComponent(token)}`
      : `/api/predictions/datasets/${id}`;
    const payload = await request.json();
    const response = await post(path, payload, "PATCH");

    let json: unknown = null;
    try {
      json = await response.json();
    } catch {
      json = null;
    }

    return NextResponse.json(json ?? {}, { status: response.status });
  } catch {
    return NextResponse.json({ message: "Unexpected error." }, { status: 500 });
  }
}
