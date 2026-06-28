"use client";

import { useDownloader } from "@/components/_core/providers/downloader";
import { getJson } from "@/lib/api/admin";
import {
  ISearchQuery,
  ISearchResult,
} from "@/lib/api/admin/interfaces/SearchEngine";
import { addToast, Button, Chip, Spinner, Tooltip } from "@heroui/react";
import Link from "next/link";
import { useEffect, useState } from "react";
import { FiCheck, FiPlus } from "react-icons/fi";
import {
  MdArrowForward,
  MdImageNotSupported,
  MdOutlineHourglassEmpty,
  MdSearchOff,
} from "react-icons/md";

export default function SearchListItems(props: {
  searchOptions: ISearchQuery;
  onRecordOpen: () => void;
}) {
  const { addItem, isAdded } = useDownloader();
  const [isSearching, setIsSearching] = useState(false);
  const [records, setRecords] = useState<ISearchResult>();
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  useEffect(() => {
    if (!props.searchOptions.query || !props.searchOptions.type) {
      return;
    }

    let isCurrentSearch = true;

    setIsSearching(true);
    setErrorMessage(null);

    async function runSearch() {
      // Validate SMILES before running substructure search
      if (
        props.searchOptions.type === "Structures" &&
        props.searchOptions.structureMatch === "substructure" &&
        !props.searchOptions.isDrawnStructure
      ) {
        try {
          const validateResponse = await fetch(
            `/api/mol/smiles/canonize?smiles=${encodeURIComponent(props.searchOptions.query)}`,
          );
          if (!isCurrentSearch) return;
          const validateJson = await validateResponse.json();
          if (!validateResponse.ok || !validateJson?.canonized_smiles) {
            setIsSearching(false);
            setErrorMessage(
              "Invalid SMILES. Please enter a valid molecule structure for substructure search.",
            );
            return;
          }
        } catch {
          if (!isCurrentSearch) return;
          setIsSearching(false);
          setErrorMessage("SMILES validation failed. Please try again.");
          return;
        }
      }

      try {
        const response = await getJson(
          `/api/search/${props.searchOptions.type.toLowerCase()}`,
          props.searchOptions.type === "Structures" &&
            props.searchOptions.structureMatch === "substructure"
            ? { substructure: props.searchOptions.query }
            : props.searchOptions.isDrawnStructure
              ? { smiles: props.searchOptions.query }
              : { query: props.searchOptions.query },
        );

        if (!isCurrentSearch) {
          return;
        }

        if (response?.code === 200 && response.data) {
          setRecords(response.data);
          return;
        }

        console.warn(response);
        setRecords(undefined);
        setErrorMessage("Search failed. Please, try again.");
        addToast({
          title: "Error",
          description: "Failed to load search results. Please, try again.",
          color: "danger",
          shouldShowTimeoutProgress: true,
          timeout: 4500,
        });
      } catch (error) {
        if (!isCurrentSearch) {
          return;
        }

        console.error(error);
        setRecords(undefined);
        setErrorMessage("Search failed. Please, try again.");
        addToast({
          title: "Error",
          description: "Failed to load search results. Please, try again.",
          color: "danger",
          shouldShowTimeoutProgress: true,
          timeout: 4500,
        });
      } finally {
        if (isCurrentSearch) {
          setIsSearching(false);
        }
      }
    }

    runSearch();

    return () => {
      isCurrentSearch = false;
    };
  }, [
    props.searchOptions.isDrawnStructure,
    props.searchOptions.query,
    props.searchOptions.structureMatch,
    props.searchOptions.type,
  ]);

  const total = records?.meta.total;
  const resultCount = total ?? records?.data.length ?? 0;
  const hasMoreResults = total === undefined && Boolean(records?.links.next);

  return (
    <div className="flex flex-col gap-3 w-full">
      <div className="flex flex-col w-full gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div className="flex min-w-0 flex-col gap-1 flex-1">
          <h2 className="font-semibold text-foreground">
            Results for &quot;{props.searchOptions.query}&quot;
          </h2>
          <p className="text-sm text-foreground-500">
            Filtered by {props.searchOptions.type.toLowerCase()}
            {props.searchOptions.type === "Structures"
              ? ` · ${props.searchOptions.structureMatch === "substructure" ? "substructure" : "exact structure"}`
              : ""}
            .
          </p>
        </div>
        <Chip color="primary" size="sm" variant="flat">
          {isSearching
            ? "Searching"
            : errorMessage
              ? "Error"
              : `${resultCount}${hasMoreResults ? "+" : ""} results`}
        </Chip>
      </div>

      {isSearching ? (
        <div className="flex min-h-44 w-full flex-col items-center justify-center gap-3 rounded-lg border border-default-200 bg-default-50 dark:bg-background-dark-2">
          <Spinner size="lg" variant="wave" />
          <span className="text-sm text-foreground-500">
            Searching MolMeDB...
          </span>
        </div>
      ) : errorMessage ? (
        <div className="flex min-h-44 flex-col items-center justify-center gap-3 rounded-lg border border-danger-200 bg-danger-50 px-6 py-8 text-center text-danger-700 dark:bg-danger-950/20">
          <div className="flex h-10 w-10 items-center justify-center rounded-full bg-danger-100 text-danger-600">
            <MdSearchOff size={22} />
          </div>
          <div className="flex flex-col gap-1">
            <h3 className="font-medium">Search error</h3>
            <p className="max-w-sm text-sm">{errorMessage}</p>
          </div>
        </div>
      ) : records?.data.length === 0 ? (
        <div className="flex min-h-44 flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-default-300 bg-default-50 px-6 py-8 text-center dark:bg-background-dark-2">
          <div className="flex h-10 w-10 items-center justify-center rounded-full bg-default-100 text-foreground-500">
            <MdSearchOff size={22} />
          </div>
          <div className="flex flex-col gap-1">
            <h3 className="font-medium text-foreground">No results found</h3>
            <p className="max-w-sm text-sm text-foreground-500">
              No matching records in this category.
            </p>
          </div>
        </div>
      ) : records?.data ? (
        <div className="flex max-h-[30rem] flex-col gap-2 overflow-y-auto pr-1">
          {records.data.map((record, index) => {
            const recordTitle = record.title ?? "Molecule record";
            const recordLink = record.link ?? "";
            const isAvailable = record.isAvailable !== false && recordLink;
            const downloaderItem = record.downloader ?? null;
            const isInDownloader = downloaderItem
              ? isAdded(downloaderItem.category, downloaderItem.id)
              : false;
            const content = (
              <div className="flex min-w-0 flex-1 items-center gap-4">
                {record.imageUrl ? (
                  <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-md border border-default-200 bg-default-50 p-1 dark:bg-background-dark">
                    <img
                      src={record.imageUrl ?? "todo"}
                      alt={recordTitle}
                      width={80}
                      height={80}
                      className="max-h-full max-w-full object-contain"
                    />
                  </div>
                ) : (
                  <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-md border border-default-200 bg-default-50 text-foreground-400 dark:bg-background-dark">
                    <MdImageNotSupported size={22} />
                  </div>
                )}
                <div className="flex min-w-0 flex-1 flex-col items-start justify-center gap-1 overflow-hidden">
                  <div className="w-full overflow-x-auto pb-1">
                    <h3 className="w-max max-w-none whitespace-nowrap text-sm font-semibold text-foreground">
                      {recordTitle}
                    </h3>
                  </div>
                  {record.subtitle ? (
                    <div className="w-full overflow-x-auto pb-1">
                      <p className="w-max max-w-none whitespace-nowrap text-left text-sm text-foreground-500">
                        {record.subtitle}
                      </p>
                    </div>
                  ) : null}
                  {!isAvailable ? (
                    <Chip
                      size="sm"
                      variant="flat"
                      color="warning"
                      startContent={<MdOutlineHourglassEmpty size={14} />}
                    >
                      {record.availabilityMessage ??
                        "This molecule record is being prepared."}
                    </Chip>
                  ) : null}
                </div>
              </div>
            );

            if (!isAvailable) {
              return (
                <div
                  key={`pending-${recordTitle}-${index}`}
                  className="flex min-h-24 cursor-not-allowed items-center justify-between gap-3 rounded-lg border border-default-200 bg-white px-3 py-3 text-left opacity-80 shadow-sm dark:bg-background-dark-2"
                  aria-disabled="true"
                >
                  {content}
                </div>
              );
            }

            return (
              <div
                key={`${recordLink}-${index}`}
                className="flex min-h-24 items-stretch gap-2 rounded-lg border border-default-200 bg-white p-2 shadow-sm dark:bg-background-dark-2"
              >
                <Link
                  href={recordLink}
                  onClick={props.onRecordOpen}
                  className="flex min-w-0 flex-1 items-center gap-3 rounded-md px-1 py-1 outline-none transition-colors hover:bg-default-100 focus-visible:ring-2 focus-visible:ring-primary dark:hover:bg-default-50/10"
                >
                  {content}
                  <MdArrowForward
                    size={19}
                    className="shrink-0 text-foreground-400"
                  />
                </Link>
                {downloaderItem ? (
                  <div className="flex shrink-0 items-center border-l border-default-200 pl-2 dark:border-default-100">
                    <Tooltip
                      content={
                        isInDownloader
                          ? "Already in downloader"
                          : "Add to downloader"
                      }
                    >
                      <Button
                        isIconOnly
                        aria-label={
                          isInDownloader
                            ? `${downloaderItem.label} is already in downloader`
                            : `Add ${downloaderItem.label} to downloader`
                        }
                        color={isInDownloader ? "success" : "primary"}
                        isDisabled={isInDownloader}
                        size="sm"
                        variant={isInDownloader ? "flat" : "light"}
                        onPress={() => addItem(downloaderItem)}
                      >
                        {isInDownloader ? <FiCheck /> : <FiPlus />}
                      </Button>
                    </Tooltip>
                  </div>
                ) : null}
              </div>
            );
          })}
        </div>
      ) : (
        <div className="flex min-h-44 flex-col items-center justify-center gap-3 rounded-lg border border-default-200 bg-default-50 px-6 py-8 text-center dark:bg-background-dark-2">
          <MdSearchOff size={24} className="text-foreground-400" />
          <p className="text-sm text-foreground-500">No active search.</p>
        </div>
      )}
    </div>
  );
}
