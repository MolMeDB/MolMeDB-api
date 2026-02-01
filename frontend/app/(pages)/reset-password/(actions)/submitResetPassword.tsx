"use server";

import { redirect } from "next/navigation";
import { get, post } from "@/lib/api/admin";
import ApiResponse from "@/lib/api/response";
import { Cookie } from "@/lib/api/cookies";

export default async function submitResetPassword(
  _previousState: any,
  formData: FormData,
) {
  const rawFormData = {
    email: formData.get("email"),
  };

  let redirectTo = null;

  try {
    const result2 = await post("/forgot-password", rawFormData);

    if (result2.status == 200) {
      return {
        status: 200,
        message:
          "Your password reset link has been sent to your email address.",
        data: {},
      } as ApiResponse;
    }

    if (result2.status == 422) {
      console.error(result2);
      return {
        status: 422,
        message:
          "Cannot send reset password link. Please, check if the email is correct or try again later.",
        data: {},
      } as ApiResponse;
    }

    var data = await result2.json();

    console.log(result2, data);

    // Check if the login form return errors
    if (data.errors) {
      console.error(data);
      return {
        status: 400,
        message:
          data.message ??
          "Registration error, please check your input and try again.",
        data: data.errors,
      } as ApiResponse;
    }

    data = data?.data;

    // Check if returned data has correct format
    if (!data.id || !data.name || !data.email) {
      // Logout user
      redirectTo = "/api/logout";
    }

    // Save user information
    if (!redirectTo) {
      await Cookie.setUserData(data);
      redirectTo = "/lab";
    }
  } catch (error) {
    console.error(error);
    return {
      status: 500,
      message: "Server error, please try again later.",
      data: {},
    } as ApiResponse;
  }

  if (redirectTo) redirect(redirectTo);
}
