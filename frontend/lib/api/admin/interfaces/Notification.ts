import { ReactNode } from "react";

export default interface Notification {
  type: "primary" | "warning" | "danger" | "success";
  title: string;
  message: ReactNode;
}
