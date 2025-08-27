import Site from "@/components/_core/layout/Site";
import SiteContent from "@/components/_core/layout/SiteContent";
import { SiteMenu } from "@/components/_core/layout/SiteMenu";

export default function RootLayout(
  props: Readonly<{
    children: React.ReactNode;
  }>
) {
  return (
    <Site>
      <SiteMenu isLogoClickable />
      {props.children}
    </Site>
  );
}
