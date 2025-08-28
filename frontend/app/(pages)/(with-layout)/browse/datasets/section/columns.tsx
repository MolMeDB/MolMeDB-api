import IUiTableColumn from "@/components/ui/table/interface/columns";
import IPublication from "@/lib/api/admin/interfaces/Publication";
import {
  Button,
  Chip,
  Popover,
  PopoverContent,
  PopoverTrigger,
  Tooltip,
} from "@heroui/react";
import { EyeIcon } from "./table";
import { FaExternalLinkAlt } from "react-icons/fa";
import Link from "next/link";

export const datasetColumns: IUiTableColumn<IPublication>[] = [
  {
    key: "authors",
    title: "Authors",
    render: (item) => {
      const ars = item.authors
        .map(
          (author) =>
            author.full_name ??
            `${author.first_name ?? ""} ${author.last_name ?? ""}`
        )
        .map((author) => author.trim())
        .join(", ");
      return (
        <Popover placement="bottom" showArrow={true}>
          <PopoverTrigger>
            <div className="line-clamp-2 max-w-xs cursor-default">{ars}</div>
          </PopoverTrigger>
          <PopoverContent>
            <div className="max-w-xs">{ars}</div>
          </PopoverContent>
        </Popover>
      );
    },
  },
  {
    key: "title",
    title: "Title",
    render: (item) => (
      <Popover color="secondary" placement="bottom" showArrow={true}>
        <PopoverTrigger>
          <div className="line-clamp-2 max-w-lg font-semibold cursor-default">
            {item.title}
          </div>
        </PopoverTrigger>
        <PopoverContent>
          <div className="max-w-lg">{item.title}</div>
        </PopoverContent>
      </Popover>
    ),
    isSortable: true,
    sortKey: "title",
  },
  {
    key: "journal",
    title: "Journal",
    render: (item) => item.journal,
    isSortable: true,
    sortKey: "journal",
  },
  {
    key: "year",
    title: "Year",
    render: (item) =>
      item.year && (
        <Chip variant="flat" color="warning" className="text-xs">
          {item.year}
        </Chip>
      ),
    isSortable: true,
    sortKey: "year",
  },
  {
    key: "link",
    title: "Link",
    render: (item) =>
      item.identifier.value && (
        <Popover placement="bottom" showArrow={true}>
          <PopoverTrigger>
            <Button
              size="sm"
              variant="flat"
              color="secondary"
              className="cursor-default"
            >
              {item.identifier.value ?? "Link"}
            </Button>
          </PopoverTrigger>
          <PopoverContent className="p-2">
            <div className="flex flex-col gap-1 text-xs">
              <div className="flex flex-row gap-1">
                {item.identifier.source_name && (
                  <label className="font-semibold text-secondary">
                    {item.identifier.source_name}:
                  </label>
                )}
                <span>{item.identifier.value}</span>
              </div>
              {item.doi && (
                <Button
                  size="sm"
                  endContent={<FaExternalLinkAlt />}
                  as={Link}
                  href={`https://doi.org/${item.doi}`}
                  target="_blank"
                >
                  Article
                </Button>
              )}
            </div>
          </PopoverContent>
        </Popover>
      ),
    isSortable: false,
  },
];
