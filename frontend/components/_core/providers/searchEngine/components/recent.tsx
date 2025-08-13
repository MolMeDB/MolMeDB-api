"use client";

import {
  IRecentSearchQuery,
  ISearchQuery,
} from "@/lib/api/admin/interfaces/SearchEngine";
import { Button, Chip } from "@heroui/react";
import { useEffect, useState } from "react";
import { RxCountdownTimer } from "react-icons/rx";

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
    console.log("Added");
  }, [props.submittedQuery.query, props.submittedQuery.type]);

  return (
    <div className="flex flex-col gap-2">
      <h2 className="font-semibold">Recent</h2>
      {recentSearches.map((search, index) => (
        <div key={index} className="flex flex-col gap-1">
          <Button
            className="px-2"
            variant="flat"
            size="md"
            onPress={() => {
              props.onSubmitQuery(search);
            }}
          >
            <div className="flex flex-row justify-start gap-3 items-center w-full cursor-pointer">
              <div className="border-1 p-2 rounded-lg border-zinc-600 bg-zinc-200">
                <RxCountdownTimer size={13} />
              </div>
              <div className="flex flex-row justify-between items-center gap-4 w-full">
                <label className="text-md cursor-pointer">{search.query}</label>
                <Chip color="warning" size="sm" variant="faded">
                  {search.type}
                </Chip>
                {/* <label className="text-xs text-warning">{search.type}</label> */}
              </div>
            </div>
          </Button>
        </div>
      ))}
    </div>
  );
}

function useRecentSearches(max = 5) {
  const [recentSearches, setRecentSearches] = useState<IRecentSearchQuery[]>(
    () => {
      const saved = localStorage.getItem("recentSearches");
      return saved ? JSON.parse(saved) : [];
    }
  );

  useEffect(() => {
    localStorage.setItem("recentSearches", JSON.stringify(recentSearches));
  }, [recentSearches]);

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
