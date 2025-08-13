"use client";
import DetailSection from "../components/section";
import DetailProperty from "../components/property";
import {
  MdCheckCircleOutline,
  MdDownload,
  MdQuestionMark,
  MdTitle,
} from "react-icons/md";
import { FaBalanceScale } from "react-icons/fa";
import { PiLetterCircleP } from "react-icons/pi";
import IStructure, {
  IIdentifierSource,
  IIdentifierType,
} from "@/lib/api/admin/interfaces/Structure";
import { Badge, Chip, Tooltip } from "@heroui/react";
import { JSX } from "react";
import { FaUser } from "react-icons/fa6";

export function IdentifierSourceTooltip(props: {
  children: JSX.Element;
  item?: IIdentifierSource;
}) {
  if (!props.item) {
    return props.children;
  }

  return (
    <Tooltip
      color="warning"
      content={
        <div className="flex flex-col gap-0.5">
          {props.item.type == "user" ? (
            <span className="text-sm text-foreground-500">
              Manually curated
            </span>
          ) : (
            <>
              <div className="flex flex-row gap-1 items-center text-xs">
                <label className="font-bold">Source:</label>
                <span className="text-foreground-700">
                  {props.item.data.enum_type}
                </span>
              </div>
              <div className="flex flex-row gap-1 items-center text-xs">
                <label className="font-bold">Source ID:</label>
                <span className="text-foreground-700">
                  {props.item.data.value}
                </span>
              </div>
            </>
          )}
        </div>
      }
    >
      <Badge
        isOneChar
        size="sm"
        color="warning"
        content={
          props.item.type == "user" ? (
            <FaUser className="text-white" />
          ) : props.item.type == "identifier" ? (
            <MdDownload className="text-white" />
          ) : (
            <MdQuestionMark className="text-white" />
          )
        }
        placement="top-right"
      >
        {props.children}
      </Badge>
    </Tooltip>
  );
}

export default function CompoundBasicProperties(props: {
  compound: IStructure;
}) {
  return (
    <DetailSection title="Basic properties" order={1}>
      <div className="grid grid-cols-2 gap-4 gap-y-6">
        <DetailProperty
          icon={<MdTitle size={30} />}
          title="Alternative names"
          value={
            <div className="flex flex-row flex-wrap items-center gap-2 pt-1">
              {props.compound.identifiers
                ?.filter((i) => i.type == IIdentifierType.NAME)
                .map((i) => (
                  <IdentifierSourceTooltip item={i.source} key={i.id}>
                    <Chip size="sm" variant="bordered">
                      {i.value}
                    </Chip>
                  </IdentifierSourceTooltip>
                ))}
            </div>
          }
          className="col-span-2"
        />
        <DetailProperty
          tooltipContent={
            props.compound.molecular_weight ? (
              <div className="flex flex-col">
                <div className="flex flex-row justify-start text-xs">
                  RdKit software
                </div>
                <div className="flex flex-row items-center gap-1">
                  <MdCheckCircleOutline className="text-success" />
                  Calculated from <b>SMILES</b>
                </div>
              </div>
            ) : null
          }
          icon={<FaBalanceScale size={30} />}
          isValid={true}
          title="Molecular weight"
          value={
            props.compound.molecular_weight
              ? `${props.compound.molecular_weight} Da`
              : "N/A"
          }
        />
        <DetailProperty
          tooltipContent={
            props.compound.logp ? (
              <div className="flex flex-col">
                <div className="flex flex-row justify-start text-xs">
                  RdKit software
                </div>
                <div className="flex flex-row items-center gap-1">
                  <MdCheckCircleOutline className="text-success" />
                  Calculated from <b>SMILES</b>
                </div>
              </div>
            ) : null
          }
          icon={<PiLetterCircleP size={30} />}
          isValid={true}
          title="LogP"
          value={props.compound.logp ? `${props.compound.logp}` : "N/A"}
        />
      </div>
    </DetailSection>
  );
}
