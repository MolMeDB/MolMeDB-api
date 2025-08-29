import IFile from "./File";

export default interface IMethod {
  id: number;
  name: string;
  abbreviation: string;
  description?: string;
  datasets?: IFile[];
}

export interface IMethodStats {
  method: IMethod;
  total: {
    interactions_passive: number;
    structures: number;
  };
}
