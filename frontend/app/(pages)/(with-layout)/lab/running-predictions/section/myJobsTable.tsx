"use client";

import UiTable from "@/components/ui/table";
import { Modal, ModalContent, useDisclosure } from "@heroui/react";
import { useMemo } from "react";
import { datasetColumns as columns } from "./columns";
import { IPredictionDataset } from "@/lib/api/admin/interfaces/Predictions";

export default function MyJobsTable() {
  const { isOpen, onOpenChange, onOpen } = useDisclosure();

  const stableApiParams = useMemo(() => {
    return {};
  }, []);

  return (
    <>
      <UiTable<IPredictionDataset>
        apiUrl={`/api/predictions/datasets`}
        apiParams={stableApiParams}
        aria-label="Datasets table"
        columns={columns}
        itemKey="id"
        defaultRowsPerPage={20}
        searchPlaceholder="Search by comment..."
        hasSearch
      />
      <Modal
        scrollBehavior="inside"
        size="xl"
        isOpen={isOpen}
        onOpenChange={onOpenChange}
      >
        <ModalContent>
          {(onClose) => (
            <></>
            // <PublicationModalContent onClose={onClose} id={detailId} />
          )}
        </ModalContent>
      </Modal>
    </>
  );
}
