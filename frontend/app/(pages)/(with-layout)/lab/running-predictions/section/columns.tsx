import IUiTableColumn from "@/components/ui/table/interface/columns";
import {
  Chip,
  Popover,
  PopoverContent,
  PopoverTrigger,
  Tooltip,
} from "@heroui/react";
import { IPredictionDataset } from "@/lib/api/admin/interfaces/Predictions";
import { EyeIcon } from "@/components/ui/icons/eye";
import Link from "next/link";

function formatDateTime(value: string) {
  const match = value.match(
    /^(\d{4})\/(\d{2})\/(\d{2}) (\d{2}):(\d{2})/,
  );

  if (!match) {
    return value;
  }

  const [, year, month, day, hour, minute] = match;

  return `${day}. ${month}. ${year} ${hour}:${minute}`;
}

export const datasetColumns: IUiTableColumn<IPredictionDataset>[] = [
  {
    key: "id",
    title: "ID",
    render: (item) => item.id,
    sortKey: "id",
    isSortable: true,
  },
  {
    key: "comment",
    title: "Comment",
    render: (item) => (
      <Popover color="secondary" placement="bottom" showArrow={true}>
        <PopoverTrigger>
          <div className="line-clamp-2 max-w-lg font-semibold cursor-default">
            {item.comment ? item.comment : "N/A"}
          </div>
        </PopoverTrigger>
        <PopoverContent>
          <div className="max-w-lg">{item.comment || "No comment"}</div>
        </PopoverContent>
      </Popover>
    ),
    isSortable: true,
    sortKey: "comment",
  },
  {
    key: "author",
    title: "Author",
    render: (item) =>
      item.user?.name ??
      item.user?.email ??
      (item.user_id ? `User #${item.user_id}` : "N/A"),
    isSortable: true,
    sortKey: "author",
  },
  {
    key: "state",
    title: "State",
    render: (item) => {
      const { enum_state } = item;

      if (enum_state === "In progress") {
        return (
          <Chip variant="bordered" color="primary" className="text-xs">
            {enum_state}
          </Chip>
        );
      }

      if (enum_state === "Failed") {
        return (
          <Chip variant="flat" color="danger" className="text-xs">
            {enum_state}
          </Chip>
        );
      }

      if (enum_state === "Finished with errors") {
        return (
          <Chip variant="flat" color="warning" className="text-xs">
            {enum_state}
          </Chip>
        );
      }

      if (enum_state === "Pending") {
        return (
          <Chip variant="flat" color="warning" className="text-xs">
            {enum_state}
          </Chip>
        );
      }

      if (enum_state === "Finished") {
        return (
          <Chip variant="flat" color="success" className="text-xs">
            {enum_state}
          </Chip>
        );
      }

      return enum_state ?? "N/A";
    },
    isSortable: true,
    sortKey: "state",
  },
  {
    key: "stats",
    title: "Completed",
    render: (item) => (
      <div>
        {item.stats.done + item.stats.failed} / {item.stats.total || 0}
      </div>
    ),
    isSortable: false,
  },
  // {
  //   key: "last_update_at",
  //   title: "Updated at",
  //   render: (item) => formatDateTime(item.updated_at),
  //   isSortable: true,
  //   sortKey: "last_update_at",
  // },
  {
    key: "created_at",
    title: "Created at",
    render: (item) => formatDateTime(item.created_at),
    isSortable: true,
    sortKey: "created_at",
  },
  {
    key: "actions",
    title: "Actions",
    render: (item) => (
      <div className="relative flex items-center w-full gap-2">
        <Tooltip content="Details">
          <Link
            href={`/lab/running-predictions/${item.id}`}
            className="text-lg text-default-400 cursor-pointer active:opacity-50"
          >
            <EyeIcon />
          </Link>
        </Tooltip>
      </div>
    ),
  },
];
