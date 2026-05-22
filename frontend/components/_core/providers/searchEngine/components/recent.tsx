"use client";

import {
  IRecentSearchQuery,
  ISearchQuery,
} from "@/lib/api/admin/interfaces/SearchEngine";
import { Button, Chip } from "@heroui/react";
import { useEffect, useState } from "react";
import { MdHistory, MdOutlineSearchOff } from "react-icons/md";

export default function RecentSearchList(props: {
  submittedQuery: ISearchQuery;
  onSubmitQuery: (query: ISearchQuery) => void;
}) {
  const { recentSearches, addSearch } = useRecentSearches(5);

  useEffect(() => {
    if (!props.submittedQuery.query || !props.submittedQuery.type) {
      return;
    }
    addSearch({
      query: props.submittedQuery.query,
      type: props.submittedQuery.type,
      datetime: new Date().toISOString(),
    });
  }, [props.submittedQuery.query, props.submittedQuery.type]);

  return (
    <div className="flex flex-col gap-4 py-1">
      <div className="flex items-center justify-between gap-3">
        <div className="flex items-center gap-2">
          <MdHistory size={20} className="text-foreground-500" />
          <h2 className="font-semibold text-foreground">Recent</h2>
        </div>
        {recentSearches.length > 0 ? (
          <span className="text-xs text-foreground-500">
            {recentSearches.length} saved
          </span>
        ) : null}
      </div>

      {recentSearches.length === 0 ? (
        <div className="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-default-300 bg-default-50 px-8 py-10 text-center dark:bg-background-dark-2">
          <div />
          <div className="flex h-10 w-10 items-center justify-center rounded-full bg-default-100 text-foreground-500">
            <MdOutlineSearchOff size={22} />
          </div>
          <div className="flex flex-col gap-1">
            <h3 className="font-medium text-foreground">
              No recent searches yet
            </h3>
            <p className="max-w-sm text-sm text-foreground-500">
              Nothing to show yet.
            </p>
          </div>
          <div />
        </div>
      ) : (
        <div className="flex flex-col gap-2">
          {recentSearches.map((search, index) => (
            <Button
              key={`${search.type}-${search.query}-${index}`}
              className="h-auto min-h-12 px-2 py-2"
              variant="flat"
              size="md"
              onPress={() => {
                props.onSubmitQuery(search);
              }}
            >
              <div className="flex w-full cursor-pointer items-center justify-start gap-3">
                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-default-200 bg-background text-foreground-500 dark:bg-background-dark">
                  <MdHistory size={17} />
                </div>
                <div className="flex min-w-0 flex-1 items-center justify-between gap-4">
                  <span className="min-w-0 truncate text-left text-sm font-medium text-foreground">
                    {search.query}
                  </span>
                  <Chip color="primary" size="sm" variant="flat">
                    {search.type}
                  </Chip>
                </div>
              </div>
            </Button>
          ))}
        </div>
      )}
    </div>
  );
}

function useRecentSearches(max = 5) {
  const [recentSearches, setRecentSearches] = useState<IRecentSearchQuery[]>(
    [],
  );
  const [hasLoaded, setHasLoaded] = useState(false);

  useEffect(() => {
    const saved = localStorage.getItem("recentSearches");

    try {
      setRecentSearches(saved ? JSON.parse(saved) : []);
    } catch {
      setRecentSearches([]);
    }

    setHasLoaded(true);
  }, []);

  useEffect(() => {
    if (!hasLoaded) {
      return;
    }

    localStorage.setItem("recentSearches", JSON.stringify(recentSearches));
  }, [hasLoaded, recentSearches]);

  const addSearch = (term: IRecentSearchQuery) => {
    setRecentSearches((prev) => {
      const updated = [
        term,
        ...prev.filter((t) => t.query !== term.query || t.type !== term.type),
      ].slice(0, max);
      return updated;
    });
  };

  return { recentSearches, addSearch };
}
