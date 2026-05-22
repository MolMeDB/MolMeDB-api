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
  logs: {
    message: string,
    context?: 'error' | 'success' | 'warning' | 'info',
    type?: 'UPLOAD' | 'STATE CHANGE' | 'VALIDATION RUN' | 'REUPLOAD' | 'UPLOAD RUN',
    state: number,
    state_label?: string,
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
