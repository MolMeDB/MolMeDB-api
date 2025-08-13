"use client";

import { ISelectItem } from "@/lib/api/admin/interfaces/SelectData";
import {
  Chip,
  Select,
  SelectedItems,
  SelectItem,
  SelectSection,
} from "@heroui/react";
import { useEffect, useState } from "react";

export default function FilterSelect(props: {
  title: string;
  items: ISelectItem[];
  selectedItems: Set<string>;
  onChange: (allIds: Set<string>, selectedIds: Set<string>) => void;
}) {
  const [allIds, setAllIds] = useState<Set<string>>(new Set());
  const [selectedIds, setSelectedIds] = useState<Set<string>>(
    new Set(props.selectedItems)
  );

  useEffect(() => {
    setSelectedIds(new Set(props.selectedItems).intersection(allIds));
  }, [props.selectedItems]);

  useEffect(() => {
    const extractAllItemValues = (items: any[]): string[] => {
      return items.flatMap((item) => {
        if (item.type === "item") {
          return [item.value.toString()];
        } else if (item.type === "category" && Array.isArray(item.children)) {
          return item.children
            .filter((child: ISelectItem) => child.type === "item")
            .map((child: any) => child.value.toString());
        } else {
          return [];
        }
      });
    };

    const allValues = extractAllItemValues(props.items);
    setAllIds(new Set(allValues));
  }, [props.items]);

  return (
    <Select
      classNames={{
        base: "min-w-40 max-w-44",
        trigger: "min-h-8 py-2",
      }}
      aria-label={props.title}
      selectionMode="multiple"
      isClearable
      placeholder={props.title}
      selectedKeys={selectedIds}
      onSelectionChange={(e) => {
        props.onChange(
          allIds,
          new Set(Array.from(e).map((item) => item.toString()))
        );
      }}
      renderValue={(items: SelectedItems) => {
        return (
          <div className="flex flex-row items-center gap-2">
            <label>{props.title}</label>
            <Chip color="warning" size="sm" variant="flat">
              {items.length}
            </Chip>
          </div>
        );
      }}
    >
      {props.items.map((cat, index) => {
        return cat.type == "category" ? (
          <SelectSection key={index} title={cat.category}>
            {cat.children.map((item, index) => {
              return item.type == "item" ? (
                <SelectItem key={item.value}>{item.label}</SelectItem>
              ) : null;
            })}
          </SelectSection>
        ) : (
          <SelectItem key={cat.value}>{cat.label}</SelectItem>
        );
      })}
    </Select>
  );
}
