"use server";

import { redirect } from "next/navigation";
import { post } from "@/lib/api/admin";
import ApiResponse from "@/lib/api/response";
import { Cookie } from "@/lib/api/cookies";

export default async function submitLogin(
  _previousState: any,
  formData: FormData,
) {
  const rawFormData = {
    email: formData.get("email"),
    password: formData.get("password"),
  };

  let redirectTo = null;

  try {
    const result2 = await post("/login", rawFormData);

    if (result2.status === 422) {
      const data = await result2.json();

      return {
        status: 400,
        message: "Incorrect login credentials.",
        data: data.errors ?? {},
      } as ApiResponse;
    }

    if (result2.status != 200) {
      console.error(result2);
      throw new Error("Invalid server response.");
    }

    const response = await result2.json();
    const data = response?.data;

    if (!data.id || !data.name || !data.email) {
      throw new Error("Invalid user response.");
    }

    await Cookie.setUserData(data);
    redirectTo = "/lab";
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
