import IFile from "./File";

export default interface IMembrane {
  id: number;
  name: string;
  abbreviation: string;
  description?: string;
  datasets?: IFile[];
}

export interface IMembraneStats {
  membrane: IMembrane;
  total: {
    interactions_passive: number;
    structures: number;
  };
}
