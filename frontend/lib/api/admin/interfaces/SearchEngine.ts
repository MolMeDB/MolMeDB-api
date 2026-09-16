import FilteredResponse from "./http/FilteredResponse";

export interface ISearchItem {
  imageUrl?: string;
  title?: string | null;
  subtitle?: string;
  description?: string;
  link?: string | null;
  isAvailable?: boolean;
  availabilityMessage?: string | null;
  downloader?: {
    category: "membrane" | "method" | "molecule" | "protein";
    id: string;
    label: string;
  } | null;
}

export interface ISearchQuery {
  query: string;
  type: "Structures" | "Membranes" | "Methods" | "Proteins" | "Datasets";
  isDrawnStructure?: boolean;
  structureMatch?: "exact" | "substructure";
}

export interface ISearchResult
  extends Omit<FilteredResponse<ISearchItem>, "meta"> {
  meta: Omit<FilteredResponse<ISearchItem>["meta"], "total"> & {
    total?: number;
  };
}

export interface IRecentSearchQuery extends ISearchQuery {
  datetime: string;
}
