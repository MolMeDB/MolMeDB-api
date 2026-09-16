import { getJson, post } from "@/lib/api/admin";
import { NextResponse } from "next/server";

export async function GET(request: Request) {
  try {
    const searchParams = new URL(request.url).searchParams;

    const response = await getJson(
      `/api/predictions/datasets?${searchParams.toString()}`,
    );

    return NextResponse.json(response?.data ?? {}, {
      status: response?.code ?? 500,
    });
  } catch {
    return NextResponse.json({ message: "Unable to load datasets." }, { status: 500 });
  }
}

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
