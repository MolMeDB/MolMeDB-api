import { IPrediction } from "@/lib/api/admin/interfaces/Predictions";

/**
 * Predictions imported from the legacy pipeline (or otherwise never submitted
 * to the remote server) have no remote_status/remote_current_step at all,
 * even when they're already Finished locally. Fall back to the local state
 * instead of always reporting "Pending" in that case.
 */
export function getRemoteStatusLabel(item: IPrediction): string {
  if (item.enum_remote_status) return item.enum_remote_status;
  if (item.remote_current_step) return item.remote_current_step;

  switch (item.enum_state) {
    case "Finished":
      return "Finished";
    case "Error":
      return "Failed";
    case "Running":
      return "Running";
    default:
      return "Pending";
  }
}

export function getRemoteStatusColor(
  item: IPrediction,
): "danger" | "success" | "warning" | "default" {
  if (item.remote_error_message) return "danger";

  const status = item.remote_status ?? null;
  if (status === "running") return "warning";
  if (status === "completed") return "success";
  if (status === "failed") return "danger";

  if (!status) {
    if (item.enum_state === "Finished") return "success";
    if (item.enum_state === "Error") return "danger";
    if (item.enum_state === "Running") return "warning";
  }

  return "default";
}
