"use server";

import { postForm } from "@/lib/api/admin";
import ApiResponse from "@/lib/api/response";

export default async function submitUploadDataset(
  _previousState: any,
  formData: FormData,
) {
  const payload = new FormData();
  const keys = [
    "dataset_type",
    "dataset_name",
    "comment",
    "membrane_id",
    "method_id",
    "publication_pmid",
    "publication_lookup_provider",
    "publication_lookup_source",
    "turnstile_token",
  ];

  keys.forEach((key) => {
    const value = formData.get(key);

    if (typeof value === "string" && value.trim() !== "") {
      payload.append(key, value);
    }
  });

  const file = formData.get("file");
  if (!(file instanceof File) || file.size === 0) {
    return {
      status: 422,
      message: "Please select a file for upload.",
      data: {},
    } as ApiResponse;
  }

  payload.append("file", file);

  try {
    const response = await postForm("/api/lab/upload", payload);

    if (response.status === 201) {
      const json = await response.json();

      return {
        status: 201,
        message:
          json?.message ??
          "Upload request has been accepted and queued for processing.",
        data: json?.data ?? {},
      } as ApiResponse;
    }

    const json = await response.json();

    return {
      status: response.status,
      message:
        json?.message ??
        "Upload request failed. Please check the form and try again.",
      data: json?.errors ?? json?.data ?? {},
    } as ApiResponse;
  } catch (error) {
    console.error(error);

    return {
      status: 500,
      message: "Unexpected error occurred while uploading data.",
      data: {},
    } as ApiResponse;
  }
}
