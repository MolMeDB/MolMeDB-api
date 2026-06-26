import IFile from "./File";
import IMembrane from "./Membrane";
import { IUser } from "./User";

export interface IPrediction {
  id: number;
  token: string;
  comment?: string;
  temperature: number;
  state: number;
  enum_state: string;
  step: number;
  total_steps: number;
  enum_step: string;
  progress_percent: number;
  method_type: string;
  method: string;
  remote_method?: string | null;
  remote_calculation_id?: string | null;
  remote_molecule_id?: string | null;
  remote_status?: string | null;
  enum_remote_status?: string | null;
  remote_current_step?: string | null;
  remote_heartbeat_at?: string | null;
  remote_last_status_at?: string | null;
  remote_finished_at?: string | null;
  remote_error_message?: string | null;
  logs?: {
    [key: string]: any;
  }[];
  priority: 1 | 2 | 3;
  dataset: IPredictionDataset;
  user: IUser;
  membrane: IPredictionMembrane;
  result: IPredictionResult;
  structure: IPredictionStructure;
  updated_at: string;
  created_at: string;
}

export interface IPredictionStructure {
  id: number;
  remote_id: null | number;
  remote_identifier: null | string;
  canonical_smiles: string;
  structure_2d_url?: string;
  structure_2d_url_big?: string;
  total_conformers?: number;
  remote_molecule_status?: string | null;
  created_at: string;
  updated_at: string;
}

export interface IPredictionDataset {
  id: number;
  comment: string;
  token: string;
  user_id?: number;
  temperature: number;
  membrane: IPredictionMembrane;
  priority: 1 | 2 | 3;
  method_type: string;
  method: string;
  remote_method?: string | null;
  state: number;
  enum_state: string;
  stats: {
    pending: number;
    running: number;
    done: number;
    failed: number;
    total: number;
  };
  overall_stats: {
    progress_percent: number;
    completed_percent: number;
    steps_done: number;
    steps_total: number;
  };
  user?: IUser;
  updated_at: string;
  created_at: string;
}

export interface IPredictionMembrane {
  id: number;
  name: string;
  abbreviation: string;
  remote_id: number;
  related_record: IMembrane;
}

interface IParsedCosmoResult {
  symmetry: string;
  layer_count: number;
  layer_positions: number[];
  temperature: number;
  solutes: {
    mean_position: number;
    logK: number;
    logPerm: number;
    energy_values: number[];
  }[];
}

export interface IPredictionResult {
  id: number;
  file: IFile;
  results: IParsedCosmoResult[] | false;
}
