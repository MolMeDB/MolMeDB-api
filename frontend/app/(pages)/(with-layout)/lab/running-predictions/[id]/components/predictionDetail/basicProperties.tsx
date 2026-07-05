"use client";
import DetailSection from "@/app/(pages)/(with-layout)/mol/[id]/components/section";
import DetailProperty from "@/app/(pages)/(with-layout)/mol/[id]/components/property";
import { IPrediction } from "@/lib/api/admin/interfaces/Predictions";
import { SiMoleculer } from "react-icons/si";
import {
  MdAccessTime,
  MdErrorOutline,
  MdFingerprint,
  MdList,
  MdSync,
} from "react-icons/md";
import { Chip, Link, Progress } from "@heroui/react";
import { RiProgress3Line } from "react-icons/ri";
import {
  getRemoteStatusColor,
  getRemoteStatusLabel,
} from "@/lib/predictionRemoteStatus";

function getProgressColor(
  compound: IPrediction,
): "danger" | "success" | "warning" | "default" {
  if (["Error", "Remove", "Stopped"].includes(compound.enum_state)) {
    return "danger";
  }

  if (compound.progress_percent >= 100) {
    return "success";
  }

  if (compound.enum_state === "Running") {
    return "warning";
  }

  return "default";
}

export default function CompoundBasicProperties(props: {
  compound: IPrediction;
}) {
  const allLogs = props.compound.logs ?? [];
  const calcId = props.compound.remote_calculation_id ?? null;
  const remoteLogs = allLogs.filter((log) => {
    const logCalcId = (log as Record<string, unknown>).calculation_id ?? null;
    return logCalcId === null || logCalcId === undefined || logCalcId === calcId;
  });

  return (
    <DetailSection title="Basic properties" order={1}>
      <div className="grid grid-cols-1 gap-4 gap-y-6">
        <DetailProperty
          icon={<SiMoleculer size={30} />}
          title="Canonical smiles"
          value={props.compound.structure.canonical_smiles}
        />
        <DetailProperty
          icon={<MdList size={30} />}
          title="MolMeDB ID"
          value={
            props.compound.structure.remote_identifier ? (
              <Link href={`/mol/${props.compound.structure.remote_identifier}`}>
                {props.compound.structure.remote_identifier}
              </Link>
            ) : (
              <span className="font-bold text-warning-700">
                Not exists yet
              </span>
            )
          }
        />
        <DetailProperty
          icon={<RiProgress3Line size={30} />}
          title="Progress"
          className="w-full"
          value={
            <div className="flex flex-row gap-4 w-full items-center">
              <Progress
                className="w-1/2"
                color={getProgressColor(props.compound)}
                value={props.compound.progress_percent}
                minValue={0}
                maxValue={100}
              />
              <span className="whitespace-nowrap">
                {props.compound.enum_state}: {props.compound.enum_step} (
                {props.compound.progress_percent}%)
              </span>
            </div>
          }
        />
        <DetailProperty
          icon={<MdSync size={30} />}
          title="Remote status"
          value={
            <div className="flex flex-col gap-2">
              <div className="flex flex-wrap gap-2">
                <Chip
                  size="sm"
                  variant="flat"
                  color={getRemoteStatusColor(props.compound)}
                >
                  {getRemoteStatusLabel(props.compound)}
                </Chip>
                {props.compound.remote_current_step && (
                  <Chip size="sm" variant="flat" color="secondary">
                    {props.compound.remote_current_step}
                  </Chip>
                )}
              </div>
              {props.compound.remote_error_message && (
                <span className="text-danger text-sm">
                  {props.compound.remote_error_message}
                </span>
              )}
              {props.compound.is_paused && props.compound.remote_pause_reason && (
                <span className="text-default-500 text-sm">
                  {props.compound.remote_pause_reason}
                </span>
              )}
            </div>
          }
        />
        <DetailProperty
          icon={<MdFingerprint size={30} />}
          title="Remote IDs"
          value={
            <div className="flex flex-col gap-1">
              <span>
                Calculation: {props.compound.remote_calculation_id ?? "-"}
              </span>
              <span>Molecule: {props.compound.remote_molecule_id ?? "-"}</span>
            </div>
          }
        />
        <DetailProperty
          icon={<MdAccessTime size={30} />}
          title="Remote timestamps"
          value={
            <div className="flex flex-col gap-1">
              <span>
                Heartbeat:{" "}
                {props.compound.is_paused
                  ? "Paused"
                  : (props.compound.remote_heartbeat_at ?? "-")}
              </span>
              <span>Paused: {props.compound.remote_paused_at ?? "-"}</span>
              <span>
                Last status: {props.compound.remote_last_status_at ?? "-"}
              </span>
              <span>Finished: {props.compound.remote_finished_at ?? "-"}</span>
              <span className="text-xs text-default-400 mt-1">
                Status is refreshed at most every 5 minutes
              </span>
            </div>
          }
        />
        <DetailProperty
          icon={<MdErrorOutline size={30} />}
          title={`Remote logs (${remoteLogs.length})`}
          value={
            remoteLogs.length > 0 ? (
              <pre className="max-h-64 overflow-auto rounded-xl bg-default-100 p-3 text-xs whitespace-pre-wrap">
                {JSON.stringify(
                  remoteLogs.map((log) => {
                    const { details: _d, ...rest } = log as Record<
                      string,
                      unknown
                    >;
                    return rest;
                  }),
                  null,
                  2,
                )}
              </pre>
            ) : (
              "No remote logs yet"
            )
          }
        />
      </div>
    </DetailSection>
  );
}
