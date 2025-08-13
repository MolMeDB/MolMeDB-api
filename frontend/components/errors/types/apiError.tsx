export default class ApiError extends Error {
  line: number;
  trace: object[];
  file: string;

  constructor(message: string, file: string, line: number, trace: object[]) {
    super(
      `Backend error |:| ${message} |:| ${file} |:| ${line} |:| ${JSON.stringify(
        trace
      )}`
    );
    this.name = "ApiError";
    this.line = line;
    this.trace = trace;
    this.file = file;
  }
}
