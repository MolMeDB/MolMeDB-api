import { Popover, PopoverContent, PopoverTrigger } from "@heroui/react";
import { useMemo } from "react";

type Props = {
  pending: number;
  running: number;
  done: number;
  error: number;
  height?: number;
};

const colors = {
  pending: "bg-primary",
  running: "bg-warning",
  done: "bg-success",
  error: "bg-danger",
};

export default function PredictionDatasetStateBar({
  pending,
  running,
  done,
  error,
  height = 12,
}: Props) {
  const total = pending + running + done + error;

  const percentages = useMemo(() => {
    if (total === 0) {
      return {
        pending: 0,
        running: 0,
        done: 0,
        error: 0,
      };
    }

    return {
      pending: (pending / total) * 100,
      running: (running / total) * 100,
      done: (done / total) * 100,
      error: (error / total) * 100,
    };
  }, [pending, running, done, error, total]);

  const segments = [
    {
      key: "pending",
      value: percentages.pending,
      total: pending,
      color: colors.pending,
    },
    {
      key: "running",
      value: percentages.running,
      total: running,
      color: colors.running,
    },
    { key: "done", value: percentages.done, total: done, color: colors.done },
    {
      key: "error",
      value: percentages.error,
      total: error,
      color: colors.error,
    },
  ];

  return (
    <div
      className="w-full bg-gray-200 rounded-full overflow-hidden"
      style={{ height }}
    >
      <div className="flex w-full h-full">
        {segments.map(
          (segment) =>
            segment.value > 0 && (
              <Popover key={segment.key} showArrow={true}>
                <PopoverTrigger>
                  <div
                    className={`
                  ${segment.color}
                  transition-all duration-700 ease-in-out cursor-pointer
                `}
                    style={{ width: `${segment.value}%` }}
                  />
                </PopoverTrigger>
                <PopoverContent className={`${segment.color} text-white`}>
                  {Math.round(segment.total)}/{total}
                </PopoverContent>
              </Popover>
            ),
        )}
      </div>
    </div>
  );
}

export function PredictionDatasetStateHelper(props: {
  name: string;
  type: "pending" | "running" | "done" | "error";
}) {
  return (
    <div className="flex flex-row items-center gap-2">
      <div className={`w-2 h-2 rounded-full ${colors[props.type]}`} />
      <span className={`text-sm`}>{props.name}</span>
    </div>
  );
}
