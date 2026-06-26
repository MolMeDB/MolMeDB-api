import { getJson, post } from "@/lib/api/admin";
import { NextResponse } from "next/server";

export async function GET(
  _request: Request,
  { params }: { params: Promise<{ id: string }> },
) {
  try {
    const { id } = await params;
    const response = await getJson(`/api/predictions/datasets/${id}`);

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
    const payload = await request.json();
    const response = await post(`/api/predictions/datasets/${id}`, payload, "PATCH");

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
