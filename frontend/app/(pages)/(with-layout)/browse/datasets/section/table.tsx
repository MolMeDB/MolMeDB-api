"use client";
import UiTable from "@/components/ui/table";
import { useMemo, useState } from "react";
import IPublication from "@/lib/api/admin/interfaces/Publication";
import { datasetColumns } from "./columns";
import { Modal, ModalContent, Tooltip, useDisclosure } from "@heroui/react";
import { EyeIcon } from "@/components/ui/icons/eye";
import PublicationModalContent from "./table/modalContent";
// import { passiveInteractionsColumns } from "./columns";

export default function DatasetsTable(props: {}) {
  const {
    isOpen: isOpenDetail,
    onOpen: onOpenDetail,
    onOpenChange: onOpenChangeDetail,
  } = useDisclosure();
  const [detailId, setDetailId] = useState(0);

  const stableApiParams = useMemo(() => {
    return {};
  }, []);

  const columns = useMemo(() => {
    return [
      ...datasetColumns,
      {
        key: "actions",
        title: "Actions",
        render: (item) => (
          <div className="relative flex items-center w-full gap-2">
            <Tooltip content="Details">
              <span className="text-lg text-default-400 cursor-pointer active:opacity-50">
                <EyeIcon
                  onClick={() => {
                    onOpenDetail();
                    setDetailId(item.id);
                  }}
                />
              </span>
            </Tooltip>
          </div>
        ),
        isSortable: true,
        sortKey: "km",
      },
    ];
  }, []);

  return (
    <>
      <UiTable<IPublication>
        apiUrl={`/api/publication`}
        apiParams={stableApiParams}
        aria-label="Datasets table"
        columns={columns}
        itemKey="id"
        defaultRowsPerPage={10}
        hasSearch
      />
      <Modal
        scrollBehavior="inside"
        size="xl"
        isOpen={isOpenDetail}
        onOpenChange={onOpenChangeDetail}
      >
        <ModalContent>
          {(onClose) => (
            <PublicationModalContent onClose={onClose} id={detailId} />
          )}
        </ModalContent>
      </Modal>
    </>
  );
}
