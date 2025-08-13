export default interface IMethod {
  id: number;
  name: string;
  abbreviation: string;
  description?: string;
}

export interface IMethodStats {
  method: IMethod;
  total: {
    interactions_passive: number;
    structures: number;
  };
}
