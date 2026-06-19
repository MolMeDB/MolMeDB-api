"use server";
import { cookies as Cookies } from "next/headers";
import { headers as Headers } from "next/headers";
import { DEFAULT_COOKIES_CONFIG } from "../cookies";
import { ResponseCookie } from "next/dist/compiled/@edge-runtime/cookies";
import { selectedValuesToSearchParamsString } from "@/utils/searchParams";
import HttpJsonResponse from "./interfaces/http/jsonResponse";
import { redirect } from "next/navigation";

const baseUrl = process.env.NEXT_BACKEND_URL as string;
const XSRF_KEY = process.env.COOKIES_BACKEND_XSRF_KEY as string;
const BE_SESSION_KEY = process.env.COOKIES_BACKEND_SESSION_KEY as string;
const FE_SESSION_KEY = process.env.COOKIES_FRONTEND_SESSION_KEY as string;

type BackendCookies = {
  session: string;
  xsrfToken: string;
};

type ParsedSetCookie = {
  name: string;
  value: string;
  options: Partial<ResponseCookie>;
};

function splitSetCookieHeader(header: string): string[] {
  const cookies: string[] = [];
  let start = 0;
  let inExpires = false;

  for (let index = 0; index < header.length; index++) {
    const char = header[index];
    const slice = header.slice(index, index + 9).toLowerCase();

    if (slice === "expires=") {
      inExpires = true;
    }

    if (inExpires && char === ";") {
      inExpires = false;
    }

    if (!inExpires && char === "," && header[index + 1] === " ") {
      cookies.push(header.slice(start, index).trim());
      start = index + 2;
    }
  }

  const lastCookie = header.slice(start).trim();
  if (lastCookie) {
    cookies.push(lastCookie);
  }

  return cookies;
}

function setCookieHeaders(res: Response): string[] {
  const getSetCookie = (
    res.headers as globalThis.Headers & { getSetCookie?: () => string[] }
  ).getSetCookie;

  if (typeof getSetCookie === "function") {
    return getSetCookie.call(res.headers);
  }

  const header = res.headers.get("set-cookie");

  return header ? splitSetCookieHeader(header) : [];
}

function parseSetCookie(header: string): ParsedSetCookie | null {
  const parts = header.split(";").map((part) => part.trim());
  const [nameValue, ...attributes] = parts;
  const separatorIndex = nameValue.indexOf("=");

  if (separatorIndex < 1) {
    return null;
  }

  const options: Partial<ResponseCookie> = {
    ...DEFAULT_COOKIES_CONFIG,
  };

  for (const attribute of attributes) {
    const [rawName, ...rawValue] = attribute.split("=");
    const name = rawName.toLowerCase();
    const value = rawValue.join("=");

    if (name === "max-age" && value !== "") {
      options.maxAge = Number(value);
    }

    if (name === "expires" && value !== "") {
      const expires = new Date(value);
      if (!Number.isNaN(expires.getTime())) {
        options.expires = expires;
        delete options.maxAge;
      }
    }

    if (name === "path" && value !== "") {
      options.path = value;
    }

    if (name === "domain" && value !== "") {
      options.domain = value;
    }

    if (name === "samesite" && value !== "") {
      options.sameSite = value.toLowerCase() as ResponseCookie["sameSite"];
    }

    if (name === "secure") {
      options.secure = true;
    }

    if (name === "httponly") {
      options.httpOnly = true;
    }
  }

  return {
    name: nameValue.slice(0, separatorIndex),
    value: nameValue.slice(separatorIndex + 1),
    options,
  };
}

function rememberCookiesHeader(cookies: Awaited<ReturnType<typeof Cookies>>): string {
  return cookies
    .getAll()
    .filter((cookie) => cookie.name.startsWith("remember_"))
    .map((cookie) => `${cookie.name}=${cookie.value}`)
    .join("; ");
}

function backendCookieHeader(
  xsrfToken?: string,
  session?: string,
  rememberCookies = "",
): string {
  return [
    xsrfToken ? `${XSRF_KEY}=${xsrfToken}` : null,
    session ? `${BE_SESSION_KEY}=${session}` : null,
    rememberCookies || null,
  ]
    .filter(Boolean)
    .join("; ");
}

function decodeCookieValue(value: string): string {
  try {
    return decodeURIComponent(value);
  } catch {
    return value;
  }
}

