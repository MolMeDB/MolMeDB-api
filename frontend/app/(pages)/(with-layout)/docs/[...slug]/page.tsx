import SimpleSiteHeader from "@/components/_core/layout/SimpleSiteHeader";
import SiteFooter from "@/components/_core/layout/SiteFooter";
import SiteContent from "@/components/_core/layout/SiteContent";
import { GiBookshelf } from "react-icons/gi";
import Client from "../client";

export default async function DocsArticlePage(props: {
  params: Promise<{ slug: string[] }>;
}) {
  const slug = (await props.params).slug ?? [];

  return (
    <>
      <SimpleSiteHeader>
        <div className="h-full w-full flex flex-col justify-end">
          <div className="flex flex-row items-end justify-start gap-4">
            <GiBookshelf size={40} />
            <div className="flex flex-col justify-center gap-2">
              <h1 className="text-3xl font-bold">Documentation</h1>
            </div>
          </div>
        </div>
      </SimpleSiteHeader>
      <div className="min-h-screen pb-16 w-full bg-default-100">
        <main
          className={`isolate flex-1 w-full h-full mx-auto p-6 sm:p-8 md:p-16 lg:p-16 bg-transparent`}
        >
          <div className={`w-full h-full mx-auto`}>
            <Client initialSlug={slug} />
          </div>
        </main>
      </div>
      <SiteFooter />
    </>
  );
}
