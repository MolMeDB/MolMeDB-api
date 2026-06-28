"use client";

import UiTable from "@/components/ui/table";
import { useMemo, useState, useRef, useEffect } from "react";
import { makeDatasetColumns } from "./columns";
import { IPredictionDataset } from "@/lib/api/admin/interfaces/Predictions";
import { Alert, Button, Card, CardBody, CardHeader, Input } from "@heroui/react";
import Link from "next/link";
import { MdAdd } from "react-icons/md";
import PredictionStatsWidget from "@/components/ui/predictionStatsWidget";
import Turnstile from "@/components/auth/Turnstile";
import { useRouter } from "next/navigation";

const turnstileSiteKey = process.env.NEXT_PUBLIC_TURNSTILE_SITE_KEY ?? "";

type EmailStep = "email" | "code" | "verified";

export default function MyJobsTable({
  isLoggedIn,
  initialToken = "",
}: {
  isLoggedIn: boolean;
  initialToken?: string;
}) {
  const router = useRouter();

  const [emailStep, setEmailStep] = useState<EmailStep>(
    isLoggedIn ? "verified" : "email",
  );
  const [email, setEmail] = useState("");
  const [authenticatedViaEmail, setAuthenticatedViaEmail] = useState(false);
  const hasAuthenticatedSession = isLoggedIn || authenticatedViaEmail;

  // Token lookup
  const [tokenInput, setTokenInput] = useState(initialToken);
  const [tokenError, setTokenError] = useState<string | null>(null);
  const [isTokenLoading, setIsTokenLoading] = useState(false);

  const [emailCode, setEmailCode] = useState("");
  const [emailTurnstileToken, setEmailTurnstileToken] = useState<string | null>(null);
  const [emailError, setEmailError] = useState<string | null>(null);
  const [isEmailSubmitting, setIsEmailSubmitting] = useState(false);
  const captchaKey = useRef(0);

  useEffect(() => {
    if (initialToken) {
      doTokenLookup(initialToken);
    }
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  async function doTokenLookup(token: string) {
    setTokenError(null);
    setIsTokenLoading(true);
    try {
      const response = await fetch("/api/predictions/by-token", {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({ token: token.trim() }),
      });
      const json = await response.json();
      if (!response.ok) {
        setTokenError(json?.errors?.token?.[0] ?? json?.message ?? "Invalid token.");
        return;
      }
      router.push(
        `/lab/running-predictions/${json.data.dataset_id}?token=${encodeURIComponent(token.trim())}`,
      );
    } catch {
      setTokenError("Request failed.");
    } finally {
      setIsTokenLoading(false);
    }
  }

  async function handleTokenLookup(e: React.FormEvent) {
    e.preventDefault();
    doTokenLookup(tokenInput);
  }

  async function handleRequestCode(e: React.FormEvent) {
    e.preventDefault();
    setEmailError(null);
    if (!emailTurnstileToken) {
      setEmailError("Please complete the captcha.");
      return;
    }
    setIsEmailSubmitting(true);
    try {
      const response = await fetch("/api/predictions/email-verification", {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({ email, turnstile_token: emailTurnstileToken }),
      });
      const json = await response.json();
      if (!response.ok) {
        setEmailError(
          json?.errors?.email?.[0] ?? json?.errors?.turnstile_token?.[0] ?? json?.message ?? "Error.",
        );
        captchaKey.current += 1;
        setEmailTurnstileToken(null);
        return;
      }
      setEmailStep("code");
    } catch {
      setEmailError("Request failed.");
    } finally {
      setIsEmailSubmitting(false);
    }
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
      setEmail(json.email ?? email);
      setAuthenticatedViaEmail(true);
      setEmailStep("verified");
      router.refresh();
    } catch {
      setEmailError("Request failed.");
    } finally {
      setIsEmailSubmitting(false);
    }
  }

  const tableApiParams = useMemo(() => ({}), []);

  const columns = useMemo(() => makeDatasetColumns(false), []);

  const showTable = hasAuthenticatedSession;

  return (
    <div className="flex flex-col gap-8">
      {/* Token lookup — always visible */}
      <Card className="border border-default-200 bg-default-50">
        <CardHeader className="pb-2">
          <h2 className="text-base font-semibold">Track a job by token</h2>
        </CardHeader>
        <CardBody>
          <form onSubmit={handleTokenLookup} className="flex flex-col sm:flex-row gap-3">
            <Input
              value={tokenInput}
              onChange={(e) => setTokenInput(e.target.value)}
              placeholder="Paste your dataset token..."
              className="flex-1"
              size="sm"
            />
            <Button
              type="submit"
              color="primary"
              size="sm"
              isLoading={isTokenLoading}
              isDisabled={!tokenInput.trim()}
            >
              Go
            </Button>
          </form>
          {tokenError && (
            <Alert color="danger" className="mt-3" title={tokenError} />
          )}
        </CardBody>
      </Card>

      {!hasAuthenticatedSession && (
        <Card className="border border-default-200 bg-default-50">
          <CardHeader className="pb-2">
            <h2 className="text-base font-semibold">View my calculations</h2>
          </CardHeader>
          <CardBody className="flex flex-col gap-4">
            {emailStep === "code" ? (
              <form onSubmit={handleVerifyCode} className="flex flex-col gap-3">
                <p className="text-sm text-default-500">
                  Enter the 6-digit code we sent to <strong>{email}</strong>.
                </p>
                <Input
                  label="Verification code"
                  value={emailCode}
                  onChange={(e) => setEmailCode(e.target.value)}
                  maxLength={6}
                  size="sm"
                />
                {emailError && <Alert color="danger" title={emailError} />}
                <div className="flex gap-2">
                  <Button
                    type="button"
                    size="sm"
                    variant="flat"
                    onPress={() => { setEmailStep("email"); setEmailCode(""); setEmailError(null); }}
                  >
                    Back
                  </Button>
                  <Button
                    type="submit"
                    color="primary"
                    size="sm"
                    isLoading={isEmailSubmitting}
                    isDisabled={emailCode.length < 6}
                  >
                    Verify
                  </Button>
                </div>
              </form>
            ) : (
              <form onSubmit={handleRequestCode} className="flex flex-col gap-3">
                <p className="text-sm text-default-500">
                  Enter your email to sign in and see your calculations.
                </p>
                <Input
                  label="Email"
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  size="sm"
                />
                <Turnstile
                  key={captchaKey.current}
                  name="turnstile_token"
                  siteKey={turnstileSiteKey}
                  onVerify={setEmailTurnstileToken}
                />
                {emailError && <Alert color="danger" title={emailError} />}
                <Button
                  type="submit"
                  color="primary"
                  size="sm"
                  isLoading={isEmailSubmitting}
                  isDisabled={!email || !emailTurnstileToken}
                >
                  Send code
                </Button>
              </form>
            )}
          </CardBody>
        </Card>
      )}

      {/* Datasets table */}
      {showTable && (
        <div className="flex flex-col gap-4">
          <PredictionStatsWidget />
          <div className="flex justify-end">
            <Button
              as={Link}
              href="/lab/new-predictions"
              color="primary"
              startContent={<MdAdd size={20} />}
            >
              New dataset
            </Button>
          </div>
          <UiTable<IPredictionDataset>
            apiUrl={`/api/predictions/datasets`}
            apiParams={tableApiParams}
            aria-label="Prediction datasets table"
            columns={columns}
            itemKey="id"
            defaultRowsPerPage={20}
            searchPlaceholder="Search by comment..."
            hasSearch
          />
        </div>
      )}
    </div>
  );
}
