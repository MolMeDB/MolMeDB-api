"use client";

import { getJson } from "@/lib/api/admin";
import {
  ISearchQuery,
  ISearchResult,
} from "@/lib/api/admin/interfaces/SearchEngine";
import { addToast, Button, Chip, Link, Spinner } from "@heroui/react";
import { useEffect, useState } from "react";
import {
  MdArrowForward,
  MdImageNotSupported,
  MdSearchOff,
} from "react-icons/md";

export default function SearchListItems(props: {
  searchOptions: ISearchQuery;
}) {
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
      try {
        const response = await getJson(
          `/api/search/${props.searchOptions.type.toLowerCase()}`,
          {
            query: props.searchOptions.query,
          },
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
  }, [props.searchOptions.query, props.searchOptions.type]);

  const total = records?.meta.total ?? 0;

  return (
    <div className="flex flex-col gap-3 w-full">
      <div className="flex flex-col w-full gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div className="flex min-w-0 flex-col gap-1 flex-1">
          <h2 className="font-semibold text-foreground">
            Results for &quot;{props.searchOptions.query}&quot;
          </h2>
          <p className="text-sm text-foreground-500">
            Filtered by {props.searchOptions.type.toLowerCase()}.
          </p>
        </div>
        <Chip color="primary" size="sm" variant="flat">
          {isSearching
            ? "Searching"
            : errorMessage
              ? "Error"
              : `${total} results`}
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
            return (
              <Button
                key={`${record.link}-${index}`}
                as={Link}
                href={record.link}
                className="h-auto min-h-24 justify-start border-default-200 bg-white px-3 py-3 text-left shadow-sm dark:bg-background-dark-2"
                variant="bordered"
                size="lg"
                endContent={
                  <MdArrowForward
                    size={19}
                    className="shrink-0 text-foreground-400"
                  />
                }
              >
                <div className="flex min-w-0 flex-1 items-center gap-4">
                  {record.imageUrl ? (
                    <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-md border border-default-200 bg-default-50 p-1 dark:bg-background-dark">
                      <img
                        src={record.imageUrl ?? "todo"}
                        alt={record.title}
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
                        {record.title}
                      </h3>
                    </div>
                    {record.subtitle ? (
                      <div className="w-full overflow-x-auto pb-1">
                        <p className="w-max max-w-none whitespace-nowrap text-left text-sm text-foreground-500">
                          {record.subtitle}
                        </p>
                      </div>
                    ) : null}
                  </div>
                </div>
              </Button>
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
