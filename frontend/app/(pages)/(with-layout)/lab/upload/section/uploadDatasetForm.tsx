"use client";

import Turnstile from "@/components/auth/Turnstile";
import { getJson, postForm } from "@/lib/api/admin";
import {
  Autocomplete,
  AutocompleteItem,
  Alert,
  Button,
  Divider,
  Input,
  Modal,
  ModalBody,
  ModalContent,
  ModalFooter,
  ModalHeader,
  Spinner,
  Textarea,
} from "@heroui/react";
import { useEffect, useState } from "react";
import { MdCloudUpload } from "react-icons/md";

const turnstileSiteKey = process.env.NEXT_PUBLIC_TURNSTILE_SITE_KEY ?? "";

type SelectOption = {
  id: number;
  abbreviation?: string;
  name?: string;
};

type PublicationLookupOption = {
  provider: "europe_pmc";
  pmid: string;
  identifier_source?: string;
  citation?: string;
  title?: string;
  journal?: string;
  year?: number | string;
  is_local?: boolean;
};

type MyUpload = {
  id: number;
  state: number;
  state_label: string;
  state_phase: string;
  can_reupload: boolean;
  can_configure?: boolean;
  can_enqueue?: boolean;
  can_revert?: boolean;
  can_cancel?: boolean;
  dataset?: {
    id?: number;
    name?: string;
    type?: string;
    membrane?: string;
    method?: string;
  };
  publication?: string | null;
  file?: {
    id?: number;
    name?: string;
    mime?: string;
  };
  last_message?: string | null;
  config?: {
    separator?: string | null;
    skip_first_row?: number | null;
    attributes?: string[] | null;
    quick_validation_ok?: boolean;
    quick_validation_at?: string | null;
  };
  logs?: Array<{
    message: string;
    context: string;
    type: string;
    state: number | null;
    payload?: Record<string, any> | null;
    timestamp?: string;
  }>;
  created_at?: string;
  updated_at?: string;
};

type ActionState = {
  status: number;
  message: string;
  data?: Record<string, unknown> | null;
};

type ConfigureState = {
  isOpen: boolean;
  recordId: number | null;
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
  isEnqueuing: boolean;
  isValidated: boolean;
};

function useDebouncedValue(value: string, delay = 300): string {
  const [debouncedValue, setDebouncedValue] = useState(value);

  useEffect(() => {
    const timeout = window.setTimeout(() => {
      setDebouncedValue(value);
    }, delay);

    return () => window.clearTimeout(timeout);
  }, [value, delay]);

  return debouncedValue;
}

