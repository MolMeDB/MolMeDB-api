import SimpleSiteHeader from "@/components/_core/layout/SimpleSiteHeader";
import SiteContent from "@/components/_core/layout/SiteContent";
import SiteFooter from "@/components/_core/layout/SiteFooter";
import { FaMagnifyingGlass } from "react-icons/fa6";
import SectionWrapper from "./section/wrapper";

export default async function BrowseDatasetsPage() {
  // const publications: FilteredResponse<IPublication> = (
  //   await getViewData(`/publication`)
  // )?.data;

  return (
    <>
      <SimpleSiteHeader>
        <div className="h-full w-full flex flex-col justify-end">
          <div className="flex flex-row items-center justify-start gap-6 lg:gap-8">
            <FaMagnifyingGlass className="text-3xl xl:text-4xl" />
            <div className="flex flex-col justify-center lg:gap-1">
              <h1 className="text-2xl md:text-3xl font-bold">Datasets</h1>
              <h2 className="text-lg">Browser</h2>
            </div>
          </div>
        </div>
      </SimpleSiteHeader>
      <SiteContent>
        <div className="min-h-screen flex flex-col gap-8 pb-16">
          <SectionWrapper />
        </div>
      </SiteContent>
      <SiteFooter />
    </>
  );
}
