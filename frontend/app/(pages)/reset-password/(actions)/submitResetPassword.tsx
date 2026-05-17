"use server";

import { post } from "@/lib/api/admin";
import ApiResponse from "@/lib/api/response";

async function responseJson(response: Response): Promise<any> {
  try {
    return await response.json();
  } catch {
    return null;
  }
}

export default async function submitResetPassword(
  _previousState: any,
  formData: FormData,
) {
  const rawFormData = {
    email: formData.get("email"),
    turnstile_token: formData.get("turnstile_token"),
  };

  try {
    const result2 = await post("/forgot-password", rawFormData);

    const data = await responseJson(result2);

    if (result2.status == 200) {
      return {
        status: 200,
        message:
          data?.status ??
          "Your password reset link has been sent to your email address.",
        data: {},
      } as ApiResponse;
    }

    if (result2.status == 422) {
      return {
        status: 422,
        message:
          data?.message ??
          "Cannot send reset password link. Please, check if the email is correct or try again later.",
        data: data?.errors ?? {},
      } as ApiResponse;
    }

    return {
      status: result2.status,
      message:
        data?.message ??
        "Cannot send reset password link. Please, try again later.",
      data: data?.errors ?? {},
    } as ApiResponse;
  } catch (error) {
    console.error(error);
    return {
      status: 500,
      message: "Server error, please try again later.",
      data: {},
    } as ApiResponse;
  }

  return {
    status: 500,
    message: "Server error, please try again later.",
    data: {},
  } as ApiResponse;
}