export default function UploadDatasetForm() {
  const [actionState, setActionState] = useState<ActionState | null>(null);
  const [isPending, setIsPending] = useState(false);

  const [turnstileToken, setTurnstileToken] = useState<string | null>(null);
  const [datasetType, setDatasetType] = useState("1");
  const [datasetName, setDatasetName] = useState("");
  const [comment, setComment] = useState("");
  const [membraneQuery, setMembraneQuery] = useState("");
  const [methodQuery, setMethodQuery] = useState("");
  const [publicationQuery, setPublicationQuery] = useState("");

  const [membraneOptions, setMembraneOptions] = useState<SelectOption[]>([]);
  const [methodOptions, setMethodOptions] = useState<SelectOption[]>([]);
  const [publicationOptions, setPublicationOptions] = useState<
    PublicationLookupOption[]
  >([]);
  const [selectedMembrane, setSelectedMembrane] = useState<SelectOption | null>(
    null,
  );
  const [selectedMethod, setSelectedMethod] = useState<SelectOption | null>(
    null,
  );
  const [selectedPublication, setSelectedPublication] =
    useState<PublicationLookupOption | null>(null);
  const [isMembraneLoading, setIsMembraneLoading] = useState(false);
  const [isMethodLoading, setIsMethodLoading] = useState(false);
  const [isPublicationLoading, setIsPublicationLoading] = useState(false);

  const debouncedMembraneQuery = useDebouncedValue(membraneQuery);
  const debouncedMethodQuery = useDebouncedValue(methodQuery);
  const debouncedPublicationQuery = useDebouncedValue(publicationQuery);

  const [myUploads, setMyUploads] = useState<MyUpload[]>([]);
  const [isLoadingUploads, setIsLoadingUploads] = useState(false);
  const [reuploadFiles, setReuploadFiles] = useState<
    Record<number, File | null>
  >({});
  const [reuploadLoading, setReuploadLoading] = useState<
    Record<number, boolean>
  >({});

  const [selectedMembraneId, setSelectedMembraneId] = useState("");
  const [selectedMethodId, setSelectedMethodId] = useState("");
  const [configureState, setConfigureState] = useState<ConfigureState>({
    isOpen: false,
    recordId: null,
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
    isEnqueuing: false,
    isValidated: false,
  });

  const membraneLabel = (option: SelectOption): string =>
    `${option.abbreviation ?? ""} - ${option.name ?? ""}`;
  const methodLabel = (option: SelectOption): string =>
    `${option.abbreviation ?? ""} - ${option.name ?? ""}`;
  const publicationLabel = (option: PublicationLookupOption): string =>
    `PMID ${option.pmid} - ${option.citation ?? option.title ?? "Untitled"}`;

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

  useEffect(() => {
    setIsMembraneLoading(true);
    getJson("/api/lab/upload/membranes", {
      query: debouncedMembraneQuery,
    })
      .then((response) => {
        if (response?.code === 200) {
          const loadedOptions: SelectOption[] = response.data?.data ?? [];
          if (
            selectedMembrane &&
            !loadedOptions.some((option) => option.id === selectedMembrane.id)
          ) {
            setMembraneOptions([selectedMembrane, ...loadedOptions]);
            return;
          }

          setMembraneOptions(loadedOptions);
          return;
        }
        setMembraneOptions([]);
      })
      .catch(() => setMembraneOptions([]))
      .finally(() => setIsMembraneLoading(false));
  }, [debouncedMembraneQuery, selectedMembrane]);

  useEffect(() => {
    setIsMethodLoading(true);
    getJson("/api/lab/upload/methods", {
      query: debouncedMethodQuery,
    })
      .then((response) => {
        if (response?.code === 200) {
          const loadedOptions: SelectOption[] = response.data?.data ?? [];
          if (
            selectedMethod &&
            !loadedOptions.some((option) => option.id === selectedMethod.id)
          ) {
            setMethodOptions([selectedMethod, ...loadedOptions]);
            return;
          }

          setMethodOptions(loadedOptions);
          return;
        }
        setMethodOptions([]);
      })
      .catch(() => setMethodOptions([]))
      .finally(() => setIsMethodLoading(false));
  }, [debouncedMethodQuery, selectedMethod]);

  useEffect(() => {
    if (debouncedPublicationQuery.trim().length < 3) {
      setPublicationOptions(selectedPublication ? [selectedPublication] : []);
      return;
    }

    setIsPublicationLoading(true);
    getJson("/api/lab/upload/publications/lookup", {
      query: debouncedPublicationQuery,
    })
      .then((response) => {
        if (response?.code === 200) {
          const loadedOptions: PublicationLookupOption[] =
            response.data?.data ?? [];
          if (
            selectedPublication &&
            !loadedOptions.some(
              (option) => option.pmid === selectedPublication.pmid,
            )
          ) {
            setPublicationOptions([selectedPublication, ...loadedOptions]);
            return;
          }

          setPublicationOptions(loadedOptions);
          return;
        }
        setPublicationOptions([]);
      })
      .catch(() => setPublicationOptions([]))
      .finally(() => setIsPublicationLoading(false));
  }, [debouncedPublicationQuery, selectedPublication]);

  async function loadMyUploads() {
    setIsLoadingUploads(true);
    try {
      const response = await getJson("/api/lab/upload/my-uploads", {
        per_page: 50,
      });

      if (response?.code === 200) {
        setMyUploads(response.data?.data?.data ?? []);
      } else {
        setMyUploads([]);
      }
    } catch (error) {
      console.error(error);
      setMyUploads([]);
    }
    setIsLoadingUploads(false);
  }

  useEffect(() => {
    loadMyUploads();
  }, []);

  useEffect(() => {
    if (actionState?.status === 201) {
      loadMyUploads();
      setPublicationQuery("");
      setPublicationOptions([]);
      setMembraneQuery("");
      setMethodQuery("");
      setMembraneOptions([]);
      setMethodOptions([]);
      setSelectedMembrane(null);
      setSelectedMethod(null);
      setSelectedPublication(null);
      setSelectedMembraneId("");
      setSelectedMethodId("");
      setDatasetType("1");
      setDatasetName("");
      setComment("");
    }
  }, [actionState?.status]);

  async function reupload(recordId: number) {
    const file = reuploadFiles[recordId];
    if (!file) {
      return;
    }

    setReuploadLoading((prev) => ({ ...prev, [recordId]: true }));
    const payload = new FormData();
    payload.append("file", file);

    try {
      const response = await postForm(
        `/api/lab/upload/${recordId}/reupload`,
        payload,
      );

      if (!response.ok) {
        const json = await response.json();
        console.error(json);
      }
    } catch (error) {
      console.error(error);
    }

    setReuploadLoading((prev) => ({ ...prev, [recordId]: false }));
    setReuploadFiles((prev) => ({ ...prev, [recordId]: null }));
    await loadMyUploads();
  }

  async function loadConfigurePreview(
    recordId: number,
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

      const response = await fetch(
        `/api/lab/upload/${recordId}/configure/preview?${query.toString()}`,
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
    } catch (error) {
      console.error(error);
      setConfigureState((prev) => ({
        ...prev,
        isLoading: false,
        errors: ["Failed to load preview."],
      }));
    }
  }

  async function openConfigure(upload: MyUpload) {
    const separator = denormalizeSeparatorValue(upload.config?.separator ?? ",");
    const skipFirstRow = upload.config?.skip_first_row ?? 1;

    setConfigureState((prev) => ({
      ...prev,
      isOpen: true,
      recordId: upload.id,
      separator,
      skipFirstRow,
      startLine: 1,
      totalRows: 0,
      previewRows: [],
      columnMapping: [],
      columnTypeOptions: {},
      columnValidTypes: [],
      errors: [],
      warnings: [],
      isValidated: !!upload.config?.quick_validation_ok,
    }));

    await loadConfigurePreview(upload.id, separator, skipFirstRow, 1);
  }

  function closeConfigure() {
    setConfigureState((prev) => ({ ...prev, isOpen: false }));
  }

  async function validateConfiguration() {
    if (!configureState.recordId) {
      return;
    }

    setConfigureState((prev) => ({
      ...prev,
      isValidating: true,
      errors: [],
      warnings: [],
    }));

    try {
      const payload = new FormData();
      payload.append("separator", normalizeSeparatorLabel(configureState.separator));
      payload.append("skip_first_row", String(configureState.skipFirstRow));
      configureState.columnMapping.forEach((value, index) => {
        payload.append(`attributes[${index}]`, value);
      });

      const response = await fetch(
        `/api/lab/upload/${configureState.recordId}/configure/validate`,
        {
          method: "POST",
          body: payload,
        },
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

      setActionState({
        status: 200,
        message: json?.message ?? "Configuration validated.",
        data: json?.data ?? null,
      });
      await loadMyUploads();
    } catch (error) {
      console.error(error);
      setConfigureState((prev) => ({
        ...prev,
        isValidating: false,
        isValidated: false,
        errors: ["Validation failed unexpectedly."],
      }));
    }
  }

  async function sendToQueue(recordId: number) {
    const isConfirmed = window.confirm(
      "Move this record to pending upload state? This action is irreversible.",
    );
    if (!isConfirmed) {
      return;
    }

    setConfigureState((prev) => ({ ...prev, isEnqueuing: true }));

    try {
      const response = await fetch(`/api/lab/upload/${recordId}/enqueue`, {
        method: "POST",
      });
      const json = await response.json();

      if (!response.ok) {
        setActionState({
          status: response.status,
          message: json?.message ?? "Failed to start upload.",
          data: json?.errors ?? json?.data ?? null,
        });
        setConfigureState((prev) => ({ ...prev, isEnqueuing: false }));
        return;
      }

      setActionState({
        status: 200,
        message: json?.message ?? "Record moved to pending upload queue.",
        data: json?.data ?? null,
      });
      setConfigureState((prev) => ({ ...prev, isEnqueuing: false, isOpen: false }));
      await loadMyUploads();
    } catch (error) {
      console.error(error);
      setActionState({
        status: 500,
        message: "Failed to start upload.",
        data: null,
      });
      setConfigureState((prev) => ({ ...prev, isEnqueuing: false }));
    }
  }

  async function revertUpload(recordId: number) {
    try {
      const response = await fetch(`/api/lab/upload/${recordId}/revert`, {
        method: "POST",
      });
      const json = await response.json();

      setActionState({
        status: response.ok ? 200 : response.status,
        message:
          json?.message ??
          (response.ok
            ? "Record reverted to configuration state."
            : "Failed to revert record."),
        data: json?.data ?? json?.errors ?? null,
      });
    } catch (error) {
      console.error(error);
      setActionState({
        status: 500,
        message: "Failed to revert record.",
        data: null,
      });
    }

    await loadMyUploads();
  }

  async function cancelUpload(recordId: number) {
    const isConfirmed = window.confirm(
      "Cancel this upload? Uploaded file will be permanently deleted and this action cannot be undone.",
    );
    if (!isConfirmed) {
      return;
    }

    try {
      const response = await fetch(`/api/lab/upload/${recordId}/cancel`, {
        method: "POST",
      });
      const json = await response.json();

      setActionState({
        status: response.ok ? 200 : response.status,
        message:
          json?.message ??
          (response.ok
            ? "Upload canceled. Uploaded file was permanently deleted."
            : "Failed to cancel upload."),
        data: json?.data ?? json?.errors ?? null,
      });
    } catch (error) {
      console.error(error);
      setActionState({
        status: 500,
        message: "Failed to cancel upload.",
        data: null,
      });
    }

    await loadMyUploads();
  }

  function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    setIsPending(true);

    fetch("/api/lab/upload/submit", {
      method: "POST",
      body: formData,
    })
      .then(async (response) => {
        const json = await response.json();

        if (response.status === 201) {
          setActionState({
            status: 201,
            message:
              json?.message ??
              "Upload request has been accepted and queued for processing.",
            data: json?.data ?? null,
          });

          return;
        }

        setActionState({
          status: response.status,
          message:
            json?.message ??
            "Upload request failed. Please check the form and try again.",
          data: json?.errors ?? json?.data ?? null,
        });
      })
      .catch((error) => {
        console.error(error);
        setActionState({
          status: 500,
          message: "Unexpected error occurred while uploading data.",
          data: null,
        });
      })
      .finally(() => setIsPending(false));
  }

  return (
    <div className="w-full max-w-4xl mx-auto bg-foreground-100 rounded-2xl p-6 lg:p-8">
      <h2 className="text-2xl font-bold">Upload computed dataset</h2>
      <p className="text-sm text-warning-700 mt-1">
        All submissions will be reviewed by our team before being published on
        the platform. Please allow up to 5 business days for the review process.
        You will receive notifications about the status of your submission via
        email.
      </p>

      <form onSubmit={handleSubmit} className="pt-6 flex flex-col gap-5">
        {actionState?.message && (
          <Alert
            color={actionState.status === 201 ? "success" : "danger"}
            title={actionState.message}
          />
        )}

        <div>
          <label className="font-semibold text-sm">Dataset setting</label>
          <select
            name="dataset_type"
            className="mt-1 w-full rounded-lg border border-default-300 bg-background px-3 py-2"
            value={datasetType}
            onChange={(event) => setDatasetType(event.target.value)}
            required
          >
            <option value="1">Passive interactions</option>
            <option value="2">Active interactions</option>
          </select>
        </div>

        <Input
          label="Dataset name (optional)"
          name="dataset_name"
          placeholder="e.g. Lipophilicity dataset for DOPC"
          value={datasetName}
          onValueChange={setDatasetName}
          maxLength={255}
        />

        <Textarea
          label="Comment (optional)"
          name="comment"
          placeholder="Any context or notes for reviewers"
          value={comment}
          onValueChange={setComment}
          maxLength={1000}
        />

        <Divider />

        <div className="flex flex-col gap-2">
          <Autocomplete
            label="Membrane"
            placeholder="Type to search membrane..."
            selectedKey={selectedMembraneId || null}
            inputValue={membraneQuery}
            onInputChange={(value) => {
              if (
                selectedMembrane &&
                value !== membraneLabel(selectedMembrane)
              ) {
                setSelectedMembrane(null);
                setSelectedMembraneId("");
              }
              setMembraneQuery(value);
            }}
            onSelectionChange={(key) => {
              const selected = membraneOptions.find(
                (option) => String(option.id) === String(key),
              );
              setSelectedMembraneId(key ? String(key) : "");
              setSelectedMembrane(selected ?? null);
              setMembraneQuery(selected ? membraneLabel(selected) : "");
            }}
            isLoading={isMembraneLoading}
            items={membraneOptions}
            isRequired
          >
            {(option) => (
              <AutocompleteItem
                key={String(option.id)}
                textValue={`${option.abbreviation ?? ""} ${option.name ?? ""}`.trim()}
              >
                {option.abbreviation} - {option.name}
              </AutocompleteItem>
            )}
          </Autocomplete>
          <input type="hidden" name="membrane_id" value={selectedMembraneId} />
        </div>

        <div className="flex flex-col gap-2">
          <Autocomplete
            label="Method"
            placeholder="Type to search method..."
            selectedKey={selectedMethodId || null}
            inputValue={methodQuery}
            onInputChange={(value) => {
              if (selectedMethod && value !== methodLabel(selectedMethod)) {
                setSelectedMethod(null);
                setSelectedMethodId("");
              }
              setMethodQuery(value);
            }}
            onSelectionChange={(key) => {
              const selected = methodOptions.find(
                (option) => String(option.id) === String(key),
              );
              setSelectedMethodId(key ? String(key) : "");
              setSelectedMethod(selected ?? null);
              setMethodQuery(selected ? methodLabel(selected) : "");
            }}
            isLoading={isMethodLoading}
            items={methodOptions}
            isRequired
          >
            {(option) => (
              <AutocompleteItem
                key={String(option.id)}
                textValue={`${option.abbreviation ?? ""} ${option.name ?? ""}`.trim()}
              >
                {option.abbreviation} - {option.name}
              </AutocompleteItem>
            )}
          </Autocomplete>
          <input type="hidden" name="method_id" value={selectedMethodId} />
        </div>

        <Divider />

        <div className="flex flex-col gap-3">
          <Autocomplete
            label="Secondary reference"
            placeholder="Type PMID, title or citation..."
            selectedKey={selectedPublication?.pmid ?? null}
            inputValue={publicationQuery}
            onInputChange={(value) => {
              if (
                selectedPublication &&
                value !== publicationLabel(selectedPublication)
              ) {
                setSelectedPublication(null);
              }
              setPublicationQuery(value);
            }}
            onSelectionChange={(key) => {
              const selected = publicationOptions.find(
                (option) => option.pmid === String(key),
              );
              setSelectedPublication(selected ?? null);
              setPublicationQuery(selected ? publicationLabel(selected) : "");
            }}
            isLoading={isPublicationLoading}
            items={publicationOptions}
            isRequired
            description="Start typing (min. 3 characters). We store PMID and create publication on upload when missing."
          >
            {(option) => (
              <AutocompleteItem
                key={option.pmid}
                textValue={publicationLabel(option)}
              >
                {publicationLabel(option)}
              </AutocompleteItem>
            )}
          </Autocomplete>
          <input
            type="hidden"
            name="publication_pmid"
            value={selectedPublication?.pmid ?? ""}
          />
          <input
            type="hidden"
            name="publication_lookup_provider"
            value={selectedPublication?.provider ?? ""}
          />
          <input
            type="hidden"
            name="publication_lookup_source"
            value={selectedPublication?.identifier_source ?? "MED"}
          />
        </div>

        <Divider />

        <div className="flex flex-col gap-2">
          <label className="font-semibold text-sm">Upload file</label>
          <input
            type="file"
            name="file"
            required
            className="w-full rounded-lg border border-default-300 bg-background px-3 py-2"
            accept=".csv,.txt,.tsv,.xls,.xlsx,.json"
          />
          <p className="text-xs text-foreground-500">
            Supported formats: CSV, TSV, TXT, XLS, XLSX, JSON (max 20 MB).
          </p>
        </div>

        <Turnstile
          name="turnstile_token"
          siteKey={turnstileSiteKey}
          onVerify={setTurnstileToken}
        />

        <Button
          type="submit"
          color="primary"
          size="lg"
          isLoading={isPending}
          isDisabled={!turnstileSiteKey || !turnstileToken || isPending}
          startContent={<MdCloudUpload />}
          className="text-white"
        >
          Submit upload request
        </Button>
      </form>

      <Divider className="my-8" />

      <div className="flex flex-col gap-4">
        <div className="flex flex-row justify-between items-center">
          <h3 className="text-xl font-bold">My uploads</h3>
          <Button
            size="sm"
            variant="flat"
            onPress={loadMyUploads}
            isLoading={isLoadingUploads}
          >
            Refresh
          </Button>
        </div>

        {myUploads.length === 0 ? (
          <p className="text-sm text-foreground-500">
            No uploads found for your account yet.
          </p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm border border-default-200 rounded-lg">
              <thead className="bg-default-100">
                <tr>
                  <th className="text-left p-2">ID</th>
                  <th className="text-left p-2">Dataset</th>
                  <th className="text-left p-2">File</th>
                  <th className="text-left p-2">State</th>
                  <th className="text-left p-2">Last message</th>
                  <th className="text-left p-2">Action</th>
                </tr>
              </thead>
              <tbody>
                {myUploads.map((upload) => (
                  <tr key={upload.id} className="border-t border-default-200">
                    <td className="p-2">#{upload.id}</td>
                    <td className="p-2">
                      <div className="font-semibold">
                        {upload.dataset?.name ?? "Unnamed"}
                      </div>
                      <div className="text-foreground-500">
                        {upload.dataset?.type} | {upload.dataset?.membrane} |{" "}
                        {upload.dataset?.method}
                      </div>
                    </td>
                    <td className="p-2">{upload.file?.name ?? "-"}</td>
                    <td className="p-2">
                      <span
                        className={`px-2 py-1 rounded-md text-xs ${
                          upload.state_phase === "error"
                            ? "bg-danger-100 text-danger-700"
                            : upload.state_phase === "done"
                              ? "bg-success-100 text-success-700"
                              : "bg-warning-100 text-warning-700"
                        }`}
                      >
                        {upload.state_label}
                      </span>
                    </td>
                    <td className="p-2 max-w-72 truncate">
                      <div className="flex flex-col gap-1">
                        <span>{upload.last_message ?? "-"}</span>
                        {upload.logs && upload.logs.length > 0 && (
                          <details>
                            <summary className="text-xs cursor-pointer text-primary">
                              Show logs ({upload.logs.length})
                            </summary>
                            <div className="mt-2 flex flex-col gap-1">
                              {upload.logs.map((log, index) => (
                                <div
                                  key={`${upload.id}-log-${index}`}
                                  className={`text-xs rounded px-2 py-1 ${
                                    log.state === 3
                                      ? "bg-danger-100 text-danger-700"
                                      : log.state === 2
                                        ? "bg-success-100 text-success-700"
                                        : "bg-default-100 text-default-700"
                                  }`}
                                >
                                  <div className="font-semibold">
                                    [{log.type}] state={log.state ?? "-"}{" "}
                                    {log.timestamp ? `| ${log.timestamp}` : ""}
                                  </div>
                                  <div>{log.message}</div>
                                </div>
                              ))}
                            </div>
                          </details>
                        )}
                      </div>
                    </td>
                    <td className="p-2">
                      <div className="flex flex-col gap-2">
                        {upload.can_configure && (
                          <Button
                            size="sm"
                            color="primary"
                            variant="flat"
                            onPress={() => openConfigure(upload)}
                          >
                            Configure
                          </Button>
                        )}

                        {upload.can_enqueue && (
                          <Button
                            size="sm"
                            color="success"
                            onPress={() => sendToQueue(upload.id)}
                          >
                            Start upload
                          </Button>
                        )}

                        {upload.can_revert && (
                          <Button
                            size="sm"
                            color="warning"
                            variant="flat"
                            onPress={() => revertUpload(upload.id)}
                          >
                            Revert
                          </Button>
                        )}

                        {upload.can_cancel && (
                          <Button
                            size="sm"
                            color="danger"
                            variant="flat"
                            onPress={() => cancelUpload(upload.id)}
                          >
                            Cancel
                          </Button>
                        )}

                        {upload.can_reupload && (
                          <>
                            <input
                              type="file"
                              accept=".csv,.txt,.tsv,.xls,.xlsx,.json"
                              onChange={(e) => {
                                const file = e.target.files?.[0] ?? null;
                                setReuploadFiles((prev) => ({
                                  ...prev,
                                  [upload.id]: file,
                                }));
                              }}
                              className="text-xs"
                            />
                            <Button
                              size="sm"
                              color="danger"
                              isLoading={!!reuploadLoading[upload.id]}
                              isDisabled={!reuploadFiles[upload.id]}
                              onPress={() => reupload(upload.id)}
                            >
                              Reupload
                            </Button>
                          </>
                        )}

                        {!upload.can_reupload &&
                          !upload.can_configure &&
                          !upload.can_enqueue &&
                          !upload.can_revert &&
                          !upload.can_cancel && (
                            <span className="text-foreground-500">-</span>
                          )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      <Modal
        isOpen={configureState.isOpen}
        onOpenChange={(open) => {
          if (!open) {
            closeConfigure();
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
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
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

                        if (configureState.recordId) {
                          await loadConfigurePreview(
                            configureState.recordId,
                            value,
                            configureState.skipFirstRow,
                            1,
                          );
                        }
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

                        if (configureState.recordId) {
                          await loadConfigurePreview(
                            configureState.recordId,
                            configureState.separator,
                            value,
                            1,
                          );
                        }
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
                  <div className="overflow-x-auto border border-default-200 rounded-lg">
                    <table className="w-full text-xs">
                      <thead className="bg-default-100">
                        <tr>
                          <th className="p-2 text-left min-w-16">Line</th>
                          {configureState.columnMapping.map((selected, index) => {
                            const validOptions =
                              configureState.columnValidTypes[index] ??
                              configureState.columnTypeOptions;

                            return (
                              <th key={`map-${index}`} className="p-2 min-w-48">
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
                            <td className="p-2">{configureState.startLine + rowIndex}</td>
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
                        className="rounded-md bg-danger-100 text-danger-700 text-xs px-2 py-1"
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
                        className="rounded-md bg-warning-100 text-warning-700 text-xs px-2 py-1"
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
            <Button variant="flat" onPress={closeConfigure}>
              Close
            </Button>
            <Button
              color="warning"
              isLoading={configureState.isValidating}
              isDisabled={
                configureState.isLoading ||
                configureState.isValidating ||
                !configureState.recordId
              }
              onPress={validateConfiguration}
            >
              Validate
            </Button>
            <Button
              color="success"
              isLoading={configureState.isEnqueuing}
              isDisabled={
                !configureState.isValidated ||
                configureState.isEnqueuing ||
                !configureState.recordId
              }
              onPress={() => {
                if (configureState.recordId) {
                  sendToQueue(configureState.recordId);
                }
              }}
            >
              Start upload
            </Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </div>
  );
}
