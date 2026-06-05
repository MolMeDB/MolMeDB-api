import { get } from "@/lib/api/admin";
import { NextResponse } from "next/server";

const downloadErrorHtml = `<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Download error</title>
    <style>
      body {
        margin: 0;
        min-height: 100vh;
        display: grid;
        place-items: center;
        font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        background: #f8fafc;
        color: #111827;
      }

      main {
        width: min(92vw, 34rem);
        padding: 2rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        background: #ffffff;
        box-shadow: 0 16px 40px rgb(15 23 42 / 0.08);
      }

      h1 {
        margin: 0 0 0.75rem;
        font-size: 1.25rem;
        line-height: 1.4;
      }

      p {
        margin: 0;
        color: #4b5563;
        line-height: 1.6;
      }
    </style>
  </head>
  <body>
    <main>
      <h1>File download failed</h1>
      <p>An error occurred while preparing the file. Please try again later.</p>
    </main>
  </body>
</html>`;

function downloadErrorResponse(status = 500) {
  return new NextResponse(downloadErrorHtml, {
    status,
    headers: {
      "content-type": "text/html; charset=utf-8",
      "cache-control": "no-store, no-cache, must-revalidate",
    },
  });
}

export async function GET(
  request: Request,
  { params }: { params: Promise<{ hash: string }> },
) {
  try {
    const { hash } = await params;
    const url = new URL(request.url);

    const backendResponse = await get(
      `/download/public/${hash}`,
      url.searchParams.toString(),
    );

    if (!backendResponse.ok) {
      return downloadErrorResponse(backendResponse.status);
    }

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
    return downloadErrorResponse();
  }
}
