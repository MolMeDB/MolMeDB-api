import { SiteMenu } from "@/components/_core/layout/SiteMenu";
import { Metadata } from "next";
import Image from "next/image";
import SiteFooter from "@/components/_core/layout/SiteFooter";
import SimpleSiteHeader from "@/components/_core/layout/SimpleSiteHeader";
import { UserSession } from "@/lib/api/admin/interfaces/User";
import { Cookie } from "@/lib/api/cookies";
import LoginInformTable from "./(components)/LoginInformTable";
import SignUpForm from "./(components)/SignUpForm";

export const metadata: Metadata = {
  title: "Create new account | MolMeDB",
  description: "",
};

export default async function LoginPage() {
  const user: UserSession | undefined =
    (await Cookie.getUserData()) as UserSession;

  return (
    <>
      <SiteMenu isLogoClickable />
      <SimpleSiteHeader>
        <></>
      </SimpleSiteHeader>
      <Main user={user} />
      <SiteFooter />
    </>
  );
}

function Main(props: { user?: UserSession }) {
  return (
    <main className="p-8 lg:p-20 mt-12 max-h-screen max-w-screen-lg w-full mx-auto flex-1 flex flex-col justify-center items-center">
      <div className="max-h-[800px] max-w-[1200px] shadow-2xl rounded-xl bg-white dark:bg-background-dark flex flex-col lg:flex-row">
        <div className="h-full lg:w-full p-8 lg:p-4 lg:py-16 rounded-xl flex flex-col justify-between items-center gap-4">
          {props.user?.id ? (
            <LoginInformTable user={props.user} />
          ) : (
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
              <h2 className="font-semibold text-3xl">Create new account</h2>
              <div className="flex flex-col justify-center items-center text-sm text-foreground/70">
                <p className="text-center">
                  Enter your details below to create your account <br /> and get
                  started.
                </p>
              </div>
              <div className="flex flex-col items-center gap-2 w-full h-1/2 lg:h-[60%]">
                <SignUpForm />
              </div>
            </>
          )}
        </div>
      </div>
    </main>
  );
}
