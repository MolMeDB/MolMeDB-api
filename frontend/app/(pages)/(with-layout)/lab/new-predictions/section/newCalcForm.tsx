"use client";

import {
  Alert,
  Button,
  Card,
  CardBody,
  CardFooter,
  CardHeader,
  Input,
  Modal,
  ModalContent,
  Textarea,
  addToast,
  useDisclosure,
} from "@heroui/react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useEffect, useRef, useState } from "react";
import { FaCheck } from "react-icons/fa6";
import { IoSend } from "react-icons/io5";
import Turnstile from "@/components/auth/Turnstile";

const turnstileSiteKey = process.env.NEXT_PUBLIC_TURNSTILE_SITE_KEY ?? "";

type EmailStep = "email" | "code" | "verified";

export default function AddNewCalculationForm({ isLoggedIn }: { isLoggedIn: boolean }) {
  const router = useRouter();

  const [emailStep, setEmailStep] = useState<EmailStep>(
    isLoggedIn ? "verified" : "email",
  );
  const [email, setEmail] = useState("");
  const [emailCode, setEmailCode] = useState("");
  const [emailTurnstileToken, setEmailTurnstileToken] = useState<string | null>(null);
  const [emailError, setEmailError] = useState<string | null>(null);
  const [isEmailSubmitting, setIsEmailSubmitting] = useState(false);
  const captchaKey = useRef(0);
  const [options, setOptions] = useState<{
    membranes: GridSelectionItemProps[];
    methods: GridSelectionItemProps[];
    structureValidation: {
      maxAtoms: number;
      allowedElements: string[];
      singleConnectedMolecule: boolean;
    };
  }>({
    membranes: [],
    methods: [],
    structureValidation: {
      maxAtoms: 120,
      allowedElements: [
        "C",
        "H",
        "O",
        "N",
        "P",
        "S",
        "F",
        "Cl",
        "Br",
        "I",
      ],
      singleConnectedMolecule: true,
    },
  });
  const [isLoadingOptions, setIsLoadingOptions] = useState(true);
  const [optionError, setOptionError] = useState<string | null>(null);
  const [setting, setSetting] = useState<{
    membranes: null | any[];
    methods: null | any[];
    description: null | string;
    smiles: null | string;
    validated_smiles?: string[];
    temperature: number;
  }>({
    membranes: null,
    methods: null,
    description: null,
    smiles: null,
    validated_smiles: undefined,
    temperature: 25,
  });

  const [isValidated, setIsValidated] = useState<boolean>(false);

  const [isValidating, setIsValidating] = useState<boolean>(false);
  const [isSaving, setIsSaving] = useState<boolean>(false);

  const [validatorErrors, setValidatorErrors] = useState<string[]>([]);
  const [validatingState, setValidatingState] =
    useState<string>("Please wait...");

  const { isOpen, onOpen, onOpenChange } = useDisclosure();

  useEffect(() => {
    let canceled = false;

    async function loadOptions() {
      setIsLoadingOptions(true);
      setOptionError(null);

      try {
        const response = await fetch("/api/predictions/options", {
          headers: {
            Accept: "application/json",
          },
        });
        const json = await response.json();

        if (!response.ok) {
          throw new Error(json?.message || "Unable to load prediction options.");
        }

        if (!canceled) {
          setOptions({
            membranes: json?.data?.membranes ?? [],
            methods: json?.data?.methods ?? [],
            structureValidation: {
              maxAtoms: json?.data?.structure_validation?.max_atoms ?? 120,
              allowedElements:
                json?.data?.structure_validation?.allowed_elements ??
                ["C", "H", "O", "N", "P", "S", "F", "Cl", "Br", "I"],
              singleConnectedMolecule:
                json?.data?.structure_validation?.single_connected_molecule ??
                true,
            },
          });
        }
      } catch (error) {
        if (!canceled) {
          setOptionError(
            error instanceof Error
              ? error.message
              : "Unable to load prediction options.",
          );
        }
      } finally {
        if (!canceled) {
          setIsLoadingOptions(false);
        }
      }
    }

    loadOptions();

    return () => {
      canceled = true;
    };
  }, []);

  useEffect(() => {
    setIsValidated(false);
    setValidatingState("Please wait...");
  }, [setting.membranes, setting.methods, setting.smiles, setting.temperature]);

  useEffect(() => {
    if (!setting.description) {
      setIsValidated(false);
      setValidatingState("Please wait...");
    }
  }, [setting.description]);

  async function validate() {
    setIsValidating(true);
    onOpen();
    const errors: string[] = [];

    // Sleep
    await new Promise((resolve) => setTimeout(resolve, 1000));

    // Validate membrane
    if (!setting.membranes || setting.membranes.length === 0) {
      errors.push("Please select at least one membrane.");
    }

    // Validate method
    if (!setting.methods || setting.methods.length === 0) {
      errors.push("Please select at least one method.");
    }

    // Validate description
    if (!setting.description || setting.description.trim().length === 0) {
      errors.push("Please provide a description for the calculation.");
    }

    // Validate SMILES
    let validatedSmiles: string[] = [];
    let totalDuplicates = 0;

    if (!setting.smiles || setting.smiles.trim().length === 0) {
      errors.push("Please provide at least one molecule in SMILES format.");
    } else if (errors.length === 0) {
      setValidatingState("Validating and canonicalizing SMILES...");
      const smiles = setting.smiles.split("\n");

      try {
        const response = await fetch("/api/predictions/validate-smiles", {
          method: "POST",
          headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ smiles }),
        });
        const json = await response.json();

        if (!response.ok) {
          const validationErrors = json?.errors?.smiles;
          errors.push(
            ...(Array.isArray(validationErrors)
              ? validationErrors.map(String)
              : [json?.message ?? "SMILES validation failed."]),
          );
        } else {
          validatedSmiles = json?.data?.smiles ?? [];
          totalDuplicates = json?.data?.duplicates_removed ?? 0;
        }
      } catch {
        errors.push("SMILES validation service is temporarily unavailable.");
      }
    }

    if (
      !setting.temperature ||
      setting.temperature < 20 ||
      setting.temperature > 45
    ) {
      errors.push("Please provide a temperature between 20 and 45 °C.");
    }

    if (errors.length === 0) {
      setValidatingState(
        "Everything looks good! You can submit the calculation." +
          (totalDuplicates > 0
            ? " Total " +
              totalDuplicates +
              " duplicate SMILES will be skipped."
            : ""),
      );
    }

    setIsValidated(errors.length === 0);
    if (errors.length === 0) {
      setSetting((prev) => ({
        ...prev,
        validated_smiles: validatedSmiles,
      }));
    }
    setValidatorErrors(errors);
    setIsValidating(false);
  }

  async function handleRequestCode(e: React.FormEvent) {
    e.preventDefault();
    setEmailError(null);
    if (!emailTurnstileToken) { setEmailError("Please complete the captcha."); return; }
    setIsEmailSubmitting(true);
    try {
      const response = await fetch("/api/predictions/email-verification", {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({ email, turnstile_token: emailTurnstileToken }),
      });
      const json = await response.json();
      if (!response.ok) {
        setEmailError(json?.errors?.email?.[0] ?? json?.errors?.turnstile_token?.[0] ?? json?.message ?? "Error.");
        captchaKey.current += 1;
        setEmailTurnstileToken(null);
        return;
      }
      setEmailStep("code");
    } catch { setEmailError("Request failed."); }
    finally { setIsEmailSubmitting(false); }
  }

  async function handleVerifyCode(e: React.FormEvent) {
    e.preventDefault();
    setEmailError(null);
    setIsEmailSubmitting(true);
    try {
      const response = await fetch("/api/predictions/email-verification/verify", {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({ email, code: emailCode }),
      });
      const json = await response.json();
      if (!response.ok) {
        setEmailError(json?.errors?.code?.[0] ?? json?.message ?? "Invalid code.");
        return;
      }
      const verifiedEmail = json.email ?? email;
      setEmail(verifiedEmail);
      setEmailStep("verified");
      router.refresh();
    } catch { setEmailError("Request failed."); }
    finally { setIsEmailSubmitting(false); }
  }

  async function submit() {
    if (!isValidated) {
      return;
    }

    setIsSaving(true);
    setValidatorErrors([]);

    try {
      const response = await fetch("/api/predictions/datasets", {
        method: "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          membranes: setting.membranes,
          methods: setting.methods,
          description: setting.description,
          smiles: setting.validated_smiles,
          temperature: setting.temperature,
        }),
      });
      const json = await response.json();

      if (!response.ok) {
        const errors = json?.errors
          ? Object.values(json.errors).flat().map(String)
          : [json?.message || "Unable to submit calculations."];

        setValidatorErrors(errors);
        onOpen();

        return;
      }

      addToast({
        title: "Calculations queued",
        description:
          json?.message ||
          "Your calculations were created and will be submitted by the worker.",
        color: "success",
        shouldShowTimeoutProgress: true,
        timeout: 4500,
      });

      const firstDatasetId = json?.data?.dataset_ids?.[0];
      const firstDatasetToken = json?.data?.datasets?.[0]?.token;
      router.push(
        firstDatasetId
          ? firstDatasetToken
            ? `/lab/running-predictions/${firstDatasetId}?token=${encodeURIComponent(firstDatasetToken)}`
            : `/lab/running-predictions/${firstDatasetId}`
          : "/lab/running-predictions",
      );
    } catch (error) {
      setValidatorErrors([
        error instanceof Error
          ? error.message
          : "Unexpected error occurred while submitting calculations.",
      ]);
      onOpen();
    } finally {
      setIsSaving(false);
    }
  }

  // Email verification creates an authenticated session before the form is shown.
  if (!isLoggedIn && emailStep !== "verified") {
    return (
      <div className="w-full">
        <div className="flex flex-col min-w-lg bg-foreground-200 rounded-xl p-4 gap-4">
          <h2 className="font-bold text-lg">Verify your email to continue</h2>
          <p className="text-sm text-foreground/60">
            Verification signs you in. If the account does not exist yet, it
            will be created automatically.
          </p>
          {emailStep === "code" ? (
            <form onSubmit={handleVerifyCode} className="flex flex-col gap-3">
              <p className="text-sm text-default-500">
                Enter the 6-digit code we sent to <strong>{email}</strong>.
              </p>
              <Input label="Verification code" value={emailCode} onChange={(e) => setEmailCode(e.target.value)} maxLength={6} />
              {emailError && <Alert color="danger" title={emailError} />}
              <div className="flex gap-2">
                <Button type="button" variant="flat" onPress={() => { setEmailStep("email"); setEmailCode(""); setEmailError(null); }}>Back</Button>
                <Button type="submit" color="primary" isLoading={isEmailSubmitting} isDisabled={emailCode.length < 6}>Verify</Button>
              </div>
            </form>
          ) : (
            <form onSubmit={handleRequestCode} className="flex flex-col gap-3">
              <Input label="Email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} />
              <Turnstile key={captchaKey.current} name="turnstile_token" siteKey={turnstileSiteKey} onVerify={setEmailTurnstileToken} />
              {emailError && <Alert color="danger" title={emailError} />}
              <Button type="submit" color="primary" isLoading={isEmailSubmitting} isDisabled={!email || !emailTurnstileToken}>Send code</Button>
            </form>
          )}
        </div>
      </div>
    );
  }

  return (
    <>
      <div className="w-full">
        <div className="flex flex-col min-w-lg bg-foreground-200 rounded-xl p-4 gap-2">
          <h2 className="font-bold text-lg">Add new calculation</h2>
          {!isLoggedIn && emailStep === "verified" && (
            <Alert color="success" title={`Submitting as ${email}`} className="mb-1" />
          )}
          {optionError && <Alert color="danger" title={optionError} />}
          <Divider />
          <div>
            <h3 className="text-md text-secondary font-semibold">
              Select membrane
            </h3>
            <label className="text-sm block text-warning-600 text-right">
              Choose one of the available membranes for the simulation
            </label>
            <div className="border-1 border-foreground-300 p-4 bg-background rounded-xl">
              <GridSelection
                onSelectionChange={(selected) =>
                  setSetting((prev) => ({
                    ...prev,
                    membranes: selected?.length ? selected : null,
                  }))
                }
                items={options.membranes}
                isLoading={isLoadingOptions}
                emptyMessage="No prediction membranes are available."
              />
            </div>
          </div>
          <Divider />
          <div>
            <h3 className="text-md text-secondary font-semibold">
              Select method
            </h3>
            <div className="flex flex-col items-end gap-1 text-right text-sm text-warning-600">
              <span>Choose one of the available methods for the simulation</span>
              <Link
                href="/docs/contributing-data/prediction-workflow"
                className="text-primary underline"
                target="_blank"
              >
                Read more about the prediction workflow
              </Link>
            </div>
            <div className="border-1 border-foreground-300 p-4 bg-background rounded-xl">
              <GridSelection
                onSelectionChange={(selected) =>
                  setSetting((prev) => ({
                    ...prev,
                    methods: selected?.length ? selected : null,
                  }))
                }
                items={options.methods}
                isLoading={isLoadingOptions}
                emptyMessage="No remote prediction methods are available."
              />
            </div>
          </div>
          <Divider />
          <div>
            <h3 className="text-md text-secondary font-semibold">
              Temperature [°C]
            </h3>
            <label className="text-sm block text-warning-600 text-right"></label>
            <div className="border-1 border-foreground-300 p-4 bg-white rounded-xl">
              <Input
                type="number"
                className=""
                value={setting.temperature.toString()}
                onChange={(value) => {
                  setSetting((prev) => ({
                    ...prev,
                    temperature: Number(value.target.value),
                  }));
                }}
                min={20}
                max={45}
              />
            </div>
          </div>
          <Divider />
          <div>
            <h3 className="text-md text-secondary font-semibold">
              Molecules (in SMILES format)
            </h3>
            <label className="text-sm block text-warning-600 text-right">
              Put one SMILES per line
            </label>
            <div className="flex flex-col gap-3 border-1 border-foreground-300 p-4 bg-white rounded-xl dark:bg-background">
              <Alert
                color="primary"
                title="Prediction structure limits"
                description={
                  <span>
                    Each SMILES must describe one connected molecule containing
                    at most {options.structureValidation.maxAtoms} atoms
                    including all hydrogens. Allowed elements:{" "}
                    {options.structureValidation.allowedElements.join(", ")}.
                    {options.structureValidation.singleConnectedMolecule &&
                      " Salts and disconnected structures are not supported."}
                  </span>
                }
              />
              <Textarea
                type="text"
                className=""
                value={setting.smiles || ""}
                onChange={(e) =>
                  setSetting((prev) => ({ ...prev, smiles: e.target.value }))
                }
                required
                isClearable
                isMultiline
                minRows={5}
                maxRows={15}
                maxLength={100000}
              />
            </div>
          </div>
          <Divider />
          <div>
            <h3 className="text-md text-secondary font-semibold">
              Description (comment)
            </h3>
            <label className="text-sm block text-warning-600 text-right">
              Describe your calculation (only for your reference)
            </label>
            <div className="border-1 border-foreground-300 p-4 bg-white rounded-xl">
              <Textarea
                type="text"
                className=""
                value={setting.description || ""}
                onChange={(e) => {
                  setSetting((prev) => ({
                    ...prev,
                    description: e.target.value,
                  }));
                }}
                required
                isMultiline
                maxLength={512}
              />
            </div>
          </div>
          <div className="flex flex-row justify-end gap-4">
            <Button
              type="button"
              color={isValidated ? "success" : "warning"}
              startContent={!isValidating && <FaCheck />}
              size="lg"
              onPress={validate}
              isLoading={isValidating}
              isDisabled={isValidated}
            >
              Validate
            </Button>
            <Button
              type="submit"
              color="primary"
              startContent={!isSaving && <IoSend />}
              size="lg"
              isLoading={isSaving}
              isDisabled={!isValidated}
              onPress={submit}
            >
              Submit
            </Button>
          </div>
        </div>
      </div>
      <Modal
        isOpen={isOpen}
        onOpenChange={onOpenChange}
        size="xl"
        hideCloseButton
      >
        <ModalContent>
          {(onClose) =>
            isValidating || validatorErrors.length === 0 ? (
              <>
                <div className="p-8 flex flex-col gap-4">
                  <h2 className="text-2xl font-bold">
                    {!isValidated ? "Validating" : "Validation finished"}
                  </h2>
                  <div>{validatingState}</div>
                  <div className="flex flex-row justify-end">
                    <Button
                      type="button"
                      color="primary"
                      onPress={() => {
                        onClose();
                      }}
                    >
                      Close
                    </Button>
                  </div>
                </div>
              </>
            ) : (
              <div className="p-8 flex flex-col gap-4">
                <h2 className="text-2xl font-bold">Validation errors</h2>
                <div className="flex flex-col gap-2">
                  {validatorErrors.map((error, index) => (
                    <Alert key={index} color="warning" title={error} />
                  ))}
                </div>
                <div className="flex flex-row justify-end">
                  <Button
                    type="button"
                    color="primary"
                    onPress={() => {
                      onClose();
                    }}
                  >
                    Close
                  </Button>
                </div>
              </div>
            )
          }
        </ModalContent>
      </Modal>
    </>
  );
}

