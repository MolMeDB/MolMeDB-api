"use client";
import { IInteractionPassive } from "@/lib/api/admin/interfaces/Interaction";
import IStructure from "@/lib/api/admin/interfaces/Structure";
import UiTable from "@/components/ui/table";
import { useMemo } from "react";
import { passiveInteractionsColumns } from "./columns";

export default function PassiveInteractionTable(props: {
  structure: IStructure;
  membraneIds: string[] | null;
  methodIds: string[] | null;
}) {
  const stableApiParams = useMemo(() => {
    return {
      "membraneIds[]": Array.from(props.membraneIds ?? []),
      "methodIds[]": Array.from(props.methodIds ?? []),
    };
  }, [props.membraneIds?.length, props.methodIds?.length]);

  return (
    <UiTable<IInteractionPassive>
      apiUrl={`/api/interactions/passive/structure/${props.structure.identifier}`}
      apiParams={stableApiParams}
      aria-label="Passive interactions table"
      columns={passiveInteractionsColumns}
      itemKey="id"
      defaultRowsPerPage={8}
    />
  );
}
