"use client";
import IPublication from "@/lib/api/admin/interfaces/Publication";
import {
  addToast,
  Button,
  Chip,
  Input,
  Modal,
  ModalContent,
  Pagination,
  Popover,
  PopoverContent,
  PopoverTrigger,
  Spinner,
  Table,
  TableBody,
  TableCell,
  TableColumn,
  TableHeader,
  TableRow,
  Tooltip,
  useDisclosure,
} from "@heroui/react";
import { TableColumns } from "./table/columns";
import React, { Key, useCallback, useEffect, useRef, useState } from "react";
import FilteredResponse from "@/lib/api/admin/interfaces/http/FilteredResponse";
import { getJson } from "@/lib/api/admin";
import Link from "next/link";
import { FaExternalLinkAlt } from "react-icons/fa";
import { MdSearch } from "react-icons/md";
import { IconBaseProps } from "react-icons";
import PublicationModalContent from "./table/modalContent";
import { useSearchParams } from "next/navigation";

export const EyeIcon = (props: IconBaseProps) => {
  return (
    <svg
      aria-hidden="true"
      fill="none"
      focusable="false"
      height="1em"
      role="presentation"
      viewBox="0 0 20 20"
      width="1em"
      {...props}
    >
      <path
        d="M12.9833 10C12.9833 11.65 11.65 12.9833 10 12.9833C8.35 12.9833 7.01666 11.65 7.01666 10C7.01666 8.35 8.35 7.01666 10 7.01666C11.65 7.01666 12.9833 8.35 12.9833 10Z"
        stroke="currentColor"
        strokeLinecap="round"
        strokeLinejoin="round"
        strokeWidth={1.5}
      />
      <path
        d="M9.99999 16.8916C12.9417 16.8916 15.6833 15.1583 17.5917 12.1583C18.3417 10.9833 18.3417 9.00831 17.5917 7.83331C15.6833 4.83331 12.9417 3.09998 9.99999 3.09998C7.05833 3.09998 4.31666 4.83331 2.40833 7.83331C1.65833 9.00831 1.65833 10.9833 2.40833 12.1583C4.31666 15.1583 7.05833 16.8916 9.99999 16.8916Z"
        stroke="currentColor"
        strokeLinecap="round"
        strokeLinejoin="round"
        strokeWidth={1.5}
      />
    </svg>
  );
};

/**
 * Prepinac - Search, Stats
 *  - STATS
 * *  - Grafy: by year, by journal, by number of interactions/substances
 *
 */

export default function SectionTable() {
  const [data, setData] = React.useState<FilteredResponse<IPublication> | null>(
    null as any
  );
  const [isLoading, setIsLoading] = useState(false);
  const [detailId, setDetailId] = useState(0);
  const [page, setPage] = React.useState(1);
  const [rowsPerPage, setRowsPerPage] = useState(10);
  const [filterValue, setFilterValue] = React.useState("");
  const isFirst = useRef(true);
  const {
    isOpen: isOpenDetail,
    onOpen: onOpenDetail,
    onOpenChange: onOpenChangeDetail,
  } = useDisclosure();

  const pages = data ? Math.ceil(data.meta.total / rowsPerPage) : 0;

  const searchParams = useSearchParams();

  useEffect(() => {
    const id = searchParams.get("id");
    setFilterValue(id ? `ID:${id}` : "");
  }, [searchParams]);

  // page efekt
  useEffect(() => {
    if (isFirst.current) return;
    reloadData();
  }, [page]);

  // filterValue efekt
  useEffect(() => {
    if (isFirst.current) {
      isFirst.current = false;
      reloadData();
      return;
    }

    if (filterValue.length > 2 || filterValue.length === 0) {
      reloadData();
    }
  }, [filterValue]);

  const onSearchChange = React.useCallback((value?: string) => {
    if (value) {
      setFilterValue(value);
      setPage(1);
    } else {
      setFilterValue("");
    }
  }, []);

  const onClear = React.useCallback(() => {
    setFilterValue("");
    setPage(1);
  }, []);

  const reloadData = async () => {
    setIsLoading(true);
    getJson("/api/publication", {
      perPage: rowsPerPage,
      page: page,
      query: filterValue,
    }).then((response) => {
      setIsLoading(false);
      if (!response || response?.code !== 200) {
        addToast({
          title: "Error",
          description: "Failed to load publications data. Please, try again.",
          color: "danger",
          shouldShowTimeoutProgress: true,
          timeout: 4500,
        });
        return;
      }

      setData(response.data as FilteredResponse<IPublication>);
    });
  };

  const renderCell = useCallback((item: IPublication, columnKey: Key) => {
    switch (columnKey) {
      case "authors":
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
      case "identifier":
        return (
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
        );
      case "year":
        return (
          <Chip variant="flat" color="warning" className="text-xs">
            {item.year}
          </Chip>
        );
      case "actions":
        return (
          <div className="relative flex items-center w-full gap-2">
            <Tooltip content="Details">
              <span className="text-lg text-default-400 cursor-pointer active:opacity-50">
                <EyeIcon
                  onClick={() => {
                    onOpenDetail();
                    setDetailId(item.id);
                  }}
                />
              </span>
            </Tooltip>
          </div>
        );
      case "title":
        return (
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
        );
      default:
        return item[columnKey as keyof IPublication]?.toString();
    }
  }, []);

  return (
    <div>
      <Table
        removeWrapper={false}
        aria-label="Example table with dynamic content"
        bottomContent={
          <div className="flex w-full justify-center">
            <Pagination
              isCompact
              showControls
              showShadow
              color="secondary"
              page={page}
              total={pages}
              onChange={(page) => setPage(page)}
            />
          </div>
        }
        topContent={
          <div className="flex flex-col justify-between w-full gap-6">
            <div className="flex flex-row gap-4 w-full h-full">
              <div className="w-1/2">
                <Input
                  isClearable
                  className="w-full"
                  placeholder="Search publication..."
                  startContent={<MdSearch />}
                  value={filterValue}
                  onClear={() => onClear()}
                  onValueChange={onSearchChange}
                />
              </div>
            </div>
            <div className="flex flex-row justify-between gap-4 text-xs text-foreground/40 font-bold">
              <span>Total {data?.meta.total} records</span>
              <span>Rows per page: </span>
            </div>
          </div>
        }
      >
        <TableHeader columns={TableColumns}>
          {(column) => (
            <TableColumn key={column.key}>{column.label}</TableColumn>
          )}
        </TableHeader>
        <TableBody
          loadingState={isLoading ? "loading" : "idle"}
          //   loadingState={"loading"}
          loadingContent={
            <div className="flex justify-center w-full h-full items-center bg-zinc-100/70 z-20">
              <Spinner size="lg" variant="wave" color="secondary" />
            </div>
          }
          items={data?.data ?? []}
          emptyContent="No publications found. Please, try reloading the page."
        >
          {(item) => (
            <TableRow key={item.id}>
              {(columnKey) => (
                <TableCell>{renderCell(item, columnKey)}</TableCell>
              )}
            </TableRow>
          )}
        </TableBody>
      </Table>
      <Modal size="4xl" isOpen={isOpenDetail} onOpenChange={onOpenChangeDetail}>
        <ModalContent>
          {(onClose) => (
            <PublicationModalContent onClose={onClose} id={detailId} />
          )}
        </ModalContent>
      </Modal>
    </div>
  );
}
