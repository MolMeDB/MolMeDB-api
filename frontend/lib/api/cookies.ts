import { ResponseCookie } from "next/dist/compiled/@edge-runtime/cookies";
import jwt from "jsonwebtoken";
import { cookies } from "next/headers";
import { UserSession } from "./admin/interfaces/User";

const SECRET_KEY = process.env.JWT_SECRET || "SECRET";
const USER_SESSION_KEY = process.env
  .COOKIES_FRONTEND_SESSION_USER_KEY as string;
const DEFAULT_COOKIE_MAX_AGE = 60 * 60 * 24;

export const DEFAULT_COOKIES_CONFIG = {
  httpOnly: true, // Not accesible from javascript if true
  secure: process.env.NODE_ENV === "production", // Pouze přes HTTPS
  sameSite: "strict",
  path: "/",
  maxAge: DEFAULT_COOKIE_MAX_AGE, // 1 den
} as ResponseCookie;

type UserCookieOptions = {
  expiresAt?: string;
  remember?: boolean;
};

function maxAgeFromExpiresAt(expiresAt?: string): number | null {
  if (!expiresAt) {
    return null;
  }

  const expiresAtTime = Date.parse(expiresAt);
  if (!Number.isFinite(expiresAtTime)) {
    return null;
  }

  return Math.max(1, Math.floor((expiresAtTime - Date.now()) / 1000));
}

function userCookieMaxAge(options: UserCookieOptions = {}): number {
  return (
    maxAgeFromExpiresAt(options.expiresAt) ??
    (options.remember ? 60 * 60 * 24 * 365 * 5 : DEFAULT_COOKIE_MAX_AGE)
  );
}

function sign(data: object, maxAge = DEFAULT_COOKIE_MAX_AGE) {
  return jwt.sign(data, SECRET_KEY, { expiresIn: maxAge });
}

function unsign(token: string) {
  return jwt.verify(token, SECRET_KEY);
}

function normalizeUserData(data: unknown): UserSession | null {
  if (!data || typeof data !== "object") {
    return null;
  }

  const payload = data as Record<string, unknown>;
  const source =
    payload.user && typeof payload.user === "object"
      ? (payload.user as Record<string, unknown>)
      : payload;

  const rawId = source.id;
  const id =
    typeof rawId === "number"
      ? rawId
      : typeof rawId === "string"
        ? Number(rawId)
        : undefined;

  if (!id || !Number.isFinite(id)) {
    return null;
  }

  return {
    id,
    first_name:
      typeof source.first_name === "string" ? source.first_name : undefined,
    last_name:
      typeof source.last_name === "string" ? source.last_name : undefined,
    name: typeof source.name === "string" ? source.name : undefined,
    email: typeof source.email === "string" ? source.email : undefined,
  };
}

async function setUserData(data: object, options: UserCookieOptions = {}) {
  "use server";
  const user = normalizeUserData(data);

  if (!user || !USER_SESSION_KEY) {
    return;
  }

  const cookiesStore = await cookies();
  const maxAge = userCookieMaxAge(options);

  cookiesStore.set(USER_SESSION_KEY, sign(user, maxAge), {
    ...DEFAULT_COOKIES_CONFIG,
    maxAge,
  });
}

async function getUserData() {
  "use server";
  if (!USER_SESSION_KEY) {
    return null;
  }

  const cookiesStore = await cookies();
  const cookieValue = cookiesStore.get(USER_SESSION_KEY)?.value as string;
  if (!cookieValue) {
    return null;
  }

  try {
    return normalizeUserData(unsign(cookieValue));
  } catch {
    return null;
  }
}

export const Cookie = {
  sign,
  unsign,
  setUserData,
  getUserData,
};
