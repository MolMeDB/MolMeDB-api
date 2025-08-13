"use client";

import ICategory from "@/lib/api/admin/interfaces/Category";
import SectionPieChart from "./PieChart";
import { useEffect, useState } from "react";
import SectionDetail from "./Detail";
import { useSearchParams } from "next/navigation";

export default function SectionWrapper(props: { categories: ICategory[] }) {
  const [selectedMembraneId, setSelectedMembraneId] = useState("");
  const searchParams = useSearchParams();

  useEffect(() => {
    setSelectedMembraneId(searchParams.get("id") || "");
  }, [searchParams]);

  return (
    props.categories && (
      <>
        <SectionPieChart
          categories={props.categories}
          setSelectedMembraneId={setSelectedMembraneId}
        />
        <div className="h-1 w-full bg-zinc-100" />
        <SectionDetail membraneId={selectedMembraneId} />
      </>
    )
  );
}
