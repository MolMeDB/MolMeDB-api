"use server";

import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import HttpJsonResponse from "../admin/interfaces/http/jsonResponse";

const DOMAIN = process.env.FRONTEND_URL as string;
const XSRF_KEY = process.env.COOKIES_BACKEND_XSRF_KEY as string;
const SESSION_KEY = process.env.COOKIES_BACKEND_SESSION_KEY as string;

export async function getApiRequestHeaders(path: string): Promise<RequestInit> {
  const cookiesStore = await cookies();
  const SESSION = cookiesStore.get(SESSION_KEY)?.value as string;
  const XSRF_TOKEN = cookiesStore.get(XSRF_KEY)?.value as string;

  return {
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
      Referer: process.env.FRONTEND_URL as string,
      Cookie: `${XSRF_KEY}=${XSRF_TOKEN}; ${SESSION_KEY}=${SESSION}`,
      "Forward-To": path,
    },
  };
}

export async function getViewData(
  path: string,
  params = {}
): Promise<HttpJsonResponse | null> {
  // Add params
  const queryString = new URLSearchParams(params).toString();
  let endpoint = "/api/wrapper/viewData";
  if (queryString) {
    endpoint = `${endpoint}?${queryString}`;
  }

  let redirectTo = null;

  console.log("GET", DOMAIN + endpoint);

  try {
    const response = await fetch(`${DOMAIN}${endpoint}`, {
      ...(await getApiRequestHeaders(path)),
      method: "get",
    });

    if (response.status == 401) {
      redirectTo = "/login";
    } else if (response.status == 200) {
      const d = await response.json();
      return {
        code: response.status,
        data: d,
      };
    } else {
      return {
        code: response.status,
        message: await response.text(),
      };
    }
  } catch {
    return {
      code: 500,
      message: "Neplatná odpověď serveru. Zkuste to znovu.",
    };
  }

  if (redirectTo) redirect(redirectTo);

  return null;
}
