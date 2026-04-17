import { redirect } from "next/navigation";

type PageProps = {
  params: Promise<{
    id: string;
    hash: string;
  }>;
  searchParams: Promise<Record<string, string | string[] | undefined>>;
};

export default async function VerifyEmailPage({
  params,
  searchParams,
}: PageProps) {
  const { id, hash } = await params;
  const query = new URLSearchParams();

  Object.entries(await searchParams).forEach(([key, value]) => {
    if (typeof value === "string") {
      query.set(key, value);
    }
  });

  const backendUrl = process.env.NEXT_BACKEND_URL;

  if (!backendUrl) {
    redirect("/login?verified=0");
  }

  const response = await fetch(
    `${backendUrl}/verify-email/${id}/${hash}?${query.toString()}`,
    {
      method: "GET",
      redirect: "manual",
      cache: "no-store",
      headers: {
        Accept: "text/html,application/xhtml+xml,application/json",
      },
    },
  );

  if (response.status >= 300 && response.status < 400) {
    const location = response.headers.get("location");

    if (location) {
      redirect(location);
    }
  }

  redirect("/login?verified=0");
}
