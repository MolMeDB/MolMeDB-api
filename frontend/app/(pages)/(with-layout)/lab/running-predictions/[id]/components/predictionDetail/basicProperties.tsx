"use client";
import DetailSection from "@/app/(pages)/(with-layout)/mol/[id]/components/section";
import DetailProperty from "@/app/(pages)/(with-layout)/mol/[id]/components/property";
import { IPrediction } from "@/lib/api/admin/interfaces/Predictions";
import { SiMoleculer } from "react-icons/si";
import { MdList } from "react-icons/md";
import { Link, Progress } from "@heroui/react";
import { RiProgress3Line } from "react-icons/ri";

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
          title="Record"
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
      </div>
    </DetailSection>
  );
}
