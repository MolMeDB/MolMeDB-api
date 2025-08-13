import { SearchParams } from "@/enums/SearchParams";
// import { ChemicalsFilter } from "@/types/filters/ChemicalsFilter";

type NextSearchParams = {
  [key: string]: string | string[] | undefined;
};

export function selectedValuesToSearchParamsString(selectedValues: {
  [key: string]: Set<number | string>;
}) {
  selectedValues = Object.fromEntries(
    Object.entries(selectedValues).filter(
      ([_, value]) => value !== undefined && value?.toString().trim() !== ""
    )
  );

  return Object.keys(selectedValues)
    .flatMap((key) =>
      selectedValues[key] instanceof Set || Array.isArray(selectedValues[key])
        ? [...selectedValues[key]].map((value) => `${key}=${value}`)
        : `${key}=${selectedValues[key]}`
    )
    .join("&");
}

export function getListFilter(searchParams: NextSearchParams) {
  return {
    page: parseInt(getSingleValue(searchParams, SearchParams.page, "0") || "1"),
    query: getSingleValue(searchParams, SearchParams.query, ""),
    limit:
      parseInt(getSingleValue(searchParams, SearchParams.limit) ?? "10") || 10,
    [SearchParams.category]: getMultipleValues(
      searchParams,
      SearchParams.category,
      []
    ),
  };
}

function getSingleValue(
  searchParams: NextSearchParams,
  key: string,
  defaultValue?: string
): string | undefined {
  const value = searchParams[key];

  if (!value) {
    return defaultValue;
  }

  return typeof value === "string"
    ? value
    : value.length > 0
    ? value[0]
    : defaultValue;
}

function getMultipleValues(
  searchParams: NextSearchParams,
  key: string,
  defaultValue?: Array<string>
): Array<string> | undefined {
  const value = searchParams[key];

  if (!value) {
    return defaultValue;
  }

  return Array.isArray(value)
    ? value.filter((v) => v !== undefined && v !== null)
    : [value];
  // return typeof value === "string" ? [value] : [...value];
}
