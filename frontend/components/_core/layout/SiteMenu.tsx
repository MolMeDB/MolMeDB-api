"use client";

import {
  Button,
  Dropdown,
  DropdownItem,
  DropdownMenu,
  DropdownSection,
  DropdownTrigger,
  Kbd,
  Navbar,
  NavbarBrand,
  NavbarContent,
  NavbarItem,
  NavbarMenu,
  NavbarMenuItem,
  NavbarMenuToggle,
} from "@heroui/react";
import SiteLogoLink from "./SiteLogoLink";
import Link from "next/link";
import { useEffect, useState } from "react";
import { FaMagnifyingGlass } from "react-icons/fa6";
import SearchEngine from "../providers/searchEngine";
import { MdOutlineLayers, MdPeopleOutline } from "react-icons/md";
import { BsBoxes } from "react-icons/bs";
// import { UserSession } from "@/lib/api/admin/interfaces/user";

export function SiteMenu(props: {
  // user?: UserSession;
  hideLogoOnTop?: boolean;
  hideMenu?: boolean;
  isLogoClickable?: boolean;
}) {
  const [isScrolled, setIsScrolled] = useState(false);
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [isVisibleSE, setIsVisibleSE] = useState(false);

  useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 50);
    };
    window.addEventListener("scroll", handleScroll);
    return () => window.removeEventListener("scroll", handleScroll);
  }, []);

  return (
    <Navbar
      classNames={{
        base: `h-16 -mb-16 transition-all duration-500 ease-in-out text-white/80 ${
          isScrolled || isMenuOpen
            ? " text-foreground/80 dark:bg-background-menu-dark/90"
            : "!bg-transparent backdrop-blur-none backdrop-saturate-100"
        }`,
        brand: `${
          isScrolled || isMenuOpen ? "" : ""
        } flex justify-between lg:justify-start gap-8`,
        item: `${isScrolled || isMenuOpen ? "" : ""} font-bold`,
        wrapper: "max-w-screen xl:max-w-[1400px]",
        toggle: "lg:hidden h-10",
        toggleIcon: "h-full",
      }}
      isMenuOpen={isMenuOpen}
      onMenuOpenChange={setIsMenuOpen}
      disableAnimation
    >
      <NavbarBrand>
        {!props.hideMenu && (
          <NavbarMenuToggle
            aria-label={isMenuOpen ? "Close menu" : "Open menu"}
          />
        )}
        <h1 className="sr-only">Pokusnice</h1>
        {(!props.hideLogoOnTop || isScrolled || isMenuOpen) && (
          <SiteLogoLink
            isScrolled={isScrolled || isMenuOpen}
            isLink={props.isLogoClickable}
          />
        )}
      </NavbarBrand>
      {/* <NavbarContent className="hidden lg:flex gap-10">
        
      </NavbarContent> */}
      <NavbarContent
        as="div"
        className="hidden lg:flex items-center gap-10"
        justify="end"
      >
        <NavbarItem>
          <Dropdown>
            <DropdownTrigger className="cursor-pointer">
              <label
                className="tap-highlight-transparent font-semibold
        outline-none data-[focus-visible=true]:z-10 data-[focus-visible=true]:outline-2 
        data-[focus-visible=true]:outline-focus data-[focus-visible=true]:outline-offset-2 
        text-medium no-underline hover:underline hover:opacity-80 active:opacity-disabled 
        transition-opacity data-[active=true]:text-primary data-[active=true]:font-semibold
        w-full h-12"
              >
                Browse
              </label>
            </DropdownTrigger>
            <DropdownMenu>
              <DropdownItem
                key={"membranes"}
                as={Link}
                href="/browse/membranes"
                color="secondary"
                // textValue="Membranes"
                startContent={<MdOutlineLayers size={25} />}
                className="!no-underline"
              >
                Membranes
              </DropdownItem>
              <DropdownItem
                key={"methods"}
                color="secondary"
                startContent={<FaMagnifyingGlass className="p-0.5" size={24} />}
                href="/browse/methods"
                textValue="Methods"
                className="!no-underline"
              >
                <label className="md:text-md cursor-pointer">Methods</label>
              </DropdownItem>
              <DropdownItem
                key={"transporters"}
                color="secondary"
                textValue="Transporters"
                startContent={<BsBoxes size={23} />}
                href="/browse/proteins"
                className="!no-underline"
              >
                <label className="md:text-md cursor-pointer">Proteins</label>
              </DropdownItem>
              <DropdownSection>
                <DropdownItem
                  key={"sets"}
                  textValue="Datasets"
                  color="secondary"
                  startContent={<MdPeopleOutline size={23} />}
                  href="/browse/datasets"
                  className="!no-underline"
                >
                  <label className="md:text-md cursor-pointer">Datasets</label>
                </DropdownItem>
              </DropdownSection>
            </DropdownMenu>
          </Dropdown>
        </NavbarItem>
        <NavbarItem>
          <MenuLink href="/stats" title="Statistics" />
        </NavbarItem>
        <NavbarItem isActive>
          <MenuLink href="/lab" title="Lab" />
        </NavbarItem>
        <NavbarItem>
          <MenuLink href="/docs" title="Documentation" />
        </NavbarItem>
        <div>
          <Button
            size="md"
            color={isScrolled ? "secondary" : "default"}
            startContent={<FaMagnifyingGlass size={18} />}
            onPress={() => setIsVisibleSE(true)}
            endContent={<Kbd keys={["command"]}>K</Kbd>}
          >
            Search
          </Button>
          <SearchEngine
            isOpenSE={isVisibleSE}
            onClose={() => setIsVisibleSE(false)}
          />
        </div>
        {/* <Input
          classNames={{
            base: "max-w-full sm:max-w-[10rem] h-10",
            mainWrapper: "h-full",
            input: "text-small",
            inputWrapper:
              "h-full font-normal text-default-500 bg-default-400/20 dark:bg-default-500/20",
          }}
          placeholder="Type to search..."
          size="sm"
          startContent={<SearchIcon size={18} />}
          type="search"
        /> */}
        {/* {props.user?.id ? (
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
                <p className="font-semibold">Přihlášen(a) jako</p>
                <p className="font-semibold">{props.user.email}</p>
              </DropdownItem>
              <DropdownItem key="settings">Nastavení účtu</DropdownItem>
              <DropdownItem key="logout" color="danger" href="/logout">
                Odhlásit se
              </DropdownItem>
            </DropdownMenu>
          </Dropdown>
        ) : ( */}
        <Link href="/login">{/* <UserDetailButton /> */}</Link>
        {/* )} */}
      </NavbarContent>
      <NavbarMenu className="flex flex-col gap-2 dark:bg-background-dark/80">
        <NavbarMenuItem>
          {/* <Link href={props?.user ? "/logout" : "/login"}>
            <UserDetailButton user={props.user} toMenu />
          </Link> */}
        </NavbarMenuItem>
        {/* <Divider /> */}
        <NavbarMenuItem onClick={() => setIsMenuOpen(false)}>
          {/* <Dropdown>
            <DropdownTrigger className="cursor-pointer">
              <label
                className="tap-highlight-transparent font-semibold
        outline-none data-[focus-visible=true]:z-10 data-[focus-visible=true]:outline-2 
        data-[focus-visible=true]:outline-focus data-[focus-visible=true]:outline-offset-2 
        text-medium no-underline hover:underline hover:opacity-80 active:opacity-disabled 
        transition-opacity data-[active=true]:text-primary data-[active=true]:font-semibold
        w-full h-12 text-xl"
              >
                Browse
              </label>
            </DropdownTrigger>
            <DropdownMenu> */}
          {/* <DropdownItem
                key={"membranes"}
                color="secondary"
                startContent={<MdOutlineLayers size={25} />}
                href="/browse/membranes"
                className="!no-underline"
              >
                <label className="md:text-md cursor-pointer no-underline">
                  Membranes
                </label>
              </DropdownItem> */}
          {/* <DropdownItem
                key={"methods"}

                color="secondary"
                startContent={<FaMagnifyingGlass className="p-0.5" size={24} />}
                href="/browse/methods"
                className="!no-underline"
              >
                <label className="md:text-md cursor-pointer">Methods</label>
              </DropdownItem>
              <DropdownItem
                key={"transporters"}
                color="secondary"
                startContent={<BsBoxes size={23} />}
                href="/browse/proteins"
                className="!no-underline"
              > */}
          {/* <Link className="underline-none" href="/browse/proteins"> */}
          {/* <label className="md:text-md cursor-pointer">
                  Transporters
                </label> */}
          {/* </Link> */}
          {/* </DropdownItem> */}
          {/* <DropdownSection>
                <DropdownItem
                  key={"sets"}
                  color="secondary"
                  startContent={<MdPeopleOutline size={23} />}
                  href="/browse/datasets"
                  className="!no-underline"
                >
                  <label className="md:text-md cursor-pointer">Sources</label>
                </DropdownItem>
              </DropdownSection> */}
          {/* </DropdownMenu> */}
          {/* </Dropdown> */}
        </NavbarMenuItem>
        <NavbarMenuItem onClick={() => setIsMenuOpen(false)}>
          <MenuItem
            href="/stats"
            title="Statistics" //demoMark={!props.user?.id}
          />
        </NavbarMenuItem>
        <NavbarMenuItem onClick={() => setIsMenuOpen(false)}>
          <MenuItem
            href="/lab"
            title="Lab"
            // demoMark={!props.user?.id}
          />
        </NavbarMenuItem>
        <NavbarMenuItem onClick={() => setIsMenuOpen(false)}>
          <MenuItem href="/docs" title="Documentation" />
        </NavbarMenuItem>
      </NavbarMenu>
    </Navbar>
  );
}

