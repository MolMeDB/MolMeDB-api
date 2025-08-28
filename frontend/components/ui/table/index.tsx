"use client";

import React, { useEffect, useMemo, useState } from "react";
import IUiTableColumn from "./interface/columns";
import { useAsyncList } from "@react-stately/data";
import { getJson } from "@/lib/api/admin";
import FilteredResponse from "@/lib/api/admin/interfaces/http/FilteredResponse";
import {
  addToast,
  Button,
  Chip,
  Input,
  Pagination,
  SortDescriptor,
  Spinner,
  Switch,
  Table,
  TableBody,
  TableCell,
  TableColumn,
  TableHeader,
  TableRow,
} from "@heroui/react";
import { FaEye, FaEyeSlash } from "react-icons/fa6";
import { MdSearch } from "react-icons/md";

function getValueByPath(obj: any, path: string): any {
  return path.split(".").reduce((acc, key) => {
    return acc && acc[key] !== undefined ? acc[key] : undefined;
  }, obj);
}

export default function UiTable<TData>(props: {
  "aria-label"?: string;
  apiUrl: string;
  apiParams?: {
    [key: string]: any;
  };
  columns: IUiTableColumn<TData>[];
  itemKey: keyof TData;
  defaultRowsPerPage?: number;
  hasSearch?: boolean;
  searchPlaceholder?: string;
}) {
  const [hideEmptyCols, setHideEmptyCols] = useState(true);
  const [isLoading, setIsLoading] = useState(false);
  const [page, setPage] = useState(1);
  const [totalItems, setTotalItems] = useState(0);
  const [items, setItems] = useState<TData[]>([]);
  const [query, setQuery] = useState("");
  const [sortBy, setSortBy] = useState<SortDescriptor>({
    column: "",
    direction: "ascending",
  });
  const rowsPerPage = props.defaultRowsPerPage ?? 10;

  let list = useAsyncList({
    async load({ signal }) {
      try {
        setIsLoading(true);
        const response = await getJson(
          props.apiUrl,
          {
            ...props.apiParams,
            page,
            query,
            per_page: rowsPerPage,
            sortBy: props.columns.find(
              (c) => c.key.toString() === sortBy.column.toString()
            )?.sortKey,
            sortByDirection: sortBy.direction === "ascending" ? "asc" : "desc",
          },
          signal
        );

        if (response?.code === 200 && response.data) {
          const fr: FilteredResponse<TData> = response.data;
          setTotalItems(fr.meta.total);
          setItems(fr.data);
          setIsLoading(false);
          return {
            items: fr.data,
          };
        } else {
          addToast({
            title: "Error",
            description: "Failed to load table data. Please, try again.",
            color: "danger",
            shouldShowTimeoutProgress: true,
            timeout: 4500,
          });
          return { items: [] };
        }
      } catch (error) {
        addToast({
          title: "Error",
          description: "Failed to load table data. Please, try again.",
          color: "danger",
          shouldShowTimeoutProgress: true,
          timeout: 4500,
        });
        return { items: [] };
      }
    },
    sort: (a) => {
      setSortBy(a.sortDescriptor);
      return {
        items: a.items,
      };
    },
  });

  const pages = Math.ceil(totalItems / rowsPerPage);

  const tableColumns = useMemo(() => {
    if (!hideEmptyCols) return props.columns;

    return props.columns.filter((column) => {
      if (!column.isHideable) return true;

      return items.some((item: any) => {
        const value = getValueByPath(item, column.key.toString());
        return value !== undefined && value !== null;
      });
    });
  }, [hideEmptyCols, items, props.columns]);

  useEffect(() => {
    list.reload();
  }, [page, rowsPerPage, props.apiParams, sortBy]);

  const onSearchChange = React.useCallback((value?: string) => {
    if (value) {
      setQuery(value);
      setPage(1);
    } else {
      setQuery("");
      list.reload();
    }
  }, []);

  return (
    <Table
      aria-label={props["aria-label"]}
      color="primary"
      sortDescriptor={
        list.sortDescriptor && list.sortDescriptor.column !== undefined
          ? (list.sortDescriptor as {
              column: string;
              direction: "ascending" | "descending";
            })
          : undefined
      }
      onSortChange={list.sort}
      topContent={
        <div className="flex flex-col gap-4">
          {props.hasSearch && (
            <div className="flex flex-row gap-4 w-full h-full">
              <div className="w-1/2">
                <Input
                  className="w-full"
                  placeholder="Search publication..."
                  startContent={<MdSearch />}
                  value={query}
                  onValueChange={onSearchChange}
                  endContent={
                    <Chip
                      size="sm"
                      color="default"
                      onClick={() => {
                        if (page !== 1) setPage(1);
                        else {
                          list.reload();
                        }
                      }}
                    >
                      <MdSearch />
                    </Chip>
                  }
                  onKeyDown={(e) => {
                    if (e.key === "Enter") {
                      if (page !== 1) setPage(1);
                      else {
                        list.reload();
                      }
                    }
                  }}
                />
              </div>
            </div>
          )}
          <div className="flex flex-row justify-between items-center text-foreground/60 text-sm">
            <div>Total: {totalItems}</div>
            {tableColumns.some((c) => c.isHideable === true) && (
              <div className="flex items-center">
                <Switch
                  defaultSelected={hideEmptyCols}
                  color="primary"
                  size="sm"
                  onChange={(e) => setHideEmptyCols(e.target.checked)}
                  thumbIcon={({ isSelected, className }) =>
                    isSelected ? (
                      <FaEyeSlash className={className} />
                    ) : (
                      <FaEye className={className} />
                    )
                  }
                  classNames={{
                    label: "text-sm text-foreground/60",
                  }}
                >
                  Hide empty columns
                </Switch>
              </div>
            )}
          </div>
        </div>
      }
      bottomContent={
        totalItems > 0 && (
          <div className="flex flex-row w-full justify-between">
            <Pagination
              isCompact
              showControls
              showShadow
              color="primary"
              page={page}
              total={pages}
              onChange={(page) => {
                setPage(page);
              }}
            />
          </div>
        )
      }
    >
      <TableHeader columns={tableColumns}>
        {(column) => (
          <TableColumn
            allowsSorting={column.isSortable && column.sortKey !== undefined}
            key={column.key.toString()}
          >
            {column.title}
          </TableColumn>
        )}
      </TableHeader>
      <TableBody
        items={items}
        isLoading={isLoading}
        loadingContent={
          <div className="flex flex-row justify-center items-center bg-background/70 dark:bg-background-dark-2/70 w-full h-full z-30">
            <Spinner size="lg" variant="wave" color="warning" />
          </div>
        }
        emptyContent={"Start by selecting the membranes and methods."}
      >
        {(item: TData) => (
          <TableRow key={item[props.itemKey]?.toString()}>
            {(columnKey) => (
              <TableCell>
                {props.columns.find((c) => c.key === columnKey)?.render(item)}
              </TableCell>
            )}
          </TableRow>
        )}
      </TableBody>
    </Table>
  );
}
