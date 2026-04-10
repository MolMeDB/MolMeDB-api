"use client";
import DetailSection from "@/app/(pages)/(with-layout)/mol/[id]/components/section";
import DetailProperty from "@/app/(pages)/(with-layout)/mol/[id]/components/property";
import { IPrediction } from "@/lib/api/admin/interfaces/Predictions";
import { SiMoleculer } from "react-icons/si";
import { MdList } from "react-icons/md";
import {
  Link,
  Popover,
  PopoverContent,
  PopoverTrigger,
  Progress,
} from "@heroui/react";
import { RiProgress3Fill, RiProgress3Line } from "react-icons/ri";

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
              <label className="font-bold text-warning-700">
                Not exists yet
              </label>
            )
          }
        />
        <DetailProperty
          icon={<RiProgress3Line size={30} />}
          title="Progress"
          className="w-full"
          value={
            <div className="flex flex-row gap-4 w-full no-wrap items-center">
              <Progress
                className="w-1/2"
                color={
                  props.compound.step == 0
                    ? "default"
                    : props.compound.step == props.compound.total_steps
                      ? "success"
                      : "warning"
                }
                value={props.compound.step}
                minValue={0}
                maxValue={props.compound.total_steps}
              />
              <label className="whitespace-nowrap">
                {props.compound.enum_step}
              </label>
            </div>
          }
        />
      </div>
    </DetailSection>
  );
}