function Divider() {
  return (
    <div className="h-1 w-full bg-gradient-to-r from-foreground-100/50 via-foreground-100 to-foreground-100/50" />
  );
}

interface GridSelectionItemProps {
  id: number | string;
  short_name: string;
  long_name?: string;
  description?: string;
  show_more_link?: string;
  onPress?: (id: number | string) => void;
}

function GridSelection(props: {
  items: GridSelectionItemProps[];
  multiple?: boolean;
  isLoading?: boolean;
  emptyMessage?: string;
  onSelectionChange?: (selectedItems: (number | string)[]) => void;
}) {
  const [selectedItems, setSelectedItems] = useState<(number | string)[]>([]);

  useEffect(() => {
    props.onSelectionChange?.(selectedItems);
  }, [selectedItems]);

  const Item = (props: GridSelectionItemProps) => (
    <Card
      isPressable
      className={`w-64 border-2 cursor-pointer select-none 
          ${selectedItems.includes(props.id) ? "border-primary" : "border-transparent hover:border-primary/50"}`}
      onPress={() => props.onPress?.(props.id)}
    >
      <CardHeader
        className={`flex flex-col gap-0 ${props.long_name || props.description ? "items-start" : "items-center"}`}
      >
        <h1>{props.short_name}</h1>
        {props.long_name && (
          <h2 className="text-sm text-foreground/50">{props.long_name}</h2>
        )}
      </CardHeader>
      {props.description && (
        <CardBody>
          <p className="text-clip line-clamp-3 text-sm">{props.description}</p>
        </CardBody>
      )}
      {props.show_more_link && (
        <CardFooter className="flex justify-end">
          <Link
            className="text-sm text-primary underline"
            href={props.show_more_link}
            target="_blank"
          >
            ...show more
          </Link>
        </CardFooter>
      )}
    </Card>
  );

  return (
    <div className="flex flex-row flex-wrap gap-4">
      {props.isLoading && (
        <div className="w-full text-sm text-foreground/60">Loading...</div>
      )}
      {!props.isLoading && props.items.length === 0 && (
        <div className="w-full text-sm text-warning">
          {props.emptyMessage ?? "No options available."}
        </div>
      )}
      {props.items.map((item, index) => (
        <Item
          key={index}
          {...item}
          onPress={(id) => {
            if (selectedItems.includes(id)) {
              setSelectedItems(selectedItems.filter((item) => item !== id));
            } else if (!props.multiple) {
              setSelectedItems([id]);
            } else {
              setSelectedItems([...selectedItems, id]);
            }
          }}
        />
      ))}
    </div>
  );
}
