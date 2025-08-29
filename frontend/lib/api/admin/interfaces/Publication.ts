import IFile from "./File";

export default interface IPublication {
  id: number;
  identifier: IPublicationIdentifier;
  citation?: string;
  title?: string;
  doi?: string;
  journal?: string;
  volume?: string;
  issue?: string;
  page?: string;
  year?: number;
  authors: IPublicationAuthor[];
  stats?: {
    passive_interactions?: number;
    active_interactions?: number;
    membranes?: number;
    methods?: number;
    datasets?: number;
    authors?: number;
    dataset_passive_interactions?: number;
    dataset_active_interactions?: number;
  };
  datasets?: IFile[];
}

export interface IPublicationIdentifier {
  value?: string;
  source?: string;
  source_name?: string;
}

export interface IPublicationAuthor {
  first_name?: string;
  last_name?: string;
  full_name?: string;
  affiliation?: string;
}
