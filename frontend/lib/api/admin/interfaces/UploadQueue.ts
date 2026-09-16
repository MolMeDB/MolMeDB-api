import { IDataset, IDatasetActive, IDatasetPassive } from "./Dataset";
import IFile from "./File";
import IPublication from "./Publication";

export interface IUploadQueue {
  id: number,
  state: number,
  state_label: string,
  state_phase: string,
  can_reupload: boolean,
  can_configure: boolean,
  can_enqueue: boolean, 
  can_revert: boolean,
  can_cancel: boolean, 
  dataset: IDatasetPassive | IDatasetActive,
  secondary_publication?: IPublication,
  file?: IFile,
  last_message?: string,
  config?: IUploadQueueConfig,
  processing_progress?: IUploadQueueProgress | null,
  logs: {
    message: string,
    context?: 'error' | 'success' | 'warning' | 'info',
    type?: 'UPLOAD' | 'STATE CHANGE' | 'VALIDATION RUN' | 'REUPLOAD' | 'UPLOAD RUN' | 'NOTIFICATION',
    state: number,
    state_label?: unknown,
    payload?: any,
    timestamp: string,
    user_id?: number
  }[]
}

export interface IUploadQueueConfig {
  separator: string,
  skip_first_row: number,
  attributes: []
}

export interface IUploadQueueProgress {
  phase?: 'detailed_validation' | 'import' | string | null,
  mode?: string | null,
  processed_rows: number,
  created_rows?: number | null,
  skipped_rows?: number | null,
  next_line?: number | null,
  total_rows?: number | null,
  percent?: number | null,
  heartbeat_at?: string | null
}
