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
import { redirect } from "next/navigation";

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
            {!item.comment
              ? "N/A"
              : item.comment?.length < 40
                ? (item.comment ?? "")
                : item.comment?.substring(0, 40) + "..."}
          </div>
        </PopoverTrigger>
        <PopoverContent>
          <div className="max-w-lg">{item.comment}</div>
        </PopoverContent>
      </Popover>
    ),
    isSortable: true,
    sortKey: "comment",
  },
  {
    key: "author",
    title: "Author",
    render: (item) => item.user?.name,
    isSortable: true,
    sortKey: "author",
  },
  {
    key: "state",
    title: "State",
    render: (item) => {
      if (
        item.stats.running > 0 ||
        (item.stats.done > 0 && item.stats.pending > 0)
      ) {
        return (
          <Chip variant="bordered" color="success" className="text-xs">
            Running
          </Chip>
        );
      }
      if (
        item.stats.pending > 0 ||
        (!item.stats.done && !item.stats.running && !item.stats.failed)
      ) {
        return (
          <Chip variant="flat" color="warning" className="text-xs">
            Pending
          </Chip>
        );
      }

      if (item.stats.done > 0) {
        return (
          <Chip variant="flat" color="success" className="text-xs">
            Done
          </Chip>
        );
      }
    },
    isSortable: true,
    sortKey: "state",
  },
  {
    key: "stats",
    title: "Progress",
    render: (item) => (
      <div>
        {item.stats.done + item.stats.running} / {item.stats.total}
      </div>
    ),
    isSortable: false,
  },
  {
    key: "last_update_at",
    title: "Updated at",
    render: (item) => item.updated_at,
    isSortable: true,
    sortKey: "last_update_at",
  },
  {
    key: "created_at",
    title: "Created at",
    render: (item) => item.created_at,
    isSortable: true,
    sortKey: "created_at",
  },
  {
    key: "actions",
    title: "Actions",
    render: (item) => (
      <div className="relative flex items-center w-full gap-2">
        <Tooltip content="Details">
          <span className="text-lg text-default-400 cursor-pointer active:opacity-50">
            <EyeIcon
              onClick={() => {
                redirect(`/lab/running-predictions/${item.id}`);
              }}
            />
          </span>
        </Tooltip>
      </div>
    ),
  },
];
