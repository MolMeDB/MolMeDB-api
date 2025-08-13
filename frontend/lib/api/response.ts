export default interface ApiResponse {
  status: number;
  title: string | undefined;
  message: string | undefined;
  data: any;
}
