import { ResponseCookie } from "next/dist/compiled/@edge-runtime/cookies";
import jwt from "jsonwebtoken";
import { cookies } from "next/headers";
import { UserSession } from "./admin/interfaces/User";

const SECRET_KEY = process.env.JWT_SECRET || "SECRET";
const USER_SESSION_KEY = process.env
  .COOKIES_FRONTEND_SESSION_USER_KEY as string;

export const DEFAULT_COOKIES_CONFIG = {
  httpOnly: true, // Not accesible from javascript if true
  secure: process.env.NODE_ENV === "production", // Pouze přes HTTPS
  sameSite: "strict",
  path: "/",
  maxAge: 60 * 60 * 24, // 1 den
} as ResponseCookie;

function sign(data: object) {
  return jwt.sign(data, SECRET_KEY, { expiresIn: "1d" });
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

async function setUserData(data: object) {
  "use server";
  const user = normalizeUserData(data);

  if (!user || !USER_SESSION_KEY) {
    return;
  }

  const cookiesStore = await cookies();
  cookiesStore.set(USER_SESSION_KEY, sign(user), DEFAULT_COOKIES_CONFIG);
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
