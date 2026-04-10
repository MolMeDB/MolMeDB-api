"use client";
import { useMemo } from "react";
import DetailSection from "@/app/(pages)/(with-layout)/mol/[id]/components/section";
import UiTable from "@/components/ui/table";
import {
  IPrediction,
  IPredictionStructure,
} from "@/lib/api/admin/interfaces/Predictions";
import IUiTableColumn from "@/components/ui/table/interface/columns";

export default function CompoundPredictions(props: {
  compound: IPredictionStructure;
}) {
  return (
    <DetailSection title="Prediction results" order={5}>
      <>
        <div className="mt-4">
          <PredictionsTable structure={props.compound} />
        </div>
      </>
    </DetailSection>
  );
}

const columns: IUiTableColumn<IPrediction>[] = [
  {
    key: "id",
    title: "ID",
    render: (item) => item.result.id,
    sortKey: "id",
    isSortable: true,
  },
  {
    key: "membrane",
    title: "Membrane",
    render: (item) => item.membrane.name,
    isSortable: true,
    sortKey: "membrane",
  },
  {
    key: "method",
    title: "Method",
    render: (item) => item.method,
    isSortable: true,
    sortKey: "method",
  },
  {
    key: "temperature",
    title: "T [°C]",
    render: (item) => item.temperature,
    isSortable: false,
  },
  {
    key: "logk",
    title: "LogK",
    render: (item) =>
      item?.result?.results?.pop()?.solutes?.pop()?.logK.toFixed(2),
    isSortable: false,
  },
  {
    key: "logperm",
    title: "LogPerm",
    render: (item) =>
      item?.result?.results?.pop()?.solutes?.pop()?.logPerm.toFixed(2),
    isSortable: false,
  },
];

function PredictionsTable(props: { structure: IPredictionStructure }) {
  const stableApiParams = useMemo(() => {
    return {
      hasResults: true,
    };
  }, []);

  return (
    <UiTable<IPrediction>
      apiUrl={`/api/predictions/byStructure/${props.structure.id}`}
      apiParams={stableApiParams}
      aria-label="Predictions table"
      columns={columns}
      itemKey="id"
      defaultRowsPerPage={8}
      loadingText="Parsing results..."
    />
  );
}
