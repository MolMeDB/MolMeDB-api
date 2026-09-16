"use server";

import { post } from "@/lib/api/admin";
import ApiResponse from "@/lib/api/response";

export default async function resendVerification(
  _previousState: any,
  formData: FormData,
) {
  const email = formData.get("email");

  if (!email) {
    return {
      status: 400,
      message: "Email is required.",
      data: {},
    } as ApiResponse;
  }

  try {
    const result = await post("/email/verification-notification/guest", {
      email,
    });

    if (result.status === 429) {
      return {
        status: 429,
        message:
          "Please wait a minute before requesting another verification email.",
        data: {},
      } as ApiResponse;
    }

    if (result.status !== 200) {
      console.error(result);
      return {
        status: 500,
        message: "Could not send verification email. Please try again later.",
        data: {},
      } as ApiResponse;
    }

    return {
      status: 200,
      message: "Verification email sent. Please check your inbox.",
      data: {},
    } as ApiResponse;
  } catch (error) {
    console.error(error);
    return {
      status: 500,
      message: "Invalid server response. Please, try again.",
      data: {},
    } as ApiResponse;
  }
}
