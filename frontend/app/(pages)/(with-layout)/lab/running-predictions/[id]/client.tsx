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

const predictionStateOptions = [
  { label: "Stopped", value: 0 },
  { label: "Prepared", value: 1 },
  { label: "Error", value: 2 },
  { label: "Remove", value: 3 },
  { label: "Running", value: 4 },
  { label: "Finished", value: 5 },
];

const predictionStepOptions = [
  { label: "Pending", value: 0 },
  { label: "Ionized", value: 1 },
  { label: "SDF Ready", value: 2 },
  { label: "Optimization Running", value: 3 },
  { label: "COSMO Running", value: 4 },
  { label: "Result prepared for parsing", value: 5 },
  { label: "Result Parsed", value: 6 },
  { label: "Result Stored", value: 7 },
];

export default function PredictionDatasetClient(props: {
  data: IPredictionDataset;
}) {
  const { isOpen, onOpenChange, onOpen } = useDisclosure();
  const [modalRecord, setModalRecord] = useState<IPrediction | null>(null);

  const stableApiParams = useMemo(() => {
    return {};
  }, []);

  const columns = useMemo(
    () => [
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
    ],
    [],
  );

  useEffect(() => {
    if (modalRecord) {
      onOpen();
    }
  }, [modalRecord, onOpen]);

  useEffect(() => {
    if (!isOpen) {
      setModalRecord(null);
    }
  }, [isOpen]);

  const progressPercent = props.data.overall_stats.progress_percent;

  const datasetComment = props.data.comment || "No comment";

  const priorityLabel: Record<IPredictionDataset["priority"], string> = {
    1: "Low",
    2: "Medium",
    3: "High",
  };

  return (
    <div className="flex flex-col gap-4">
      <InfoBox icon={<MdComment size={20} />} title={"Comment"}>
        {datasetComment}
      </InfoBox>
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div className="lg:col-span-2">
          <InfoBox icon={<FaUser size={20} />} title={"Author"}>
            {props.data.user?.email ?? props.data.user?.name ?? "N/A"}
          </InfoBox>
        </div>
        <InfoBox icon={<MdPriorityHigh size={20} />} title={"Priority"}>
          {props.data.priority == 1 ? (
            <span className="text-success font-bold">
              {priorityLabel[props.data.priority]}
            </span>
          ) : props.data.priority == 2 ? (
            <span className="text-primary font-bold">
              {priorityLabel[props.data.priority]}
            </span>
          ) : (
            <span className="text-warning font-bold">
              {priorityLabel[props.data.priority]}
            </span>
          )}
        </InfoBox>
      </div>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <InfoBox icon={<FaTemperatureHalf size={20} />} title={"Temperature"}>
          {props.data.temperature} °C
        </InfoBox>
        <InfoBox icon={<FiDatabase size={20} />} title={"Membrane"}>
          {props.data.membrane?.name ?? "N/A"}
        </InfoBox>
        <InfoBox icon={<CgArrowRightO size={20} />} title={"Method"}>
          {props.data.method}
        </InfoBox>
      </div>
      <InfoBox
        icon={<RiProgress2Line size={20} />}
        title={`Progress (${progressPercent}%)`}
      >
        <div className="flex flex-col gap-1">
          <PredictionDatasetStateBar
            pending={props.data.stats.pending}
            running={props.data.stats.running}
            done={props.data.stats.done}
            error={props.data.stats.failed}
          />
          <div className="flex flex-wrap gap-x-4 gap-y-2">
            <PredictionDatasetStateHelper name="Pending" type="pending" />
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
        defaultRowsPerPage={40}
        filters={[
          {
            key: "query",
            type: "text",
            placeholder: "Search ID or SMILES...",
          },
          {
            key: "state",
            type: "select",
            multiple: true,
            placeholder: "State",
            options: predictionStateOptions,
          },
          {
            key: "step",
            type: "select",
            multiple: true,
            placeholder: "Step",
            options: predictionStepOptions,
          },
        ]}
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
