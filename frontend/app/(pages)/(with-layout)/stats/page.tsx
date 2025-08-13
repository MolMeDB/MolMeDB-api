import SimpleSiteHeader from "@/components/_core/layout/SimpleSiteHeader";
import SiteFooter from "@/components/_core/layout/SiteFooter";
import { FaChartLine } from "react-icons/fa6";
import StatsContent from "./content";
import IStatsGlobal from "@/lib/api/admin/interfaces/Stats";
import { getViewData } from "@/lib/api/frontend";

export default async function StatsPage() {
  const stats: IStatsGlobal = (await getViewData(`/stats/`))?.data?.data;

  return (
    <>
      <SimpleSiteHeader>
        <div className="h-full w-full flex flex-col justify-end">
          <div className="flex flex-row items-center justify-start gap-6 lg:gap-8">
            <FaChartLine className="text-3xl xl:text-4xl" />
            <div className="flex flex-col justify-center gap-2 lg:gap-1">
              <h1 className="text-2xl md:text-3xl font-bold">Statistics</h1>
            </div>
          </div>
        </div>
      </SimpleSiteHeader>
      {/* <SiteContent> */}
      {stats ? (
        <div className="min-h-screen flex flex-col gap-16 pb-16">
          <StatsContent stats={stats} />
        </div>
      ) : null}
      {/* </SiteContent> */}
      <SiteFooter />
    </>
  );
}