async function forwardedHeaders(): Promise<Record<string, string>> {
  try {
    const incomingHeaders = await Headers();
    const headers: Record<string, string> = {};
    const forwardedFor =
      incomingHeaders.get("x-forwarded-for") ??
      incomingHeaders.get("x-real-ip");
    const forwardedProto = incomingHeaders.get("x-forwarded-proto");
    const forwardedHost =
      incomingHeaders.get("x-forwarded-host") ?? incomingHeaders.get("host");

    if (forwardedFor) {
      headers["X-Forwarded-For"] = forwardedFor;
      headers["X-Real-IP"] = forwardedFor.split(",")[0].trim();
    }

    if (forwardedProto) {
      headers["X-Forwarded-Proto"] = forwardedProto;
    }

    if (forwardedHost) {
      headers["X-Forwarded-Host"] = forwardedHost;
    }

    return headers;
  } catch {
    return {};
  }
}

async function updateCookies(res: Response): Promise<BackendCookies | null> {
  const parsedCookies = setCookieHeaders(res)
    .map(parseSetCookie)
    .filter((cookie): cookie is ParsedSetCookie => cookie !== null);

  const XSRF_TOKEN = parsedCookies.find((cookie) => cookie.name === XSRF_KEY);
  const SESSION = parsedCookies.find((cookie) => cookie.name === BE_SESSION_KEY);

  if (!XSRF_TOKEN || !SESSION) {
    if (parsedCookies.length > 0) {
      const cookiesStore = await Cookies();

      for (const cookie of parsedCookies.filter((cookie) =>
        cookie.name.startsWith("remember_"),
      )) {
        cookiesStore.set(cookie.name, cookie.value, cookie.options);
      }
    }

    return null;
  }

  const cookiesStore = await Cookies();
  cookiesStore.set(XSRF_KEY, XSRF_TOKEN.value, XSRF_TOKEN.options);
  cookiesStore.set(FE_SESSION_KEY, SESSION.value, SESSION.options);

  for (const cookie of parsedCookies.filter((cookie) =>
    cookie.name.startsWith("remember_"),
  )) {
    cookiesStore.set(cookie.name, cookie.value, cookie.options);
  }

  return {
    session: SESSION.value,
    xsrfToken: XSRF_TOKEN.value,
  };
}

async function refreshCSRF() {
  console.log("Refreshing CSRF");
  const proxyHeaders = await forwardedHeaders();
  const res = await fetch(`${baseUrl}/sanctum/csrf-cookie`, {
    method: "GET",
    credentials: "include",
    headers: {
      Accept: "application/json",
      Referer: process.env.FRONTEND_URL as string,
      ...proxyHeaders,
    },
  });

  return await updateCookies(res);
}

async function _post(
  uri: string,
  data = {},
  method = "POST",
  backendCookies?: BackendCookies | null,
) {
  const cks = await Cookies();

  const SESSION = backendCookies?.session ?? (cks.get(FE_SESSION_KEY)?.value as string);
  const XSRF_TOKEN =
    backendCookies?.xsrfToken ?? (cks.get(XSRF_KEY)?.value as string);
  const XSRF_HEADER = XSRF_TOKEN ? decodeCookieValue(XSRF_TOKEN) : "";
  const REMEMBER_COOKIES = rememberCookiesHeader(cks);
  const proxyHeaders = await forwardedHeaders();

  // console.log("POST", uri);
  // console.log("COOK", SESSION);
  // console.log("COOK2", XSRF_TOKEN);

  // Přidáme credentials a X-XSRF-TOKEN
  const result = await fetch(`${baseUrl}${uri}`, {
    method,
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
      Referer: process.env.FRONTEND_URL as string,
      Cookie: backendCookieHeader(XSRF_TOKEN, SESSION, REMEMBER_COOKIES),
      "X-XSRF-TOKEN": XSRF_HEADER,
      ...proxyHeaders,
    },
    body: JSON.stringify(data),
  });

  if (result.status == 419) {
    return false;
  }

  await updateCookies(result);

  return result;
}

async function _postForm(
  uri: string,
  data: FormData,
  method = "POST",
  backendCookies?: BackendCookies | null,
) {
  const cks = await Cookies();

  const SESSION = backendCookies?.session ?? (cks.get(FE_SESSION_KEY)?.value as string);
  const XSRF_TOKEN =
    backendCookies?.xsrfToken ?? (cks.get(XSRF_KEY)?.value as string);
  const XSRF_HEADER = XSRF_TOKEN ? decodeCookieValue(XSRF_TOKEN) : "";
  const REMEMBER_COOKIES = rememberCookiesHeader(cks);
  const proxyHeaders = await forwardedHeaders();

  const result = await fetch(`${baseUrl}${uri}`, {
    method,
    credentials: "include",
    headers: {
      Accept: "application/json",
      Referer: process.env.FRONTEND_URL as string,
      Cookie: backendCookieHeader(XSRF_TOKEN, SESSION, REMEMBER_COOKIES),
      "X-XSRF-TOKEN": XSRF_HEADER,
      ...proxyHeaders,
    },
    body: data,
  });

  if (result.status == 419) {
    return false;
  }

  await updateCookies(result);

  return result;
}

