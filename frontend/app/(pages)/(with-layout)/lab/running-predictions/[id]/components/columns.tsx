import IUiTableColumn from "@/components/ui/table/interface/columns";
import {
  Chip,
  Popover,
  PopoverContent,
  PopoverTrigger,
  Progress,
} from "@heroui/react";
import { IPrediction } from "@/lib/api/admin/interfaces/Predictions";
import { FaHeart, FaRegHeart } from "react-icons/fa6";
import { timeAgo } from "@/lib/timeAgo";
import {
  getRemoteStatusColor,
  getRemoteStatusLabel,
} from "@/lib/predictionRemoteStatus";

function latestTimestamp(
  a: string | null | undefined,
  b: string | null | undefined,
): string {
  const vals = [a, b].filter(Boolean) as string[];
  if (vals.length === 0) return "-";
  return vals.reduce((latest, t) => (t > latest ? t : latest));
}

function getProgressColor(
  item: IPrediction,
): "danger" | "success" | "warning" | "default" {
  if (["Error", "Remove", "Stopped"].includes(item.enum_state)) {
    return "danger";
  }

  if (item.progress_percent >= 100) {
    return "success";
  }

  if (item.enum_state === "Running") {
    return "warning";
  }

  return "default";
}

export const datasetColumns: IUiTableColumn<IPrediction>[] = [
  {
    key: "id",
    title: "ID",
    render: (item) => item.structure.id,
    sortKey: "id",
    isSortable: true,
  },
  {
    key: "structure",
    title: "Canonical smiles",
    render: (item) => {
      return (
        <Popover>
          <PopoverTrigger>
            <span className="line-clamp-2 max-w-xs cursor-default">
              {item.structure.canonical_smiles}
            </span>
          </PopoverTrigger>
          <PopoverContent>
            <div className="max-w-md break-all">
              {item.structure.canonical_smiles}
            </div>
          </PopoverContent>
        </Popover>
      );
    },
    isSortable: false,
  },
  {
    key: "progress",
    title: "Progress",
    render: (item) => (
      <Popover>
        <PopoverTrigger>
          <Progress
            className="cursor-pointer"
            color={getProgressColor(item)}
            value={item.progress_percent}
            minValue={0}
            maxValue={100}
          />
        </PopoverTrigger>
        <PopoverContent>
          {item.enum_state}: {item.enum_step} ({item.progress_percent}%)
        </PopoverContent>
      </Popover>
    ),
    isSortable: true,
    sortKey: "step",
  },
  {
    key: "remote_status",
    title: "Remote status",
    render: (item) => (
      <div className="flex flex-col gap-1">
        <Chip size="sm" variant="flat" color={getRemoteStatusColor(item)}>
          {getRemoteStatusLabel(item)}
        </Chip>
        {item.remote_error_message && (
          <span className="text-xs text-danger line-clamp-2">
            {item.remote_error_message}
          </span>
        )}
      </div>
    ),
    isSortable: false,
  },
  {
    key: "remote_heartbeat_at",
    title: "Heartbeat",
    render: (item) => (
      <div className="flex items-center gap-1.5">
        {item.remote_heartbeat_at ? (
          <span className="animate-heartbeat inline-flex shrink-0 text-danger">
            <FaHeart size={12} />
          </span>
        ) : (
          <span className="inline-flex shrink-0 text-default-400">
            <FaRegHeart size={12} />
          </span>
        )}
        {item.remote_heartbeat_at && <span>{timeAgo(item.remote_heartbeat_at)}</span>}
      </div>
    ),
    isSortable: false,
  },
  {
    key: "last_update_at",
    title: "Last update",
    render: (item) => (
      <div className="flex flex-col">
        <span>
          {timeAgo(latestTimestamp(item.updated_at, item.remote_last_status_at))}
        </span>
        <span className="text-xs text-default-400">
          Status checked max every 5 min
        </span>
      </div>
    ),
    isSortable: true,
    sortKey: "last_update_at",
  },
];
