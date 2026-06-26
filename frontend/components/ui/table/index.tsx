"use client";

import React, { useEffect, useMemo, useRef, useState } from "react";
import IUiTableColumn from "./interface/columns";
import { useAsyncList } from "@react-stately/data";
import { getJson } from "@/lib/api/admin";
import FilteredResponse from "@/lib/api/admin/interfaces/http/FilteredResponse";
import {
  addToast,
  Button,
  Chip,
  CircularProgress,
  Input,
  Pagination,
  Select,
  SelectItem,
  SortDescriptor,
  Spinner,
  Switch,
  Table,
  TableBody,
  TableCell,
  TableColumn,
  TableHeader,
  TableRow,
  Tooltip,
} from "@heroui/react";
import { FaEye, FaEyeSlash } from "react-icons/fa6";
import { MdSearch } from "react-icons/md";
import { useHandle401 } from "@/lib/api/admin/redirections";

type UiTableFilterOption = {
  label: string;
  value: string | number;
};

type UiTableFilter =
  | {
      key: string;
      type: "text";
      label?: string;
      placeholder?: string;
      param?: string;
    }
  | {
      key: string;
      type: "select";
      label?: string;
      placeholder?: string;
      param?: string;
      multiple?: boolean;
      options: UiTableFilterOption[];
    };

type UiTableFilterValues = Record<string, string | Set<string>>;

function getValueByPath(obj: any, path: string): any {
  return path.split(".").reduce((acc, key) => {
    return acc && acc[key] !== undefined ? acc[key] : undefined;
  }, obj);
}