const MenuItem = ({ href = "#", title = "", demoMark = false }) => {
  return (
    <Link
      className="relative inline-flex items-center gap-2 tap-highlight-transparent font-semibold
        outline-none data-[focus-visible=true]:z-10 data-[focus-visible=true]:outline-2 
        data-[focus-visible=true]:outline-focus data-[focus-visible=true]:outline-offset-2 
        no-underline hover:underline hover:opacity-80 active:opacity-disabled 
        transition-opacity data-[active=true]:text-primary data-[active=true]:font-semibold
        w-full h-12 text-lg"
      href={href}
    >
      {demoMark && (
        <div className="absolute -top-0 -left-4 text-xs rounded-xl px-1 bg-warning -rotate-12">
          Demo
        </div>
      )}
      {title}
    </Link>
  );
};

const MenuLink = ({ href = "#", title = "" }) => {
  return (
    <Link
      className="relative inline-flex items-center tap-highlight-transparent font-bold
        outline-none data-[focus-visible=true]:z-10 data-[focus-visible=true]:outline-2 
        data-[focus-visible=true]:outline-focus data-[focus-visible=true]:outline-offset-2 
        text-medium no-underline hover:underline hover:opacity-80 active:opacity-disabled 
        transition-opacity data-[active=true]:text-primary data-[active=true]:font-semibold"
      href={href}
    >
      {title}
    </Link>
  );
};

