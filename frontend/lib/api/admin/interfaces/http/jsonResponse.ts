export default interface HttpJsonResponse {
  code: number;
  message?: string;
  errors?: {
    [key: string]: string[];
  };
  data?: any;
}
