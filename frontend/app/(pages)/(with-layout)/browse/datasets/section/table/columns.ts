import IPublication from "@/lib/api/admin/interfaces/Publication";

interface ITableColumn {
  key: string;
  label: string;
  hideable?: boolean;
  sortable?: boolean;
  render?: (item: IPublication) => React.ReactNode;
  description?: (item: IPublication) => string;
}

export const TableColumns: ITableColumn[] = [
  {
    key: "authors",
    label: "Author(s)",
  },
  {
    key: "title",
    label: "Title",
  },
  {
    key: "journal",
    label: "Journal",
  },
  {
    key: "year",
    label: "Year",
  },
  {
    key: "identifier",
    label: "Link",
  },
  {
    key: "actions",
    label: "Actions",
  },
];
