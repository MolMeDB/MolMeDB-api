import { get } from "@/lib/api/admin";
import { NextResponse } from "next/server";

export async function GET(request: Request) {
  // Get

  const url = new URL(request.url);
  const smiles = url.searchParams.get("smiles");

  if (!smiles) {
    return NextResponse.json({ error: "No smiles provided" }, { status: 400 });
  }

  const result = await get(
    "/api/structure/mol/canonize_smiles/" + encodeURIComponent(smiles),
  );

  if (result.status === 503) {
    return NextResponse.json("Service is temporarily unavailable.", {
      status: 503,
    });
  }

  if (result.status === 200) {
    return NextResponse.json(await result.json(), { status: 200 });
  }

  return NextResponse.json("Unspecified error. Please try again.", {
    status: 500,
  });
}
