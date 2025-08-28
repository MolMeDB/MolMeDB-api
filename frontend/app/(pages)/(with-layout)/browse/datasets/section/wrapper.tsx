"use client";

import { Tab, Tabs } from "@heroui/react";
import { MdSearch } from "react-icons/md";
import { IoMdStats } from "react-icons/io";
import SectionStats from "./charts";
import DatasetsTable from "./table";

export default function SectionWrapper() {
  return (
    <div className="flex flex-col items-center gap-8">
      <Tabs
        variant="bordered"
        color="primary"
        aria-label="Typ skladu"
        size="lg"
        classNames={{
          tabList: "rounded-full",
          tab: "text-lg font-semibold px-6 py-4",
          cursor: "rounded-full",
          panel: "w-full",
        }}
      >
        <Tab
          key="browse"
          title={
            <div className="flex items-center space-x-2">
              <MdSearch size={25} />
              <span>Browse</span>
            </div>
          }
        >
          <DatasetsTable />
        </Tab>
        <Tab
          key="stats"
          title={
            <div className="flex items-center space-x-2">
              <IoMdStats size={25} />
              <span>Stats</span>
            </div>
          }
        >
          <SectionStats />
        </Tab>
      </Tabs>
    </div>
  );
}
