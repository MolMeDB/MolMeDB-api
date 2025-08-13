import FilteredResponse from "./http/FilteredResponse";

export interface ISearchItem {
  imageUrl?: string;
  title: string;
  subtitle?: string;
  description?: string;
  link: string;
}

export interface ISearchQuery {
  query: string;
  type: "Structures" | "Membranes" | "Methods" | "Proteins" | "Datasets";
}

export interface ISearchResult extends FilteredResponse<ISearchItem> {}

export interface IRecentSearchQuery extends ISearchQuery {
  datetime: string;
}
