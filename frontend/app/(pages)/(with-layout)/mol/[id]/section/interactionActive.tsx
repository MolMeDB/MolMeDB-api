"use client";

import DetailSection from "../components/section";
import IStructure from "@/lib/api/admin/interfaces/Structure";
import ActiveInteractionTable from "../components/tables/activeInteractions";
import { downloadFile } from "@/utils/downloadFile";
import { addToast, Button } from "@heroui/react";
import { useState } from "react";

export default function CompoundPassiveInteractions(props: {
  compound: IStructure;
}) {
  const [canExport, setCanExport] = useState(false);
  const [isExporting, setIsExporting] = useState(false);

  const handleExport = async () => {
    if (!canExport || isExporting) {
      return;
    }

    setIsExporting(true);

    try {
      await downloadFile(
        `/api/export/structure/${props.compound.id}/activeInteractions`,
        `${props.compound.identifier}-active-interactions.csv`,
      );
    } catch {
      addToast({
        title: "Export failed",
        description:
          "An error occurred while preparing the file. Please try again later.",
        color: "danger",
        shouldShowTimeoutProgress: true,
        timeout: 6000,
      });
    } finally {
      setIsExporting(false);
    }
  };

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
            variant="bordered"
            color="success"
            isDisabled={!canExport}
            isLoading={isExporting}
            onPress={handleExport}
          >
            Export data
          </Button>
        </div>
      </>
    </DetailSection>
  );
}
