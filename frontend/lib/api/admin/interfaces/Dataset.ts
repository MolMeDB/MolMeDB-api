import IMembrane from "./Membrane";
import IMethod from "./Method";

enum IDatasetType {
  PASSIVE = 1,
  ACTIVE = 2,
  PASSIVE_INTERNAL_COSMO = 3,
}

export type IDataset = {
  id: number;
  type: IDatasetType;
  enum_type: string;
  name: string;
};

export interface IDatasetPassive extends IDataset {
  type: IDatasetType.PASSIVE;
  membrane: IMembrane;
  method: IMethod;
}

export interface IDatasetActive extends IDataset {
  type: IDatasetType.ACTIVE;
}
