"use client";

import ICategory from "@/lib/api/admin/interfaces/Category";
import SectionPieChart from "./PieChart";
import { useEffect, useState } from "react";
import SectionDetail from "./Detail";
import { useSearchParams } from "next/navigation";

export default function SectionWrapper(props: { categories: ICategory[] }) {
  const [selectedMembraneId, setSelectedMembraneId] = useState("");
  const searchParams = useSearchParams();
  const membraneIdFromUrl = searchParams.get("id") || "";

  useEffect(() => {
    if (membraneIdFromUrl !== "") {
      setSelectedMembraneId(membraneIdFromUrl);
    }
  }, [membraneIdFromUrl]);

  return (
    props.categories && (
      <>
        <SectionPieChart
          categories={props.categories}
          selectedMembraneId={selectedMembraneId}
          setSelectedMembraneId={setSelectedMembraneId}
        />
        <div className="h-1 w-full bg-zinc-100 dark:bg-background-dark-2" />
        <SectionDetail membraneId={selectedMembraneId} />
      </>
    )
  );
}
