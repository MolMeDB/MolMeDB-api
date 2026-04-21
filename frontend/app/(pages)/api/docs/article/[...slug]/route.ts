import { get } from "@/lib/api/admin";
import { NextResponse } from "next/server";

export async function GET(
  _request: Request,
  { params }: { params: Promise<{ slug: string[] }> },
) {
  try {
    const { slug } = await params;
    if (!Array.isArray(slug) || slug.length === 0 || slug.length > 2) {
      return NextResponse.json(
        { message: "Documentation article was not found." },
        { status: 404 },
      );
    }

    const path = Array.isArray(slug) ? slug.join("/") : "";
    const response = await get(`/api/docs/article/${path}`);
    const json = await response.json();

    return NextResponse.json(json, { status: response.status });
  } catch {
    return NextResponse.json(
      { message: "Failed to load documentation article." },
      { status: 500 },
    );
  }
}
