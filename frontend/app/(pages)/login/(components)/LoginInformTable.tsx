"use client";
import { UserSession } from "@/lib/api/admin/interfaces/User";
import { Avatar, AvatarIcon, Button } from "@heroui/react";
import Link from "next/link";
import { IoMdHome, IoMdLogOut } from "react-icons/io";

export default function LoginInformTable(props: { user?: UserSession }) {
  return (
    <div className="flex flex-col gap-4">
      <Button
        as={Link}
        href="/"
        variant="bordered"
        startContent={<AvatarIcon />}
        size="lg"
        className="cursor-pointer"
      >
        Continue as
        <label className="text-success cursor-pointer">
          {props.user?.email}
        </label>
      </Button>
      <Button
        as={Link}
        className="w-full"
        href="/api/logout"
        color="danger"
        startContent={<IoMdLogOut size={25} />}
        size="md"
      >
        Logout
      </Button>
    </div>
  );
}
