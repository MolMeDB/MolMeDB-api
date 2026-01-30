"use client";

import {
  Dropdown,
  DropdownItem,
  DropdownMenu,
  DropdownTrigger,
} from "@heroui/react";
import Link from "next/link";
import { UserDetailButton } from "./SiteMenu";
import { useEffect, useState } from "react";
import { UserSession } from "@/lib/api/admin/interfaces/User";
import { IoMdLogOut } from "react-icons/io";

export default function SiteMenuUser(props: { user?: UserSession }) {
  const [mounted, setMounted] = useState(false);

  useEffect(() => setMounted(true), []);

  if (!mounted) {
    return (
      <div className="h-10 w-10 rounded-full bg-default-200 animate-pulse" />
    );
  }

  return props.user?.id ? (
    <Dropdown placement="bottom-end" className="">
      <DropdownTrigger className="cursor-pointer">
        <div>
          <UserDetailButton user={props.user} />
        </div>
      </DropdownTrigger>
      <DropdownMenu
        disabledKeys={["profile"]}
        aria-label="Profile Actions"
        variant="flat"
      >
        <DropdownItem key="profile" className="h-14 gap-1">
          <p className="font-semibold">Logged in as</p>
          <p className="font-semibold">{props.user.email}</p>
        </DropdownItem>
        {/* <DropdownItem href="/account" key="settings">
          Nastavení účtu
        </DropdownItem> */}
        <DropdownItem
          startContent={<IoMdLogOut />}
          key="logout"
          color="danger"
          href="/api/logout"
        >
          Log out
        </DropdownItem>
      </DropdownMenu>
    </Dropdown>
  ) : (
    <Link href="/login" prefetch={false}>
      {/* <UserDetailButton /> */}
    </Link>
  );
}
