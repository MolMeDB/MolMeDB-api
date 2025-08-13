import SimpleSiteHeader from "@/components/_core/layout/SimpleSiteHeader";
import SiteFooter from "@/components/_core/layout/SiteFooter";
import SiteContent from "@/components/_core/layout/SiteContent";
import { MdOutlineComputer } from "react-icons/md";
import Image from "next/image";
import SectionComputationButtons from "./section/computationButton";
import SectionUpload from "./section/upload";

export default async function LabPage() {
  return (
    <>
      <SimpleSiteHeader>
        <div className="h-full w-full flex flex-col justify-end">
          <div className="flex flex-row items-center justify-start gap-6 lg:gap-8">
            <MdOutlineComputer className="text-3xl xl:text-4xl" />
            <div className="flex flex-col justify-center lg:gap-1">
              <h1 className="text-2xl md:text-3xl font-bold">Laboratory</h1>
            </div>
          </div>
        </div>
      </SimpleSiteHeader>
      {/* <SiteContent> */}
      <div className="min-h-screen pb-16">
        {/* <StatsContent /> */}
        <SiteContent classNameChildren="flex flex-col gap-16 ">
          <SectionComputationButtons />
          <div className="h-1 w-full bg-gradient-to-r from-transparent via-zinc-100 to-transparent my-8" />
          <SectionUpload />
        </SiteContent>
      </div>
      {/* </SiteContent> */}
      <SiteFooter />
    </>
  );
}
