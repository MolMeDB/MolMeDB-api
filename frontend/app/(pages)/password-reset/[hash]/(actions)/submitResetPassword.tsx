"use server";

import { post } from "@/lib/api/admin";
import ApiResponse from "@/lib/api/response";

export default async function submitResetPassword(
  _previousState: any,
  formData: FormData,
) {
  const rawFormData = {
    email: formData.get("email"),
    token: formData.get("hash"),
    password: formData.get("password"),
    password_confirmation: formData.get("password_confirmation"),
  };

  try {
    const result2 = await post("/reset-password", rawFormData);

    if (result2.status == 200) {
      return {
        status: 200,
        message:
          "Your password has been reset successfully. You can now log in with your new password.",
        data: {},
      } as ApiResponse;
    }
    var data = await result2.json();

    // Check if the login form return errors
    if (data.errors) {
      console.error(data);
      return {
        status: 400,
        message:
          data.message ??
          "Cannot reset password, please check your input and try again.",
        data: data.errors,
      } as ApiResponse;
    }
  } catch (error) {
    console.error(error);
  }

  return {
    status: 500,
    message: "Server error, please try again later.",
    data: {},
  } as ApiResponse;
}
