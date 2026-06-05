"use client";

import DetailSection from "../components/section";
import IStructure from "@/lib/api/admin/interfaces/Structure";
import ActiveInteractionTable from "../components/tables/activeInteractions";
import { Button } from "@heroui/react";
import Link from "next/link";
import { useState } from "react";

export default function CompoundPassiveInteractions(props: {
  compound: IStructure;
}) {
  const [canExport, setCanExport] = useState(false);

  return (
    <DetailSection
      title="Interactions with proteins (active interactions)"
      order={6}
    >
      <>
        <div className="mt-4">
          <ActiveInteractionTable
            structure={props.compound}
            onTotalItemsChange={(totalItems) => setCanExport(totalItems > 0)}
          />
        </div>
        <div className="flex flex-row justify-end">
          <Button
            as={Link}
            href={
              "/api/export/structure/" +
              props.compound.id +
              "/activeInteractions"
            }
            variant="bordered"
            color="success"
            isDisabled={!canExport}
          >
            Export data
          </Button>
        </div>
      </>
    </DetailSection>
  );
}
