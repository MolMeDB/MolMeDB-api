"use server";

import { redirect } from "next/navigation";
import { get, post } from "@/lib/api/admin";
import ApiResponse from "@/lib/api/response";
import { Cookie } from "@/lib/api/cookies";

export default async function submitSignUp(
  _previousState: any,
  formData: FormData,
) {
  const rawFormData = {
    email: formData.get("email"),
    password: formData.get("password"),
    password_confirmation: formData.get("password_confirmation"),
    affiliation: formData.get("affiliation"),
    name: formData.get("name"),
  };

  let redirectTo = null;

  if (rawFormData.password !== rawFormData.password_confirmation) {
    // Sleep 2sec
    await new Promise((resolve) => setTimeout(resolve, 2000));
    return {
      status: 400,
      message: "Passwords do not match.",
      data: {},
    } as ApiResponse;
  }

  try {
    const result2 = await post("/register", rawFormData);

    if (result2.status == 201 || result2.status == 204) {
      return {
        status: 201,
        message:
          "Registration successful. Please, check your email to verify your account.",
        data: {},
      } as ApiResponse;
    }

    if (result2.status != 200 && result2.status != 422) {
      console.error(result2);
      throw new Error("Invalid server response. Please, try again.");
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
      message: "Neplatná odpověď serveru. Zkuste to znovu.",
      data: {},
    } as ApiResponse;
  }

  if (redirectTo) redirect(redirectTo);
}
