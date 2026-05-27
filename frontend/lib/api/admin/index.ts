"use server";
import { cookies as Cookies } from "next/headers";
import { DEFAULT_COOKIES_CONFIG } from "../cookies";
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

function getSetCookieValue(cookies: string, name: string): string | null {
  const match = cookies.match(new RegExp(`${name}=([^;]+)`));

  if (!match?.[1]) {
    return null;
  }

  return match[1];
}

function decodeCookieValue(value: string): string {
  try {
    return decodeURIComponent(value);
  } catch {
    return value;
  }
}

async function updateCookies(res: Response): Promise<BackendCookies | null> {
  const cookies = res.headers.get("set-cookie") || "";

  const XSRF_TOKEN = getSetCookieValue(cookies, XSRF_KEY);
  const SESSION = getSetCookieValue(cookies, BE_SESSION_KEY);

  if (!XSRF_TOKEN || !SESSION) {
    console.error("No match in set-cookies response"); // TODO
    // console.log("SET-COOKIES", cookies?.toString());
    // console.log(res.headers.getSetCookie());
    return null;
  }

  // console.log("Setting new cookies");
  // console.log("XSRF_TOKEN", XSRF_TOKEN);
  // console.log("SESSION", SESSION);

  // Set new cookies
  const cookiesStore = await Cookies();
  cookiesStore.set(XSRF_KEY, XSRF_TOKEN, DEFAULT_COOKIES_CONFIG);
  cookiesStore.set(FE_SESSION_KEY, SESSION, DEFAULT_COOKIES_CONFIG);
  // console.log("Cookies updated");

  return {
    session: SESSION,
    xsrfToken: XSRF_TOKEN,
  };
}

async function refreshCSRF() {
  console.log("Refreshing CSRF");
  const res = await fetch(`${baseUrl}/sanctum/csrf-cookie`, {
    method: "GET",
    credentials: "include",
    headers: {
      Accept: "application/json",
      Referer: process.env.FRONTEND_URL as string,
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
      Cookie: `${XSRF_KEY}=${XSRF_TOKEN}; ${BE_SESSION_KEY}=${SESSION}`,
      "X-XSRF-TOKEN": XSRF_HEADER,
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

  const result = await fetch(`${baseUrl}${uri}`, {
    method,
    credentials: "include",
    headers: {
      Accept: "application/json",
      Referer: process.env.FRONTEND_URL as string,
      Cookie: `${XSRF_KEY}=${XSRF_TOKEN}; ${BE_SESSION_KEY}=${SESSION}`,
      "X-XSRF-TOKEN": XSRF_HEADER,
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
      Cookie: `${XSRF_KEY}=${XSRF_TOKEN}; ${BE_SESSION_KEY}=${SESSION}`,
      "X-XSRF-TOKEN": XSRF_TOKEN,
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
