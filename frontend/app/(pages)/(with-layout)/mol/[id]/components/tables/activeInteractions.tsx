"use client";
import { IInteractionActive } from "@/lib/api/admin/interfaces/Interaction";
import IStructure from "@/lib/api/admin/interfaces/Structure";
import UiTable from "@/components/ui/table";
import { activeInteractionsColumns } from "./columns";

export default function ActiveInteractionTable(props: {
  structure: IStructure;
}) {
  return (
    <UiTable<IInteractionActive>
      apiUrl={`/api/interactions/active/structure/${props.structure.identifier}`}
      aria-label="Passive interactions table"
      columns={activeInteractionsColumns}
      itemKey="id"
      defaultRowsPerPage={8}
    />
  );
}
