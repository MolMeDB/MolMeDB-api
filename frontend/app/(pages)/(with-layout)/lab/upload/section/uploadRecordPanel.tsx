"use client";

import { IUploadQueue } from "@/lib/api/admin/interfaces/UploadQueue";
import { downloadFile } from "@/utils/downloadFile";
import {
  addToast,
  Button,
  Modal,
  ModalBody,
  ModalContent,
  ModalFooter,
  ModalHeader,
  Progress,
  Spinner,
} from "@heroui/react";
import { useState } from "react";

type UploadQueueLog = IUploadQueue["logs"][number];

type ConfigureState = {
  isOpen: boolean;
  separator: string;
  skipFirstRow: number;
  startLine: number;
  totalRows: number;
  previewRows: string[][];
  columnMapping: string[];
  columnTypeOptions: Record<string, string>;
  columnValidTypes: Array<Record<string, string>>;
  errors: string[];
  warnings: string[];
  isLoading: boolean;
  isValidating: boolean;
  isValidated: boolean;
};

type BackendErrorResponse = {
  message?: string;
  errors?: Record<string, string[] | string>;
};

const INITIAL_CONFIGURE_STATE: ConfigureState = {
  isOpen: false,
  separator: ",",
  skipFirstRow: 1,
  startLine: 1,
  totalRows: 0,
  previewRows: [],
  columnMapping: [],
  columnTypeOptions: {},
  columnValidTypes: [],
  errors: [],
  warnings: [],
  isLoading: false,
  isValidating: false,
  isValidated: false,
};

function normalizeSeparatorLabel(value: string): string {
  if (value === "\t" || value === "\\t" || value === "tab") {
    return "\\t";
  }

  return value;
}

function denormalizeSeparatorValue(value: string): string {
  if (value === "\\t" || value === "tab") {
    return "\t";
  }

  return value;
}

function buildColumnValidTypes(
  mapping: string[],
  columnTypeOptions: Record<string, string>,
): Array<Record<string, string>> {
  return mapping.map((selectedValue) => {
    return Object.fromEntries(
      Object.entries(columnTypeOptions).filter(([value]) => {
        return (
          value === "ignore" ||
          !mapping.includes(value) ||
          value === selectedValue
        );
      }),
    );
  });
}

function formatText(value: unknown, fallback = "-"): string {
  if (value === null || value === undefined) {
    return fallback;
  }

  if (typeof value === "string") {
    return value.trim() === "" ? fallback : value;
  }

  return fallback;
}

