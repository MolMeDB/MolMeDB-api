export default interface IProtein {
  id: number;
  uniprot_id: string;
  identifiers: {
    id: number;
    value: string;
    type: string;
    state: string;
  }[];
}

export interface IProteinStats {
  protein: IProtein;
  interactions_count: number;
  structures_count: number;
}
