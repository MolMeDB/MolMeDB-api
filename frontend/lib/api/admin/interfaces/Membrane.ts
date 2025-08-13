export default interface IMembrane {
  id: number;
  name: string;
  abbreviation: string;
  description?: string;
}

export interface IMembraneStats {
  membrane: IMembrane;
  total: {
    interactions_passive: number;
    structures: number;
  };
}
