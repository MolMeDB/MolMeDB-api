import { get } from "@/lib/api/admin";

export async function GET(request: Request) {
  const path = request.headers?.get("Forward-To");

  if (!path) {
    throw new Error("No path provided.");
  }

  const { searchParams } = new URL(request.url);
  // console.log(searchParams.toString());
  // var queryParams: {
  //   [key: string]: any;
  // } = Object.fromEntries(searchParams.entries());

  // for (const key in queryParams) {
  //   if (key.endsWith("[]")) {
  //     queryParams[key] = queryParams[key].split(",");
  //   }
  // }

  console.log("Request To", path, searchParams.toString());

  return get(
    `/api${path.startsWith("/") ? path : "/" + path}`,
    searchParams.toString()
  );
}
