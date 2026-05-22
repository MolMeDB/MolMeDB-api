import SimpleSiteHeader from "@/components/_core/layout/SimpleSiteHeader";
import SiteFooter from "@/components/_core/layout/SiteFooter";
import SiteContent from "@/components/_core/layout/SiteContent";
import { MdOutlineComputer } from "react-icons/md";
import { UserSession } from "@/lib/api/admin/interfaces/User";
import { Cookie } from "@/lib/api/cookies";
import Link from "next/link";
import AddNewCalculationForm from "./section/newCalcForm";
import { redirect } from "next/navigation";

export default async function LabNewCalculationPage() {
  const user: UserSession | undefined =
    (await Cookie.getUserData()) as UserSession;

  const isLoggedIn = user && user.id ? true : false;

  if (!isLoggedIn) {
    return redirect("/lab");
  }

  return (
    <>
      <SimpleSiteHeader>
        <div className="h-full w-full flex flex-col justify-end">
          <div className="flex flex-row items-center justify-start gap-6 lg:gap-8">
            <MdOutlineComputer className="text-3xl xl:text-4xl" />
            <div className="flex flex-col justify-center lg:gap-1">
              <h1 className="text-2xl md:text-3xl font-bold">
                Laboratory - predictions
              </h1>
              <div className="flex flex-row gap-2 items-center">
                <Link href="/lab" className="text-sm underline">
                  Laboratory
                </Link>
                {" >> "}
                <div className="text-sm text-white/70">
                  Add new calculations
                </div>
              </div>
            </div>
          </div>
        </div>
      </SimpleSiteHeader>
      <div className="min-h-screen pb-16">
        <SiteContent classNameChildren="flex flex-col gap-16 min-h-screen">
          <AddNewCalculationForm />
        </SiteContent>
      </div>
      <SiteFooter />
    </>
  );
}
