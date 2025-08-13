import SimpleSiteHeader from "@/components/_core/layout/SimpleSiteHeader";
import SiteFooter from "@/components/_core/layout/SiteFooter";
import SiteContent from "@/components/_core/layout/SiteContent";
import { MdBook, MdBookOnline, MdOutlineComputer } from "react-icons/md";
import { FaBookOpen } from "react-icons/fa6";
import { GiBookshelf } from "react-icons/gi";
import Client from "./client";

export default async function LabPage() {
  return (
    <>
      <SimpleSiteHeader>
        <div className="h-full w-full flex flex-col justify-end">
          <div className="flex flex-row items-end justify-start gap-4">
            <GiBookshelf size={40} className="" />
            <div className="flex flex-col justify-center gap-2">
              <h1 className="text-3xl font-bold">Documentation</h1>
            </div>
          </div>
        </div>
      </SimpleSiteHeader>
      {/* <SiteContent> */}
      <div className="min-h-screen pb-16">
        {/* <StatsContent /> */}
        <SiteContent classNameChildren="!max-w-[1500px] h-full">
          <Client />
        </SiteContent>
      </div>
      {/* </SiteContent> */}
      <SiteFooter />
    </>
  );
}
