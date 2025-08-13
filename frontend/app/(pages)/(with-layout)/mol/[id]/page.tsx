import SimpleSiteHeader from "@/components/_core/layout/SimpleSiteHeader";
import SiteContent from "@/components/_core/layout/SiteContent";
import SiteFooter from "@/components/_core/layout/SiteFooter";
import { SiMoleculer } from "react-icons/si";
import CompoundBasicProperties from "./section/basicProperties";
import Compound2D3DStructure from "./section/structure";
import CompoundIdentifiers from "./section/identifiers";
import CompoundSimilarEntries from "./section/similarEntries";
import CompoundActiveInteractions from "./section/interactionActive";
import CompoundPassiveInteractions from "./section/interactionPassive";
import IStructure from "@/lib/api/admin/interfaces/Structure";
import { getViewData } from "@/lib/api/frontend";

export default async function CompoundDetailPage(props: {
  params: Promise<{ id: string }>;
}) {
  const id = (await props.params).id;
  const compound: IStructure = (await getViewData(`/structure/${id}`))?.data
    ?.data;
  return (
    <>
      <SimpleSiteHeader>
        <div className="h-full w-full flex flex-col justify-end">
          <div className="flex flex-row items-center justify-start gap-6 lg:gap-8">
            <SiMoleculer className="text-3xl xl:text-4xl" />
            <div className="flex flex-col justify-center gap-2 lg:gap-1">
              <h1 className="text-2xl md:text-3xl font-bold">
                {compound?.name ?? id}
              </h1>
              {compound.identifier && <h2 className="text-lg">{id}</h2>}
            </div>
          </div>
        </div>
      </SimpleSiteHeader>
      <SiteContent>
        <div className="min-h-screen flex flex-col gap-16 pb-16">
          <CompoundBasicProperties compound={compound} />
          <Compound2D3DStructure compound={compound} />
          <CompoundIdentifiers compound={compound} />
          <CompoundSimilarEntries compound={compound} />
          <CompoundPassiveInteractions compound={compound} />
          <CompoundActiveInteractions compound={compound} />
        </div>
      </SiteContent>
      <SiteFooter />
    </>
  );
}