function serializeFilterValues(values: UiTableFilterValues) {
  return Object.fromEntries(
    Object.entries(values).map(([key, value]) => [
      key,
      value instanceof Set ? Array.from(value).join(",") : value,
    ]),
  );
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
  loadingText?: string;
  filters?: UiTableFilter[];
  onTotalItemsChange?: (totalItems: number) => void;
  onDataLoaded?: () => void;
  refreshInterval?: number;
}) {
  const [hideEmptyCols, setHideEmptyCols] = useState(true);
  const [isLoading, setIsLoading] = useState(false);
  const [page, setPage] = useState(1);
  const [totalItems, setTotalItems] = useState(0);
  const [items, setItems] = useState<TData[]>([]);
  const [query, setQuery] = useState("");
  const [filterValues, setFilterValues] = useState<UiTableFilterValues>({});
  const [pendingTextFilters, setPendingTextFilters] = useState<
    Record<string, string>
  >({});
  const [textFilterProgress, setTextFilterProgress] = useState<
    Record<string, number>
  >({});
  const [sortBy, setSortBy] = useState<SortDescriptor>({
    column: "",
    direction: "ascending",
  });
  const [countdown, setCountdown] = useState(props.refreshInterval ?? 0);
  const countdownRef = useRef(props.refreshInterval ?? 0);
  const rowsPerPage = props.defaultRowsPerPage ?? 10;
  const hasFilters = (props.filters?.length ?? 0) > 0;

  const handle401 = useHandle401();

  let list = useAsyncList({
    async load({ signal }) {
      try {
        setIsLoading(true);
        props.onTotalItemsChange?.(0);
        const response = await getJson(
          props.apiUrl,
          {
            ...props.apiParams,
            page,
            query,
            ...serializeFilterValues(filterValues),
            per_page: rowsPerPage,
            sortBy: props.columns.find(
              (c) => c.key.toString() === sortBy.column.toString(),
            )?.sortKey,
            sortByDirection: sortBy.direction === "ascending" ? "asc" : "desc",
          },
          signal,
        );

        if(response?.code === 401) {
          handle401();
        }

        if (response?.code === 200 && response.data) {
          const fr: FilteredResponse<TData> = response.data;
          setTotalItems(fr.meta.total);
          props.onTotalItemsChange?.(fr.meta.total);
          setItems(fr.data);
          setIsLoading(false);
          props.onDataLoaded?.();
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
          props.onTotalItemsChange?.(0);
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
        props.onTotalItemsChange?.(0);
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
  }, [page, rowsPerPage, props.apiParams, sortBy, filterValues]);

  const onSearchChange = React.useCallback((value?: string) => {
    if (value) {
      setQuery(value);
      setPage(1);
    } else {
      setQuery("");
      list.reload();
    }
  }, []);

  const updateFilterValue = React.useCallback(
    (filter: UiTableFilter, value: string | Set<string>) => {
      const param = filter.param ?? filter.key;

      setFilterValues((current) => {
        const next = { ...current };
        const isEmptySet = value instanceof Set && value.size === 0;

        if (value === "" || isEmptySet) {
          delete next[param];
        } else {
          next[param] = value;
        }

        return next;
      });
      setPage(1);
    },
    [],
  );

  const updateTextFilterValue = React.useCallback(
    (filter: UiTableFilter, value: string) => {
      const param = filter.param ?? filter.key;

      setPendingTextFilters((current) => ({
        ...current,
        [param]: value,
      }));
      setTextFilterProgress((current) => ({
        ...current,
        [param]: 100,
      }));
    },
    [],
  );

  const clearFilters = React.useCallback(() => {
    setFilterValues({});
    setPendingTextFilters({});
    setTextFilterProgress({});
    setQuery("");
    setPage(1);
  }, []);

  useEffect(() => {
    if (Object.keys(pendingTextFilters).length === 0) {
      return;
    }

    const startedAt = Date.now();
    const pendingParams = Object.keys(pendingTextFilters);
    const duration = 1000;

    const intervalId = window.setInterval(() => {
      const elapsed = Date.now() - startedAt;
      const progress = Math.max(
        0,
        Math.round(100 - (elapsed / duration) * 100),
      );

      setTextFilterProgress((current) => {
        const next = { ...current };

        pendingParams.forEach((param) => {
          next[param] = progress;
        });

        return next;
      });
    }, 50);

    const timeoutId = window.setTimeout(() => {
      setFilterValues((current) => {
        const next = { ...current };

        Object.entries(pendingTextFilters).forEach(([param, value]) => {
          if (value === "") {
            delete next[param];
          } else {
            next[param] = value;
          }
        });

        return next;
      });
      setPendingTextFilters({});
      setTextFilterProgress((current) => {
        const next = { ...current };

        pendingParams.forEach((param) => {
          delete next[param];
        });

        return next;
      });
      setPage(1);
    }, duration);

    return () => {
      window.clearInterval(intervalId);
      window.clearTimeout(timeoutId);
    };
  }, [pendingTextFilters]);

  useEffect(() => {
    if (!props.refreshInterval) return;

    countdownRef.current = props.refreshInterval;
    setCountdown(props.refreshInterval);

    const id = window.setInterval(() => {
      countdownRef.current -= 1;
      setCountdown(countdownRef.current);

      if (countdownRef.current <= 0) {
        countdownRef.current = props.refreshInterval!;
        setCountdown(props.refreshInterval!);
        list.reload();
      }
    }, 1000);

    return () => window.clearInterval(id);
  }, [props.refreshInterval]);

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
          {(props.hasSearch || hasFilters) && (
            <div className="flex flex-col gap-3 md:flex-row md:flex-wrap md:items-center w-full h-full">
              {props.hasSearch && (
                <div className="w-full md:w-1/2">
                  <Input
                    className="w-full"
                    placeholder={props.searchPlaceholder ?? "Search..."}
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
              )}
              {props.filters?.map((filter) => {
                const param = filter.param ?? filter.key;

                if (filter.type === "text") {
                  return (
                    <Input
                      key={filter.key}
                      className="w-full md:w-72"
                      label={filter.label}
                      placeholder={filter.placeholder ?? "Search..."}
                      startContent={<MdSearch />}
                      value={
                        pendingTextFilters[param] ??
                        (typeof filterValues[param] === "string"
                          ? filterValues[param]
                          : "")
                      }
                      onValueChange={(value) =>
                        updateTextFilterValue(filter, value)
                      }
                      endContent={
                        pendingTextFilters[param] !== undefined ? (
                          <CircularProgress
                            aria-label="Search debounce progress"
                            color="success"
                            disableAnimation
                            isIndeterminate={false}
                            maxValue={100}
                            minValue={0}
                            size="sm"
                            strokeWidth={4}
                            value={textFilterProgress[param] ?? 100}
                            classNames={{
                              base: "h-4 w-4",
                              svg: "h-4 w-4",
                            }}
                          />
                        ) : undefined
                      }
                    />
                  );
                }

                const selectedKeys =
                  filterValues[param] instanceof Set
                    ? (filterValues[param] as Set<string>)
                    : new Set<string>();

                return (
                  <Select
                    key={filter.key}
                    className="w-full md:w-64"
                    label={filter.label}
                    placeholder={filter.placeholder ?? "Select..."}
                    selectionMode={filter.multiple ? "multiple" : "single"}
                    selectedKeys={selectedKeys}
                    onSelectionChange={(keys) => {
                      if (keys === "all") {
                        updateFilterValue(
                          filter,
                          new Set(
                            filter.options.map((option) =>
                              option.value.toString(),
                            ),
                          ),
                        );

                        return;
                      }

                      updateFilterValue(
                        filter,
                        new Set(Array.from(keys).map((key) => key.toString())),
                      );
                    }}
                  >
                    {filter.options.map((option) => (
                      <SelectItem
                        key={option.value.toString()}
                        textValue={option.label}
                      >
                        {option.label}
                      </SelectItem>
                    ))}
                  </Select>
                );
              })}
              {hasFilters && (
                <Button
                  size="sm"
                  variant="flat"
                  onPress={clearFilters}
                  color="warning"
                >
                  Clear
                </Button>
              )}
            </div>
          )}
          <div className="flex flex-row justify-between items-center text-foreground/60 text-sm">
            <div>Total: {totalItems}</div>
            <div className="flex items-center gap-3">
              {tableColumns.some((c) => c.isHideable === true) && (
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
              )}
              {props.refreshInterval && (
                <Tooltip
                  content={`Auto-refresh every ${props.refreshInterval}s`}
                  placement="left"
                >
                  <div className="flex items-center gap-1.5 cursor-default select-none">
                    <CircularProgress
                      aria-label="Auto-refresh countdown"
                      size="sm"
                      value={(countdown / props.refreshInterval) * 100}
                      color="primary"
                      isIndeterminate={false}
                      disableAnimation
                      strokeWidth={4}
                      classNames={{ base: "h-5 w-5", svg: "h-5 w-5" }}
                    />
                    <span className="text-xs tabular-nums w-5 text-center">
                      {countdown}s
                    </span>
                  </div>
                </Tooltip>
              )}
            </div>
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
          <div className="flex flex-col gap-2 justify-center items-center bg-background/70 dark:bg-background-dark-2/70 w-full h-full z-30">
            <Spinner size="lg" variant="wave" color="warning" />
            {props.loadingText && <div>{props.loadingText}</div>}
          </div>
        }
        emptyContent={"No data..."}
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
