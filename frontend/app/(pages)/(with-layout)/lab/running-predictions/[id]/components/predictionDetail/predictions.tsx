"use client";
import { useMemo } from "react";
import DetailSection from "@/app/(pages)/(with-layout)/mol/[id]/components/section";
import UiTable from "@/components/ui/table";
import {
  IPrediction,
  IPredictionStructure,
} from "@/lib/api/admin/interfaces/Predictions";
import IUiTableColumn from "@/components/ui/table/interface/columns";

const remoteLoadError =
  "Unable to load data from the remote server. Please try again later.";

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
    render: (item) => {
      if (item.result?.results === false) {
        return remoteLoadError;
      }

      const solute = getLastSolute(item);

      return solute?.logK !== undefined ? solute.logK.toFixed(2) : "N/A";
    },
    isSortable: false,
  },
  {
    key: "logperm",
    title: "LogPerm",
    render: (item) => {
      if (item.result?.results === false) {
        return remoteLoadError;
      }

      const solute = getLastSolute(item);

      return solute?.logPerm !== undefined ? solute.logPerm.toFixed(2) : "N/A";
    },
    isSortable: false,
  },
];

function getLastSolute(item: IPrediction) {
  const results = item.result?.results;

  if (!Array.isArray(results) || results.length === 0) {
    return null;
  }

  const solutes = results[results.length - 1]?.solutes;

  if (!Array.isArray(solutes) || solutes.length === 0) {
    return null;
  }

  return solutes[solutes.length - 1];
}

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
