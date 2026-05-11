"use client";

import Turnstile from "@/components/auth/Turnstile";
import { getJson } from "@/lib/api/admin";
import { PublicationLookupOption } from "@/lib/api/admin/interfaces/Publication";
import {
  Autocomplete,
  AutocompleteItem,
  Alert,
  Button,
  Divider,
  Input,
  Textarea,
} from "@heroui/react";
import { useEffect, useState } from "react";
import { MdCloudUpload } from "react-icons/md";
import { useHandle401 } from "@/lib/api/admin/redirections";
import MyUploadsList from "./myUploads";

const turnstileSiteKey = process.env.NEXT_PUBLIC_TURNSTILE_SITE_KEY ?? "";

type SelectOption = {
  id: number;
  abbreviation?: string;
  name?: string;
};

type ActionState = {
  status: number;
  message: string;
  data?: Record<string, unknown> | null;
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

  const [selectedMembraneId, setSelectedMembraneId] = useState("");
  const [selectedMethodId, setSelectedMethodId] = useState("");

  const [reloadKey, setReloadKey] = useState(0);

  const membraneLabel = (option: SelectOption): string =>
    `${option.abbreviation ?? ""} - ${option.name ?? ""}`;
  const methodLabel = (option: SelectOption): string =>
    `${option.abbreviation ?? ""} - ${option.name ?? ""}`;
  const publicationLabel = (option: PublicationLookupOption): string =>
    `PMID ${option.pmid} - ${option.citation ?? option.title ?? "Untitled"}`;

  const handle401 = useHandle401();

  useEffect(() => {
    setIsMembraneLoading(true);
    getJson("/api/lab/upload/membranes", {
      query: debouncedMembraneQuery,
    })
      .then((response) => {
        if(response?.code === 401) {
          handle401();
        }

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
        if(response?.code === 401) {
          handle401();
        }
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
        if(response?.code === 401) {
          handle401();
        }
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


  useEffect(() => {
    if (actionState?.status === 201) {
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
      setReloadKey(reloadKey + 1);
    }
  }, [actionState?.status]);
  

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
    <div className="w-full mx-auto bg-foreground-100 rounded-2xl p-6 lg:p-8">
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

        <div className={`flex flex-col gap-2 ${datasetType == '2' ? "hidden" : ""}`}>
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
            isRequired={datasetType == '1'}
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

        <div className={`flex flex-col gap-2 ${datasetType == '2' ? "hidden" : ""}`}>
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
            isRequired={datasetType == '1'}
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
            accept=".csv"
          />
          <p className="text-xs text-foreground-500">
            Supported formats: CSV (max 20 MB).
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

      <MyUploadsList reloadKey={reloadKey} />
    </div>
  );
}
