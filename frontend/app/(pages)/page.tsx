"use server";
import Site from "@/components/_core/layout/Site";
import IntroductionSection from "./intro/section/introduction";
import HowInteracts from "./intro/section/howInteract";
import InteroperabilitySection from "./intro/section/interoperability";
import StatsSection from "./intro/section/stats";
import AccessibilitySection from "./intro/section/accessibility";
import LabSection from "./intro/section/lab";
import SiteFooter from "@/components/_core/layout/SiteFooter";
import { SiteMenu } from "@/components/_core/layout/SiteMenu";

/**
 */
export default async function Intro() {
  return (
    <Site>
      <SiteMenu hideLogoOnTop />
      <IntroductionSection />
      <div className="h-12 w-full flex-1 bg-big-delimiter dark:bg-big-delimiter-dark" />
      <HowInteracts />
      <StatsSection />
      <AccessibilitySection />
      <InteroperabilitySection />
      <LabSection />
      <SiteFooter />
    </Site>
  );
}
