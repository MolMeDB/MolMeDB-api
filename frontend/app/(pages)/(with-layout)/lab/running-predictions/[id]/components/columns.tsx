import IUiTableColumn from "@/components/ui/table/interface/columns";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
  Progress,
} from "@heroui/react";
import { IPrediction } from "@/lib/api/admin/interfaces/Predictions";

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
            {item.structure.canonical_smiles.length > 30
              ? item.structure.canonical_smiles.substring(0, 30) + "..."
              : item.structure.canonical_smiles}
          </PopoverTrigger>
          <PopoverContent>test</PopoverContent>
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
            color={
              item.step == 0
                ? "default"
                : item.step == item.total_steps
                  ? "success"
                  : "warning"
            }
            value={item.step}
            minValue={0}
            maxValue={item.total_steps}
          />
        </PopoverTrigger>
        <PopoverContent>{item.enum_step}</PopoverContent>
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
