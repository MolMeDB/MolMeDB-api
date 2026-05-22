import { SiteMenu } from "@/components/_core/layout/SiteMenu";
import { Metadata } from "next";
import Image from "next/image";
import SiteFooter from "@/components/_core/layout/SiteFooter";
import SimpleSiteHeader from "@/components/_core/layout/SimpleSiteHeader";
import { UserSession } from "@/lib/api/admin/interfaces/User";
import Link from "next/link";
import ResetPasswordForm from "./(components)/ResetPasswordForm";

export const metadata: Metadata = {
  title: "Reset password | MolMeDB",
  description: "",
};

export default async function ResetPasswordPage() {
  return (
    <>
      <SiteMenu isLogoClickable />
      <SimpleSiteHeader>
        <></>
      </SimpleSiteHeader>
      <Main />
      <SiteFooter />
    </>
  );
}

function Main(props: { user?: UserSession }) {
  return (
    <main className="p-8 lg:p-20 mt-12 max-h-screen max-w-screen-lg w-full mx-auto flex-1 flex flex-col justify-center items-center">
      <div className="max-h-[800px] max-w-[1200px] shadow-2xl rounded-xl bg-white dark:bg-background-dark flex flex-col lg:flex-row">
        <div className="h-full lg:w-full p-8 lg:p-4 lg:py-16 rounded-xl flex flex-col justify-between items-center gap-4">
          <>
            <Image
              src="/assets/layout/logo/molmedb-dark.svg"
              alt="Pokusnice"
              priority
              width={0}
              height={0}
              sizes="100vw"
              className="w-5/6 sm:w-4/6 md:w-1/3 dark:hidden"
            />
            <Image
              src="/assets/layout/logo/molmedb-white.svg"
              alt="Pokusnice"
              priority
              width={0}
              height={0}
              sizes="100vw"
              className="w-5/6 sm:w-4/6 md:w-1/2 hidden dark:block"
            />
            <h2 className="font-semibold text-3xl">Forgot password?</h2>
            <div className="flex flex-col justify-center items-center text-sm text-foreground/70">
              <Link href="/login" className="text-blue-500 hover:underline">
                Back to login
              </Link>
            </div>
            <div className="flex flex-col items-center gap-2 w-full h-1/2 lg:h-[60%]">
              <ResetPasswordForm />
            </div>
          </>
        </div>
      </div>
    </main>
  );
}
