"use client";

import UiTable from "@/components/ui/table";
import { useMemo } from "react";
import { datasetColumns as columns } from "./columns";
import { IPredictionDataset } from "@/lib/api/admin/interfaces/Predictions";

export default function MyJobsTable() {
  const stableApiParams = useMemo(() => {
    return {};
  }, []);

  return (
    <UiTable<IPredictionDataset>
      apiUrl={`/api/predictions/datasets`}
      apiParams={stableApiParams}
      aria-label="Prediction datasets table"
      columns={columns}
      itemKey="id"
      defaultRowsPerPage={20}
      searchPlaceholder="Search by comment..."
      hasSearch
    />
  );
}
