import React from "react";

export default interface IUiTableColumn<TData> {
  title: React.JSX.Element | string;
  key: keyof TData | string;
  render: (item: TData) => React.ReactNode | string | number;
  isSortable?: boolean;
  sortKey?: keyof TData | string;
  isHideable?: boolean;
}
