import Site from "@/components/_core/layout/Site";
import { SiteMenu } from "@/components/_core/layout/SiteMenu";
import { UserSession } from "@/lib/api/admin/interfaces/User";
import { Cookie } from "@/lib/api/cookies";

export default async function RootLayout(
  props: Readonly<{
    children: React.ReactNode;
  }>,
) {
  const user: UserSession | undefined =
    (await Cookie.getUserData()) as UserSession;

  return (
    <Site>
      <SiteMenu user={user} isLogoClickable />
      {props.children}
    </Site>
  );
}
