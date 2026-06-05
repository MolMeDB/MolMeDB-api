export function filenameFromContentDisposition(
  contentDisposition: string | null,
  fallback: string,
): string {
  const encodedMatch = contentDisposition?.match(/filename\*=UTF-8''([^;]+)/);
  if (encodedMatch?.[1]) {
    return decodeURIComponent(encodedMatch[1]);
  }

  const match = contentDisposition?.match(/filename="?([^"]+)"?/);

  return match?.[1] || fallback;
}

export async function downloadFile(url: string, fallbackFilename: string) {
  const response = await fetch(url);

  if (!response.ok) {
    throw new Error("File download failed.");
  }

  const blob = await response.blob();
  const objectUrl = URL.createObjectURL(blob);
  const link = document.createElement("a");

  link.href = objectUrl;
  link.download = filenameFromContentDisposition(
    response.headers.get("content-disposition"),
    fallbackFilename,
  );
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(objectUrl);
}