export async function post(uri: string, data = {}, method = "POST") {
  let result = await _post(uri, data, method);
  if (result === false) {
    // refresh CSRF
    const backendCookies = await refreshCSRF();
    result = await _post(uri, data, method, backendCookies);
    if (result === false) {
      // Cannot refresch CSRF? Error!
      throw new Error("Cannot refresh CSRF."); // TODO RemoteServerError?
    }
  }
  return result;
}

export async function postForm(
  uri: string,
  data: FormData,
  method = "POST",
) {
  let result = await _postForm(uri, data, method);
  if (result === false) {
    const backendCookies = await refreshCSRF();
    result = await _postForm(uri, data, method, backendCookies);
    if (result === false) {
      throw new Error("Cannot refresh CSRF.");
    }
  }
  return result;
}

export async function postJson(
  uri: string,
  data = {},
): Promise<HttpJsonResponse | null> {
  const result = await post(uri, data);

  try {
    if (result.status == 204) {
      return {
        code: result.status,
        data: null,
      };
    }
    return handleBackendException(await result.json(), result);
  } catch (e) {
    console.error(e);
    return null;
  }
}

export async function deleteJson(
  uri: string,
  data = {},
): Promise<HttpJsonResponse | null> {
  const result = await post(uri, data, "DELETE");

  try {
    if (result.status == 204) {
      return {
        code: result.status,
        data: null,
      };
    }
    return handleBackendException(await result.json(), result);
  } catch (e) {
    console.error(e);
    return null;
  }
}

function handleBackendException(
  jsonContent: any,
  response: Response,
): HttpJsonResponse {
  if (jsonContent.error && response.status !== 200) {
    return {
      code: response.status,
      message: jsonContent.error,
      data: null,
    };
  }
  if (jsonContent.errors && response.status !== 200) {
    return {
      code: response.status,
      message: jsonContent.message,
      errors: jsonContent.errors,
      data: null,
    };
  }
  if (jsonContent.exception && response.status !== 200) {
    return {
      code: response.status,
      message: jsonContent.exception,
      data: null,
    };
  }
  if (response.status !== 200 && response.status !== 201) {
    return {
      code: response.status,
      message: jsonContent.message,
      data: null,
    };
  }

  return {
    code: response.status,
    data: jsonContent,
  };
}

async function _get(
  uri: string,
  data:
    | string
    | {
        [key: string]: Set<string | number>;
      } = {},
  signal?: AbortSignal,
) {
  const cks = await Cookies();

  const SESSION = cks.get(FE_SESSION_KEY)?.value as string;
  const XSRF_TOKEN = cks.get(XSRF_KEY)?.value as string;
  const REMEMBER_COOKIES = rememberCookiesHeader(cks);
  const proxyHeaders = await forwardedHeaders();
  // Filter data
  // data = Object.fromEntries(
  //   Object.entries(data).filter(
  //     ([_, value]) =>
  //       value !== undefined &&
  //       value !== "undefined" &&
  //       value?.toString().trim() !== ""
  //   )
  // );

  // Add params
  let queryString = data;
  if (data instanceof Object)
    queryString = selectedValuesToSearchParamsString(data);
  if (queryString) {
    uri = `${uri}?${queryString}`;
  }

  // console.log("TO BE", uri);

  // Přidáme credentials a X-XSRF-TOKEN
  const result = await fetch(`${baseUrl}${uri}`, {
    method: "get",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
      Referer: process.env.FRONTEND_URL as string,
      Cookie: backendCookieHeader(XSRF_TOKEN, SESSION, REMEMBER_COOKIES),
      "X-XSRF-TOKEN": XSRF_TOKEN,
      ...proxyHeaders,
    },
  });

  if (result.status == 419) {
    return false;
  }

  await updateCookies(result);

  return result;
}

export async function get(
  uri: string,
  data:
    | string
    | {
        [key: string]: Set<string | number>;
      } = {},
  signal?: AbortSignal,
) {
  "use server";
  let result = await _get(uri, data, signal);
  if (result === false) {
    // console.log("Repeating request");
    // refresh CSRF
    await refreshCSRF();
    result = await _get(uri, data, signal);
    if (result === false) {
      // Cannot refresch CSRF? Error!
      throw new Error("Cannot refresh CSRF."); // TODO RemoteServerError?
    }
  }
  return result;
}

export async function getJson(
  uri: string,
  data = {},
  signal?: AbortSignal,
): Promise<HttpJsonResponse | null> {
  const result = await get(uri, data, signal);

  try {
    return handleBackendException(await result.json(), result);
  } catch {
    return null;
  }
}
