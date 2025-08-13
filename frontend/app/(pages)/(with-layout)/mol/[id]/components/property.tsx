"use client";
import { Badge, cn, Tooltip } from "@heroui/react";
import { JSX } from "react";
import { MdCheck, MdQuestionMark } from "react-icons/md";

export default function DetailProperty(props: {
  icon: JSX.Element;
  title: string;
  value: string | JSX.Element;
  isValid?: boolean;
  tooltipContent?: JSX.Element | null;
  className?: string;
}) {
  return (
    <div
      className={cn(
        "flex flex-row justify-start items-center gap-4",
        props.className
      )}
    >
      <div className="text-zinc-400">
        {props.isValid === true || props.isValid === false ? (
          <Tooltip content={props.tooltipContent}>
            <Badge
              isOneChar
              size="sm"
              color={props.isValid === true ? "success" : "warning"}
              content={
                props.isValid === true ? (
                  <MdCheck className="text-white" />
                ) : (
                  <MdQuestionMark className="text-white" />
                )
              }
              placement="top-right"
            >
              {props.icon}
            </Badge>
          </Tooltip>
        ) : (
          props.icon
        )}
      </div>
      <div className="flex flex-col gap-0 justify-center">
        <div className="font-bold">{props.title}</div>
        <div>{props.value}</div>
      </div>
    </div>
  );
}
