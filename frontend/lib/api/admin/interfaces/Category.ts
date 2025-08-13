export default interface ICategory {
  id: number;
  order: number;
  title: string;
  name?: string; // Just for internal usage
  value?: number; // Just for internal usage
  children?: ICategory[];
  membranes?: {
    id: number;
    name: string;
  }[];
  methods?: {
    id: number;
    name: string;
  }[];
  proteins?: {
    id: number;
    name: string;
  }[];
}