// const UserDetailButton = (props: { user?: UserSession; toMenu?: boolean }) => (
//   <div
//     className={`flex flex-row items-center gap-4 no-wrap ${
//       props.toMenu && "pt-6 pb-4"
//     }`}
//   >
//     <Avatar
//       isBordered={!props.toMenu}
//       className="transition-transform"
//       color="secondary"
//       size={props.toMenu ? "md" : "sm"}
//     />
//     <div className="max-w-40 flex flex-col justify-center cursor-pointer">
//       {props.user ? (
//         <label
//           className={`text-sm font-bold cursor-pointer whitespace-nowrap text-purple-400 ${
//             props.toMenu && "text-black/70"
//           }`}
//         >{`${props.user?.first_name} ${props.user?.last_name}`}</label>
//       ) : (
//         <label className="text-sm font-bold cursor-pointer">Přihlásit se</label>
//       )}
//       <label className="text-xs line-clamp-1 wrap-break-word leading-[1] cursor-pointer">
//         {props.user?.school_name ?? ""}
//       </label>
//     </div>
//   </div>
// );

// const SearchIcon = ({
//   size = 24,
//   strokeWidth = 1.5,
//   width = null,
//   height = null,
//   ...props
// }) => {
//   return (
//     <svg
//       aria-hidden="true"
//       fill="none"
//       focusable="false"
//       height={height || size}
//       role="presentation"
//       viewBox="0 0 24 24"
//       width={width || size}
//       {...props}
//     >
//       <path
//         d="M11.5 21C16.7467 21 21 16.7467 21 11.5C21 6.25329 16.7467 2 11.5 2C6.25329 2 2 6.25329 2 11.5C2 16.7467 6.25329 21 11.5 21Z"
//         stroke="currentColor"
//         strokeLinecap="round"
//         strokeLinejoin="round"
//         strokeWidth={strokeWidth}
//       />
//       <path
//         d="M22 22L20 20"
//         stroke="currentColor"
//         strokeLinecap="round"
//         strokeLinejoin="round"
//         strokeWidth={strokeWidth}
//       />
//     </svg>
//   );
// };
