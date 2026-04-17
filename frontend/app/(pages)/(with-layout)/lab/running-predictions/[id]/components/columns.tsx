import IUiTableColumn from "@/components/ui/table/interface/columns";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
  Progress,
} from "@heroui/react";
import { IPrediction } from "@/lib/api/admin/interfaces/Predictions";

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
    key: "last_update_at",
    title: "Updated at",
    render: (item) => item.updated_at,
    isSortable: true,
    sortKey: "last_update_at",
  },
];
