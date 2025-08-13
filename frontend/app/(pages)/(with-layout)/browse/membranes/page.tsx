import SimpleSiteHeader from "@/components/_core/layout/SimpleSiteHeader";
import SiteContent from "@/components/_core/layout/SiteContent";
import SiteFooter from "@/components/_core/layout/SiteFooter";
import { FaMagnifyingGlass } from "react-icons/fa6";
import ICategory from "@/lib/api/admin/interfaces/Category";
import { getViewData } from "@/lib/api/frontend";
import SectionWrapper from "./section/Wrapper";
import SafeRenderer from "@/components/errors/safeRender";

export default async function BrowseMembranesPage() {
  const categories: ICategory[] = (await getViewData(`/membrane/categories`))
    ?.data?.data;

  return (
    <>
      <SimpleSiteHeader>
        <div className="h-full w-full flex flex-col justify-end">
          <div className="flex flex-row items-center justify-start gap-6 lg:gap-8">
            <FaMagnifyingGlass className="text-3xl xl:text-4xl" />
            <div className="flex flex-col justify-center lg:gap-1">
              <h1 className="text-2xl md:text-3xl font-bold">Membranes</h1>
              <h2 className="text-lg">Browser</h2>
            </div>
          </div>
        </div>
      </SimpleSiteHeader>
      <SiteContent>
        <SafeRenderer>
          <div className="min-h-screen flex flex-col gap-8 pb-16">
            <SectionWrapper categories={categories} />
          </div>
        </SafeRenderer>
      </SiteContent>
      <SiteFooter />
    </>
  );
}
