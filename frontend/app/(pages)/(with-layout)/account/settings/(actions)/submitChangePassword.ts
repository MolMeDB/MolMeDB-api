"use server";

import { post } from "@/lib/api/admin";
import ApiResponse from "@/lib/api/response";

export default async function submitChangePassword(
  _previousState: any,
  formData: FormData,
) {
  const payload = {
    current_password: formData.get("current_password"),
    password: formData.get("password"),
    password_confirmation: formData.get("password_confirmation"),
  };

  try {
    const response = await post("/api/user/password", payload, "PUT");

    if (response.status === 200) {
      return {
        status: 200,
        title: "Success",
        message: "Password was updated successfully.",
        data: {},
      } as ApiResponse;
    }

    const json = await response.json().catch(() => null);

    if (response.status === 422) {
      return {
        status: 400,
        title: "Validation error",
        message: json?.message ?? "Please check your input.",
        data: json?.errors ?? {},
      } as ApiResponse;
    }

    if (response.status === 401) {
      return {
        status: 401,
        title: "Unauthorized",
        message: "Your session expired. Please log in again.",
        data: {},
      } as ApiResponse;
    }

    return {
      status: response.status,
      title: "Error",
      message: json?.message ?? "Unable to update password.",
      data: json?.errors ?? {},
    } as ApiResponse;
  } catch (error) {
    console.error(error);

    return {
      status: 500,
      title: "Server error",
      message: "Server error. Please try again.",
      data: {},
    } as ApiResponse;
  }
}
