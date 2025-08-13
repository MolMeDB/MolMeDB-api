"use client";
import Link from "next/link";
import Image from "next/image";

export default function SiteLogoLink(props: {
  isScrolled?: boolean;
  isLink?: boolean;
}) {
  return (
    <Link href={props.isLink ? "/" : "#"} className="ml-8 xl:ml-0 block w-fit">
      <Image
        alt="MolMeDB"
        src={`${
          props.isScrolled
            ? "/assets/layout/logo/molmedb-dark.svg"
            : "/assets/layout/logo/molmedb-white.svg"
        }`}
        priority={true}
        width={250}
        height={100}
        className="w-36 block dark:hidden -mb-2"
      />
      <Image
        alt="MolMeDB"
        src={`${"/assets/layout/logo/molmedb-white.svg"}`}
        priority={true}
        width={250}
        height={100}
        className="w-36 hidden dark:block"
      />
    </Link>
  );
}
