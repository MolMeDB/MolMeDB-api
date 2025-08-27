"use client";

import ICategory from "@/lib/api/admin/interfaces/Category";
import SectionPieChart from "./PieChart";
import { useEffect, useState } from "react";
import SectionDetail from "./Detail";
import { useSearchParams } from "next/navigation";

export default function SectionWrapper(props: { categories: ICategory[] }) {
  const [selectedProteinId, setSelectedProteinId] = useState("");
  const searchParams = useSearchParams();

  useEffect(() => {
    setSelectedProteinId(searchParams.get("id") || "");
  }, [searchParams]);

  return (
    props.categories && (
      <>
        <SectionPieChart
          categories={props.categories}
          setSelectedProteinId={setSelectedProteinId}
        />
        <div className="h-1 w-full bg-zinc-100 dark:bg-background-dark-2" />
        <SectionDetail proteinId={selectedProteinId} />
      </>
    )
  );
}
