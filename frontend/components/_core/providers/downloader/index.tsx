"use client";

import {
  createContext,
  ReactNode,
  useContext,
  useEffect,
  useState,
} from "react";

export type DownloaderCategory =
  | "membrane"
  | "method"
  | "molecule"
  | "protein";

export type DownloaderItem = {
  category: DownloaderCategory;
  id: string;
  label: string;
  included: boolean;
};

export type DownloaderSuggestedItem = Omit<DownloaderItem, "included">;

type DownloaderContextType = {
  items: DownloaderItem[];
  count: number;
  suggestedItem: DownloaderSuggestedItem | null;
  isAdded: (category: DownloaderCategory, id: string) => boolean;
  addItem: (item: DownloaderSuggestedItem) => void;
  removeItem: (category: DownloaderCategory, id: string) => void;
  setSuggestedItem: (item: DownloaderSuggestedItem | null) => void;
  setIncluded: (category: DownloaderCategory, id: string, included: boolean) => void;
  setCategoryIncluded: (category: DownloaderCategory, included: boolean) => void;
};

const STORAGE_KEY = "mmdb-downloader-items";

const DownloaderContext = createContext<DownloaderContextType | undefined>(
  undefined,
);

function readStoredItems(): DownloaderItem[] {
  if (typeof window === "undefined") {
    return [];
  }

  try {
    const raw = window.localStorage.getItem(STORAGE_KEY);

    return raw ? (JSON.parse(raw) as DownloaderItem[]) : [];
  } catch {
    return [];
  }
}

export function DownloaderProvider({ children }: { children: ReactNode }) {
  const [items, setItems] = useState<DownloaderItem[]>([]);
  const [suggestedItem, setSuggestedItem] =
    useState<DownloaderSuggestedItem | null>(null);
  const [isHydrated, setIsHydrated] = useState(false);

  useEffect(() => {
    setItems(readStoredItems());
    setIsHydrated(true);
  }, []);

  useEffect(() => {
    if (!isHydrated) {
      return;
    }

    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
  }, [items, isHydrated]);

  function isAdded(category: DownloaderCategory, id: string): boolean {
    return items.some((item) => item.category === category && item.id === id);
  }

  function addItem(item: DownloaderSuggestedItem) {
    setItems((current) =>
      current.some(
        (existing) =>
          existing.category === item.category && existing.id === item.id,
      )
        ? current
        : [...current, { ...item, included: true }],
    );
  }

  function removeItem(category: DownloaderCategory, id: string) {
    setItems((current) =>
      current.filter(
        (item) => !(item.category === category && item.id === id),
      ),
    );
  }

  function setIncluded(
    category: DownloaderCategory,
    id: string,
    included: boolean,
  ) {
    setItems((current) =>
      current.map((item) =>
        item.category === category && item.id === id
          ? { ...item, included }
          : item,
      ),
    );
  }

  function setCategoryIncluded(category: DownloaderCategory, included: boolean) {
    setItems((current) =>
      current.map((item) =>
        item.category === category ? { ...item, included } : item,
      ),
    );
  }

  return (
    <DownloaderContext.Provider
      value={{
        items,
        count: items.length,
        suggestedItem,
        isAdded,
        addItem,
        removeItem,
        setSuggestedItem,
        setIncluded,
        setCategoryIncluded,
      }}
    >
      {children}
    </DownloaderContext.Provider>
  );
}

export function useDownloader(): DownloaderContextType {
  const context = useContext(DownloaderContext);

  if (!context) {
    throw new Error("useDownloader must be used within a DownloaderProvider");
  }

  return context;
}
