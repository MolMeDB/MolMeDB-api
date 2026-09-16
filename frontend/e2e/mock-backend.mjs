// Minimal stand-in for the Laravel backend, used only by Playwright e2e
// tests. The frontend's data-fetching for pages like /login runs as Next.js
// Server Actions/Server Components (server-to-server fetches), which
// Playwright cannot intercept at the browser level — so instead of mocking
// requests, NEXT_BACKEND_URL is pointed at this real (but fake) HTTP server.
//
// Add a route here only when a new e2e test needs the frontend to reach the
// "backend" for something specific — keep it minimal.
import { createServer } from "node:http";

const port = process.env.MOCK_BACKEND_PORT
  ? Number(process.env.MOCK_BACKEND_PORT)
  : 4001;

const server = createServer((req, res) => {
  const chunks = [];
  req.on("data", (chunk) => chunks.push(chunk));
  req.on("end", () => {
    // Playwright's webServer readiness check does a GET on this URL and
    // expects a non-error status before starting tests.
    if (req.method === "GET" && req.url === "/") {
      res.writeHead(200, { "Content-Type": "text/plain" });
      res.end("mock-backend ok");
      return;
    }

    if (req.method === "POST" && req.url === "/login") {
      res.writeHead(200, { "Content-Type": "application/json" });
      res.end(
        JSON.stringify({
          data: { id: 1, name: "E2E Test User", email: "e2e@example.test" },
          meta: {},
        })
      );
      return;
    }

    res.writeHead(404, { "Content-Type": "application/json" });
    res.end(JSON.stringify({ message: `Not mocked: ${req.method} ${req.url}` }));
  });
});

server.listen(port, () => {
  console.log(`[mock-backend] listening on http://127.0.0.1:${port}`);
});
