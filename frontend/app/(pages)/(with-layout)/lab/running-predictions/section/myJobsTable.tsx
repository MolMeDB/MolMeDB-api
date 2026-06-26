"use client";

import UiTable from "@/components/ui/table";
import { useMemo } from "react";
import { datasetColumns as columns } from "./columns";
import { IPredictionDataset } from "@/lib/api/admin/interfaces/Predictions";
import { Button } from "@heroui/react";
import Link from "next/link";
import { MdAdd } from "react-icons/md";
import PredictionStatsWidget from "@/components/ui/predictionStatsWidget";

export default function MyJobsTable() {
  const stableApiParams = useMemo(() => {
    return {};
  }, []);

  return (
    <div className="flex flex-col gap-4">
      <PredictionStatsWidget />
      <div className="flex justify-end">
        <Button
          as={Link}
          href="/lab/new-predictions"
          color="primary"
          startContent={<MdAdd size={20} />}
        >
          New dataset
        </Button>
      </div>
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
    </div>
  );
}
