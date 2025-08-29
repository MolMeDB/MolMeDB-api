export const IFileTypeExportIntMembrane = 10;
export const IFileTypeExportIntMethod = 11;
export const IFileTypeExportIntPass = 12;
export const IFileTypeExportIntAct = 13;

export default interface IFile {
  name: string;
  type?: number;
  enum_type?: string;
  mime?: string;
  hash?: string;
  created_at: string;
}
