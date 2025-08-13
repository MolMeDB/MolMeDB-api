"use client";

import { getJson } from "@/lib/api/admin";
import {
  ISearchQuery,
  ISearchResult,
} from "@/lib/api/admin/interfaces/SearchEngine";
import { addToast, Button, Link, Spinner } from "@heroui/react";
import { useEffect, useState } from "react";

export default function SearchListItems(props: {
  searchOptions: ISearchQuery;
}) {
  const [isSearching, setIsSearching] = useState(false);
  const [records, setRecords] = useState<ISearchResult>();

  useEffect(() => {
    if (!props.searchOptions.query || !props.searchOptions.type) {
      return;
    }
    setIsSearching(true);

    getJson(`/api/search/${props.searchOptions.type.toLowerCase()}`, {
      query: props.searchOptions.query,
    }).then((response) => {
      setIsSearching(false);
      if (response?.code === 200 && response.data) {
        setRecords(response.data);
        return;
      }
      addToast({
        title: "Error",
        description: "Failed to load search results. Please, try again.",
        color: "danger",
        shouldShowTimeoutProgress: true,
        timeout: 4500,
      });
    });
  }, [props.searchOptions.query, props.searchOptions.type]);

  return (
    <div className="flex flex-col gap-2">
      <div className="flex flex-row items-center justify-between gap-8">
        <h2 className="font-semibold text-foreground/80">
          Results for '{props.searchOptions.query}'
        </h2>
        <h3 className="font-semibold text-foreground/60 text-sm">
          Total: {records?.meta.total}
        </h3>
      </div>

      {isSearching ? (
        <div className="h-16 w-full flex flex-row items-center justify-center">
          <Spinner size="lg" variant="wave" />
        </div>
      ) : (
        records?.data.map((record, index) => {
          return (
            <Button
              key={index}
              as={Link}
              href={record.link}
              className="px-2 h-24"
              variant="bordered"
              size="lg"
            >
              <div key={index} className="flex flex-row gap-8 w-full">
                {record.imageUrl ? (
                  <div
                    style={{ width: 80, height: 80 }}
                    className="flex items-center justify-center"
                  >
                    <img
                      src={record.imageUrl ?? "todo"}
                      alt={record.title}
                      width={80}
                      height={80}
                      className="max-w-full h-auto"
                    />
                  </div>
                ) : null}
                <div className="flex flex-col items-start justify-center gap-1">
                  <h1 className="font-bold color-danger line-clamp-1">
                    {record.title}
                  </h1>
                  <h2 className="text-foreground-400 text-sm">
                    {record.subtitle}
                  </h2>
                </div>
              </div>
            </Button>
          );
        })
      )}
    </div>
  );
}
