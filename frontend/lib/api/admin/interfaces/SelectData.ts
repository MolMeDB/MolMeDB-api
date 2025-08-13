export type ISelectItem =
  | {
      type: "item";
      label: string;
      value: string | number;
      totalInteractions: number;
    }
  | { type: "category"; category: string; children: ISelectItem[] };

export interface ISelectSetting {
  placeholder: string;
  items: ISelectItem[];
}
