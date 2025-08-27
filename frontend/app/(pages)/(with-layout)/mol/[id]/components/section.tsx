import { cn } from "@heroui/react";
import React from "react";

export default function DetailSection(props: {
  title: string;
  order: number;
  endContent?: React.JSX.Element;
  children: React.JSX.Element;
}) {
  return (
    <div className="flex flex-col gap-4">
      <Title
        content={props.title}
        order={props.order}
        endContent={props.endContent}
      />
      {props.children}
    </div>
  );
}

function Title(props: {
  content: string;
  order?: number;
  endContent?: React.JSX.Element;
}) {
  return (
    <div className={cn("flex flex-col gap-2")}>
      <div className="flex flex-row justify-between items-center">
        <div className="flex flex-row gap-3">
          <h2 className="text-xl font-bold text-secondary dark:text-indigo-300 uppercase">
            {props.order}
            {"."}
          </h2>
          <h2 className="text-xl font-bold text-secondary dark:text-indigo-300 uppercase">
            {props.content}
          </h2>
        </div>
        {props.endContent}
      </div>
      <div className="h-1.5 w-full bg-gradient-to-r from-secondary dark:from-indigo-300 to-transparent rounded-full" />
    </div>
  );
}
