"use server";

import { redirect } from "next/navigation";
import { get, post } from "@/lib/api/admin";
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

  // Check if user is already logged in
  try {
    const result = await get("/api/user");

    if (result.status == 200) {
      // Update user data
      await Cookie.setUserData(await result.json());
      // Redirect to default page
      redirectTo = "/lab";
    }
  } catch {
    // Fetch error?
    return {
      status: 500,
      message: "Neplatná odpověď serveru. Zkuste to znovu.",
      data: {},
    } as ApiResponse;
  }

  if (redirectTo) {
    redirect(redirectTo);
  }

  try {
    const result2 = await post("/login", rawFormData);

    if (result2.status != 200 && result2.status != 422) {
      console.error(result2);
      throw new Error("Invalid response.");
    }

    var data = await result2.json();

    // Check if the login form return errors
    if (data.errors) {
      console.error(data);
      return {
        status: 400,
        message: "Incorrect login credentials.",
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
      message: "Invalid server response. Please, try again.",
      data: {},
    } as ApiResponse;
  }

  if (redirectTo) redirect(redirectTo);
}
