"use client";

import { MdComment, MdPriorityHigh } from "react-icons/md";
import InfoBox from "./components/infobox";
import {
  IPrediction,
  IPredictionDataset,
} from "@/lib/api/admin/interfaces/Predictions";
import { RiProgress2Line } from "react-icons/ri";
import PredictionDatasetStateBar, {
  PredictionDatasetStateHelper,
} from "@/components/ui/progressBar/predictionDatasetStateBar";
import { FaTemperatureHalf, FaUser } from "react-icons/fa6";
import { FiDatabase } from "react-icons/fi";
import { CgArrowRightO } from "react-icons/cg";
import UiTable from "@/components/ui/table";
import { Drawer, DrawerContent, Tooltip, useDisclosure } from "@heroui/react";
import { useEffect, useMemo, useState } from "react";
import { datasetColumns } from "./components/columns";
import { EyeIcon } from "@/components/ui/icons/eye";
import PredictionDetail from "./components/predicitonDetail";

export default function PredictionDatasetClient(props: {
  data: IPredictionDataset;
}) {
  const { isOpen, onOpenChange, onOpen } = useDisclosure();
  const [modalRecord, setModalRecord] = useState<IPrediction | null>(null);

  const stableApiParams = useMemo(() => {
    return {};
  }, []);

  const columns = [
    ...datasetColumns,
    {
      key: "actions",
      title: "Actions",
      render: (item: IPrediction) => (
        <div className="relative flex items-center w-full gap-2">
          <Tooltip content="Details" placement="right">
            <span className="text-lg text-default-400 cursor-pointer active:opacity-50">
              <EyeIcon
                onClick={() => {
                  setModalRecord(item);
                }}
              />
            </span>
          </Tooltip>
        </div>
      ),
      isSortable: false,
    },
  ];

  useEffect(() => {
    if (modalRecord) {
      onOpen();
    }
  }, [modalRecord]);

  useEffect(() => {
    if (!isOpen) {
      setModalRecord(null);
    }
  }, [isOpen]);

  const finished_percent = Math.round(
    ((props.data.stats.done + props.data.stats.failed) /
      props.data.stats.total) *
      100,
  );

  return (
    <div className="flex flex-col gap-4">
      <InfoBox icon={<MdComment size={20} />} title={"Comment"} help={"test"}>
        {props.data.comment}
      </InfoBox>
      <div className="grid grid-cols-3 gap-4">
        <div className="col-span-2">
          <InfoBox icon={<FaUser size={20} />} title={"Author"}>
            {props.data.user?.email}
          </InfoBox>
        </div>
        <InfoBox icon={<MdPriorityHigh size={20} />} title={"Priority"}>
          {props.data.priority == 1 ? (
            <label className="text-success font-bold">Low</label>
          ) : props.data.priority == 2 ? (
            <label className="text-primary font-bold">Medium</label>
          ) : (
            <label className="text-warning font-bold">High</label>
          )}
        </InfoBox>
      </div>
      <div className="grid grid-cols-3 gap-4">
        <InfoBox
          icon={<FaTemperatureHalf size={20} />}
          title={"Temperature"}
          help={"test"}
        >
          {props.data.temperature} °C
        </InfoBox>
        <InfoBox
          icon={<FiDatabase size={20} />}
          title={"Membrane"}
          help={"test"}
        >
          {props.data.membrane.name}
        </InfoBox>
        <InfoBox
          icon={<CgArrowRightO size={20} />}
          title={"Method"}
          help={"test"}
        >
          {props.data.method}
        </InfoBox>
      </div>
      <InfoBox
        icon={<RiProgress2Line size={20} />}
        title={`Progress (${finished_percent}%)`}
        help={"test"}
      >
        <div className="flex flex-col gap-1">
          <PredictionDatasetStateBar
            ready={props.data.stats.pending}
            running={props.data.stats.running}
            done={props.data.stats.done}
            error={props.data.stats.failed}
          />
          <div className="flex flex-row gap-4">
            {/* <PredictionDatasetStateHelper name="Pending" type="pending" /> */}
            <PredictionDatasetStateHelper name="Queued" type="ready" />
            <PredictionDatasetStateHelper name="Running" type="running" />
            <PredictionDatasetStateHelper name="Finished" type="done" />
            <PredictionDatasetStateHelper name="Error" type="error" />
          </div>
        </div>
      </InfoBox>
      {/* TABLE OF JOBS  */}
      <UiTable<IPrediction>
        apiUrl={`/api/predictions/datasets/${props.data.id}/records`}
        apiParams={stableApiParams}
        aria-label="Datasets table"
        columns={columns}
        itemKey="id"
        defaultRowsPerPage={50}
        // searchPlaceholder="Search by comment..."
        // hasSearch
      />
      <Drawer
        scrollBehavior="inside"
        isDismissable
        isKeyboardDismissDisabled
        size="2xl"
        placement="left"
        isOpen={isOpen}
        onOpenChange={onOpenChange}
      >
        <DrawerContent>
          {(onClose) =>
            modalRecord && (
              <PredictionDetail onClose={onClose} data={modalRecord} />
            )
          }
        </DrawerContent>
      </Drawer>
    </div>
  );
}