export default function UploadRecordPanel(props: {
  upload: IUploadQueue;
  guestToken?: string;
  onRefresh: () => void | Promise<void>;
}) {
  const { upload, guestToken } = props;

  const [configureState, setConfigureState] = useState<ConfigureState>(
    INITIAL_CONFIGURE_STATE,
  );
  const [isEnqueuing, setIsEnqueuing] = useState(false);
  const [isCanceling, setIsCanceling] = useState(false);
  const [isReverting, setIsReverting] = useState(false);
  const [reuploadFile, setReuploadFile] = useState<File | null>(null);
  const [isReuploading, setIsReuploading] = useState(false);
  const [reuploadErrors, setReuploadErrors] = useState<string[]>([]);
  const [downloadLoading, setDownloadLoading] = useState(false);
  const [isLogsModalOpen, setIsLogsModalOpen] = useState(false);

  function withGuestToken(formData: FormData): FormData {
    if (guestToken) {
      formData.append("guest_token", guestToken);
    }

    return formData;
  }

  function extractBackendErrors(
    json: BackendErrorResponse | null,
    fallbackMessage: string,
  ): string[] {
    const errors = json?.errors
      ? Object.values(json.errors).flatMap((value) =>
          Array.isArray(value) ? value : [value],
        )
      : [];

    return errors.length > 0 ? errors : [json?.message ?? fallbackMessage];
  }

  function logToneClass(log: UploadQueueLog): string {
    if (log.context === "error" || log.state === 3) {
      return "bg-danger-100 text-danger-700 border-danger-200";
    }

    if (log.context === "success" || log.state === 2) {
      return "bg-success-100 text-success-700 border-success-200";
    }

    if (log.context === "warning") {
      return "bg-warning-100 text-warning-700 border-warning-200";
    }

    return "bg-default-100 text-default-700 border-default-200";
  }

  function formatLogPayload(payload: unknown): string | null {
    if (payload === null || payload === undefined) {
      return null;
    }

    if (typeof payload === "string") {
      return payload;
    }

    try {
      return JSON.stringify(payload, null, 2);
    } catch {
      return String(payload);
    }
  }

  function formatLogTimestamp(timestamp?: string): string {
    if (!timestamp) {
      return "";
    }

    const normalizedTimestamp = timestamp.includes("T")
      ? timestamp
      : timestamp.replace(" ", "T");
    const hasExplicitTimezone = /(?:Z|[+-]\d{2}:?\d{2})$/i.test(
      normalizedTimestamp,
    );
    const date = new Date(
      hasExplicitTimezone ? normalizedTimestamp : `${normalizedTimestamp}Z`,
    );

    if (Number.isNaN(date.getTime())) {
      return timestamp;
    }

    return new Intl.DateTimeFormat(undefined, {
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
      timeZoneName: "short",
    }).format(date);
  }

  function sortedLogs(): UploadQueueLog[] {
    return [...(upload.logs ?? [])].sort((firstLog, secondLog) => {
      return (
        Date.parse(secondLog.timestamp ?? "") -
        Date.parse(firstLog.timestamp ?? "")
      );
    });
  }

  async function loadConfigurePreview(
    separator: string,
    skipFirstRow: number,
    startLine = 1,
  ) {
    setConfigureState((prev) => ({
      ...prev,
      isLoading: true,
      errors: [],
      warnings: [],
    }));

    try {
      const query = new URLSearchParams({
        separator: normalizeSeparatorLabel(separator),
        skip_first_row: String(skipFirstRow),
        start_line: String(startLine),
        limit: "5",
      });

      if (guestToken) {
        query.set("guest_token", guestToken);
      }

      const response = await fetch(
        `/api/lab/upload/${upload.id}/configure/preview?${query.toString()}`,
      );
      const json = await response.json();

      if (!response.ok) {
        setConfigureState((prev) => ({
          ...prev,
          isLoading: false,
          errors: json?.errors ?? [json?.message ?? "Failed to load preview."],
        }));
        return;
      }

      const data = json?.data ?? {};
      const mapping: string[] = data?.column_mapping ?? [];
      const columnTypeOptions: Record<string, string> =
        data?.column_type_options ?? {};
      const columnValidTypes: Array<Record<string, string>> =
        data?.column_valid_types && Array.isArray(data.column_valid_types)
          ? data.column_valid_types
          : buildColumnValidTypes(mapping, columnTypeOptions);

      setConfigureState((prev) => ({
        ...prev,
        isLoading: false,
        separator: denormalizeSeparatorValue(separator),
        skipFirstRow,
        startLine: data?.start_line ?? startLine,
        totalRows: data?.total_rows ?? 0,
        previewRows: data?.preview_rows ?? [],
        columnMapping: mapping,
        columnTypeOptions,
        columnValidTypes,
      }));
    } catch {
      setConfigureState((prev) => ({
        ...prev,
        isLoading: false,
        errors: ["Failed to load preview."],
      }));
    }
  }

  async function openConfigure() {
    const separator = denormalizeSeparatorValue(upload.config?.separator ?? ",");
    const skipFirstRow = upload.config?.skip_first_row ?? 1;

    setConfigureState((prev) => ({
      ...prev,
      isOpen: true,
      separator,
      skipFirstRow,
      startLine: 1,
    }));

    await loadConfigurePreview(separator, skipFirstRow, 1);
  }

  async function validateConfiguration() {
    setConfigureState((prev) => ({
      ...prev,
      isValidating: true,
      errors: [],
      warnings: [],
    }));

    try {
      const payload = withGuestToken(new FormData());
      payload.append("separator", normalizeSeparatorLabel(configureState.separator));
      payload.append("skip_first_row", String(configureState.skipFirstRow));
      configureState.columnMapping.forEach((value, index) => {
        payload.append(`attributes[${index}]`, value);
      });

      const response = await fetch(
        `/api/lab/upload/${upload.id}/configure/validate`,
        { method: "POST", body: payload },
      );
      const json = await response.json();

      if (!response.ok) {
        const errors: string[] = Array.isArray(json?.errors?.config)
          ? json.errors.config
          : [json?.message ?? "Validation failed."];
        setConfigureState((prev) => ({
          ...prev,
          isValidating: false,
          isValidated: false,
          errors,
          warnings: json?.warnings ?? [],
        }));
        return;
      }

      setConfigureState((prev) => ({
        ...prev,
        isValidating: false,
        isValidated: true,
        errors: [],
        warnings: json?.data?.warnings ?? [],
      }));
      await props.onRefresh();
    } catch {
      setConfigureState((prev) => ({
        ...prev,
        isValidating: false,
        isValidated: false,
        errors: ["Validation failed unexpectedly."],
      }));
    }
  }

  async function sendToQueue() {
    const isConfirmed = window.confirm(
      "Move this record to pending upload state? This action is irreversible.",
    );
    if (!isConfirmed) {
      return;
    }

    setIsEnqueuing(true);

    try {
      const response = await fetch(`/api/lab/upload/${upload.id}/enqueue`, {
        method: "POST",
        body: withGuestToken(new FormData()),
      });

      if (response.ok) {
        setConfigureState(INITIAL_CONFIGURE_STATE);
      }
    } finally {
      setIsEnqueuing(false);
      await props.onRefresh();
    }
  }

  async function revertUpload() {
    setIsReverting(true);

    try {
      await fetch(`/api/lab/upload/${upload.id}/revert`, {
        method: "POST",
        body: withGuestToken(new FormData()),
      });
    } finally {
      setIsReverting(false);
      await props.onRefresh();
    }
  }

  async function cancelUpload() {
    const isConfirmed = window.confirm(
      "Cancel this upload? Uploaded file will be permanently deleted and this action cannot be undone.",
    );
    if (!isConfirmed) {
      return;
    }

    setIsCanceling(true);

    try {
      await fetch(`/api/lab/upload/${upload.id}/cancel`, {
        method: "POST",
        body: withGuestToken(new FormData()),
      });
    } finally {
      setIsCanceling(false);
      await props.onRefresh();
    }
  }

  async function reupload() {
    if (!reuploadFile) {
      return;
    }

    setIsReuploading(true);
    setReuploadErrors([]);

    try {
      const payload = withGuestToken(new FormData());
      payload.append("file", reuploadFile);

      const response = await fetch(`/api/lab/upload/${upload.id}/reupload`, {
        method: "POST",
        body: payload,
      });

      if (!response.ok) {
        let json: BackendErrorResponse | null = null;
        try {
          json = await response.json();
        } catch {
          json = null;
        }
        setReuploadErrors(extractBackendErrors(json, "Failed to reupload file."));
        return;
      }

      setReuploadFile(null);
    } finally {
      setIsReuploading(false);
      await props.onRefresh();
    }
  }

  async function handleDownload() {
    setDownloadLoading(true);

    try {
      const query = guestToken
        ? `?guest_token=${encodeURIComponent(guestToken)}`
        : "";

      await downloadFile(
        `/api/export/uploads/dataset/${upload.id}${query}`,
        upload.file?.name ?? `dataset-upload-${upload.id}`,
      );
    } catch {
      addToast({
        title: "Export failed",
        description: "An error occurred while preparing the file. Please try again later.",
        color: "danger",
        shouldShowTimeoutProgress: true,
        timeout: 6000,
      });
    } finally {
      setDownloadLoading(false);
    }
  }

  return (
    <div className="flex flex-col gap-4 rounded-xl border border-default-200 p-4">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <div>
          <div className="font-semibold">
            {upload.dataset?.name ?? "Unnamed"} (#{upload.id})
          </div>
          <div className="text-xs text-foreground-500">
            {formatText(upload.file?.name, "-")}
          </div>
        </div>
        <span
          className={`w-fit rounded-md px-2 py-1 text-xs ${
            upload.state_phase.toString().toLowerCase() === "error" ||
            upload.state_phase.toString().toLowerCase() === "canceled"
              ? "bg-danger-100 text-danger-700"
              : upload.state_phase.toString().toLowerCase() === "done"
                ? "bg-success-100 text-success-700"
                : "bg-warning-100 text-warning-700"
          }`}
        >
          {formatText(upload.state_label)}
        </span>
      </div>

      {upload.processing_progress && (
        <div className="flex flex-col gap-1">
          <Progress
            aria-label="Processing progress"
            color="primary"
            value={upload.processing_progress.percent ?? 0}
          />
          <span className="text-xs text-foreground-500">
            {upload.processing_progress.processed_rows}
            {upload.processing_progress.total_rows
              ? ` / ${upload.processing_progress.total_rows}`
              : ""}{" "}
            rows ({upload.processing_progress.percent ?? 0}%)
          </span>
        </div>
      )}

      {upload.last_message && (
        <p className="text-sm text-foreground-600">{upload.last_message}</p>
      )}

      {upload.logs.length > 0 && (
        <div>
          <Button
            size="sm"
            variant="light"
            className="w-fit px-0 text-xs text-primary"
            onPress={() => setIsLogsModalOpen(true)}
          >
            Logs ({upload.logs.length})
          </Button>
        </div>
      )}

      <div className="flex flex-wrap gap-2">
        {upload.file && (
          <Button
            size="sm"
            variant="flat"
            isLoading={downloadLoading}
            onPress={handleDownload}
          >
            Download file
          </Button>
        )}

        {upload.can_configure && (
          <Button size="sm" color="primary" variant="flat" onPress={openConfigure}>
            Configure
          </Button>
        )}

        {upload.can_revert && (
          <Button
            size="sm"
            color="warning"
            variant="flat"
            isLoading={isReverting}
            onPress={revertUpload}
          >
            Revert
          </Button>
        )}

        {upload.can_cancel && (
          <Button
            size="sm"
            color="danger"
            variant="flat"
            isLoading={isCanceling}
            onPress={cancelUpload}
          >
            Cancel
          </Button>
        )}
      </div>

      {upload.can_reupload && (
        <div className="flex flex-col gap-2">
          <input
            type="file"
            accept=".csv,.txt,.tsv,.xls,.xlsx,.json"
            onChange={(event) => {
              setReuploadFile(event.target.files?.[0] ?? null);
              setReuploadErrors([]);
            }}
            className="text-xs"
          />
          <Button
            size="sm"
            color="danger"
            isLoading={isReuploading}
            isDisabled={!reuploadFile}
            onPress={reupload}
            className="w-fit"
          >
            Reupload
          </Button>
          {reuploadErrors.length > 0 && (
            <div className="flex flex-col gap-1">
              {reuploadErrors.map((error, index) => (
                <div
                  key={`reupload-error-${index}`}
                  className="rounded-md bg-danger-100 px-2 py-1 text-xs text-danger-700"
                >
                  {error}
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      <Modal
        isOpen={configureState.isOpen}
        onOpenChange={(open) => {
          if (!open) {
            setConfigureState(INITIAL_CONFIGURE_STATE);
          }
        }}
        size="5xl"
        scrollBehavior="inside"
      >
        <ModalContent>
          <ModalHeader>Configure uploaded file</ModalHeader>
          <ModalBody className="flex flex-col gap-4">
          {configureState.isLoading ? (
            <div className="flex flex-row items-center gap-2 text-sm">
              <Spinner size="sm" />
              Loading preview...
            </div>
          ) : (
            <>
              <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div className="flex flex-col gap-1">
                  <label className="text-sm font-semibold">Delimiter</label>
                  <select
                    className="rounded-lg border border-default-300 bg-background px-3 py-2 text-sm"
                    value={normalizeSeparatorLabel(configureState.separator)}
                    onChange={async (event) => {
                      const value = denormalizeSeparatorValue(event.target.value);
                      setConfigureState((prev) => ({
                        ...prev,
                        separator: value,
                        isValidated: false,
                      }));
                      await loadConfigurePreview(value, configureState.skipFirstRow, 1);
                    }}
                  >
                    <option value=",">Comma (,)</option>
                    <option value=";">Semicolon (;)</option>
                    <option value="\\t">Tab (\t)</option>
                  </select>
                </div>

                <div className="flex flex-col gap-1">
                  <label className="text-sm font-semibold">Skip first row</label>
                  <select
                    className="rounded-lg border border-default-300 bg-background px-3 py-2 text-sm"
                    value={String(configureState.skipFirstRow)}
                    onChange={async (event) => {
                      const value = Number(event.target.value);
                      setConfigureState((prev) => ({
                        ...prev,
                        skipFirstRow: value,
                        isValidated: false,
                      }));
                      await loadConfigurePreview(configureState.separator, value, 1);
                    }}
                  >
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                  </select>
                </div>
              </div>

              <div className="text-xs text-foreground-500">
                Preview shows first 5 rows. Total rows: {configureState.totalRows}
              </div>

              {configureState.previewRows.length > 0 && (
                <div className="overflow-x-auto rounded-lg border border-default-200">
                  <table className="w-full text-xs">
                    <thead className="bg-default-100">
                      <tr>
                        <th className="min-w-16 p-2 text-left">Line</th>
                        {configureState.columnMapping.map((selected, index) => {
                          const validOptions =
                            configureState.columnValidTypes[index] ??
                            configureState.columnTypeOptions;

                          return (
                            <th key={`map-${index}`} className="min-w-48 p-2">
                              <select
                                value={selected}
                                className="w-full rounded-md border border-default-300 bg-background px-2 py-1"
                                onChange={(event) => {
                                  const value = event.target.value;
                                  const nextMapping = [...configureState.columnMapping];
                                  nextMapping[index] = value;

                                  setConfigureState((prev) => ({
                                    ...prev,
                                    columnMapping: nextMapping,
                                    columnValidTypes: buildColumnValidTypes(
                                      nextMapping,
                                      prev.columnTypeOptions,
                                    ),
                                    isValidated: false,
                                  }));
                                }}
                              >
                                {Object.entries(validOptions).map(([value, label]) => (
                                  <option key={`${index}-${value}`} value={value}>
                                    {label}
                                  </option>
                                ))}
                              </select>
                            </th>
                          );
                        })}
                      </tr>
                    </thead>
                    <tbody>
                      {configureState.previewRows.map((row, rowIndex) => (
                        <tr key={`preview-row-${rowIndex}`} className="border-t border-default-200">
                          <td className="p-2">
                            {configureState.startLine + rowIndex + configureState.skipFirstRow}
                          </td>
                          {row.map((value, index) => (
                            <td key={`preview-cell-${rowIndex}-${index}`} className="p-2">
                              <div className="max-w-64 truncate" title={value}>
                                {value}
                              </div>
                            </td>
                          ))}
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}

              {configureState.errors.length > 0 && (
                <div className="flex flex-col gap-1">
                  {configureState.errors.map((error, index) => (
                    <div
                      key={`config-error-${index}`}
                      className="rounded-md bg-danger-100 px-2 py-1 text-xs text-danger-700"
                    >
                      {error}
                    </div>
                  ))}
                </div>
              )}

              {configureState.warnings.length > 0 && (
                <div className="flex flex-col gap-1">
                  {configureState.warnings.map((warning, index) => (
                    <div
                      key={`config-warning-${index}`}
                      className="rounded-md bg-warning-100 px-2 py-1 text-xs text-warning-700"
                    >
                      {warning}
                    </div>
                  ))}
                </div>
              )}
            </>
          )}
          </ModalBody>
          <ModalFooter>
            <Button
              variant="flat"
              onPress={() => setConfigureState(INITIAL_CONFIGURE_STATE)}
            >
              Close
            </Button>
            <Button
              color="warning"
              isLoading={configureState.isValidating}
              isDisabled={configureState.isLoading || configureState.isValidating}
              onPress={validateConfiguration}
            >
              Validate
            </Button>
            {upload.can_enqueue && (
              <Button
                color="success"
                isLoading={isEnqueuing}
                isDisabled={!configureState.isValidated}
                onPress={sendToQueue}
              >
                Start upload
              </Button>
            )}
          </ModalFooter>
        </ModalContent>
      </Modal>

      <Modal
        isOpen={isLogsModalOpen}
        onOpenChange={setIsLogsModalOpen}
        size="4xl"
        scrollBehavior="inside"
      >
        <ModalContent>
          <ModalHeader>Upload logs #{upload.id}</ModalHeader>
          <ModalBody className="flex flex-col gap-3">
            <div className="grid grid-cols-1 gap-2 text-xs md:grid-cols-3">
              <div className="flex flex-col gap-1">
                <span className="text-foreground-500">Dataset</span>
                <span className="font-semibold">
                  {upload.dataset?.name ?? "Unnamed"}
                </span>
              </div>
              <div className="flex flex-col gap-1">
                <span className="text-foreground-500">File</span>
                <span className="font-semibold">
                  {upload.file?.name ?? "-"}
                </span>
              </div>
              <div className="flex flex-col gap-1">
                <span className="text-foreground-500">State</span>
                <span className="font-semibold">
                  {formatText(upload.state_label)}
                </span>
              </div>
            </div>

            <div className="flex flex-col gap-2">
              {sortedLogs().length ? (
                sortedLogs().map((log, index) => {
                  const payload = formatLogPayload(log.payload);

                  return (
                    <div
                      key={`${upload.id}-modal-log-${index}`}
                      className={`rounded-md border px-3 py-2 text-xs ${logToneClass(log)}`}
                    >
                      <div className="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                        <div className="font-semibold">
                          {formatText(log.type, "LOG")} ·{" "}
                          {formatText(log.state_label)}
                        </div>
                        <div className="text-current opacity-75">
                          {formatLogTimestamp(log.timestamp)}
                        </div>
                      </div>
                      <div className="mt-2 whitespace-pre-wrap break-words">
                        {formatText(log.message)}
                      </div>
                      {payload && (
                        <pre className="mt-2 max-h-56 overflow-auto rounded-md bg-background/70 px-3 py-2 text-[11px] leading-relaxed text-foreground">
                          {payload}
                        </pre>
                      )}
                    </div>
                  );
                })
              ) : (
                <p className="text-sm text-foreground-500">No logs available.</p>
              )}
            </div>
          </ModalBody>
          <ModalFooter>
            <Button variant="flat" onPress={() => setIsLogsModalOpen(false)}>
              Close
            </Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </div>
  );
}
