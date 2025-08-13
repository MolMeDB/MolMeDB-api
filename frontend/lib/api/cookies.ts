import { ResponseCookie } from "next/dist/compiled/@edge-runtime/cookies";
import jwt from "jsonwebtoken";
import { cookies } from "next/headers";

const SECRET_KEY = process.env.JWT_SECRET || "SECRET";
const USER_SESSION_KEY = process.env.COOKIES_BACKEND_SESSION_USER_KEY as string;

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

async function setUserData(data: object) {
  "use server";
  const cookiesStore = await cookies();
  cookiesStore.set(USER_SESSION_KEY, sign(data), DEFAULT_COOKIES_CONFIG);
}

async function getUserData() {
  "use server";
  const cookiesStore = await cookies();
  const cookieValue = cookiesStore.get(USER_SESSION_KEY)?.value as string;
  if (!cookieValue) {
    return null;
  }
  return unsign(cookieValue);
}

export const Cookie = {
  sign,
  unsign,
  setUserData,
  getUserData,
};
