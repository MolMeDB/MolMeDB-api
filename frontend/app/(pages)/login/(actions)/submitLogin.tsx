"use server";

import { redirect } from "next/navigation";
import { post } from "@/lib/api/admin";
import ApiResponse from "@/lib/api/response";
import { Cookie } from "@/lib/api/cookies";

export default async function submitLogin(
  _previousState: any,
  formData: FormData
) {
  const rawFormData = {
    email: formData.get("email"),
    password: formData.get("password"),
    remember: formData.get("remember") === "on",
  };

  let redirectTo = formData.get("redirectTo")?.toString() ?? false;

  try {
    const result2 = await post("/login", rawFormData);

    if (result2.status === 422) {
      const data = await result2.json();
      const isUnverified = Boolean(data.errors?.email_verification);

      return {
        status: 400,
        message: isUnverified
          ? "Please verify your email address before signing in."
          : "Incorrect login credentials.",
        data: {
          ...(data.errors ?? {}),
          email_verification: isUnverified,
        },
      } as ApiResponse;
    }

    if (result2.status != 200) {
      console.error(result2);
      throw new Error("Invalid server response.");
    }

    const response = await result2.json();
    const data = response?.data;
    const authMeta = response?.meta;

    if (!data.id || !data.name || !data.email) {
      throw new Error("Invalid user response.");
    }

    await Cookie.setUserData(data, {
      expiresAt:
        typeof authMeta?.session_expires_at === "string"
          ? authMeta.session_expires_at
          : undefined,
      remember: Boolean(authMeta?.remember),
    });
    redirectTo = redirectTo ? redirectTo : "/lab";
  } catch (error) {
    console.error(error);
    return {
      status: 500,
      message: "Invalid server response. Please, try again.",
      data: {},
    } as ApiResponse;
  }

  if (redirectTo) redirect(redirectTo);
}
