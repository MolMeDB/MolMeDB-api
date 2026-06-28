"use client";

import Turnstile from "@/components/auth/Turnstile";
import { useDockAlign } from "@/components/_core/layout/FloatingDock";
import { UserSession } from "@/lib/api/admin/interfaces/User";
import {
  addToast,
  Button,
  Input,
  Textarea,
  Tooltip,
} from "@heroui/react";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import { type SyntheticEvent, useEffect, useMemo, useState } from "react";
import {
  FiCheckCircle,
  FiMessageCircle,
  FiSend,
  FiX,
} from "react-icons/fi";

type FeedbackStep = "email" | "code" | "message" | "success";

type VerificationPayload = {
  email: string;
};

type ApiErrorPayload = {
  message?: string;
  errors?: Record<string, string[]>;
};

const MESSAGE_LIMIT = 4000;
const turnstileSiteKey = process.env.NEXT_PUBLIC_TURNSTILE_SITE_KEY ?? "";

class ApiRequestError extends Error {
  constructor(
    message: string,
    public status: number,
  ) {
    super(message);
  }
}

export default function FeedbackWidget({ user }: { user?: UserSession }) {
  const align = useDockAlign();
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const [authenticatedViaEmail, setAuthenticatedViaEmail] = useState(false);
  const isAuthenticated = Boolean(user?.email) || authenticatedViaEmail;
  const [requiresEmailVerification, setRequiresEmailVerification] =
    useState(false);
  const shouldVerifyEmail = !isAuthenticated || requiresEmailVerification;

  const [isOpen, setIsOpen] = useState(false);
  const [contextUri, setContextUri] = useState(pathname ?? "/");
  const [step, setStep] = useState<FeedbackStep>(() => {
    if (!shouldVerifyEmail) return "message";
    return "email";
  });
  const [email, setEmail] = useState(() => user?.email ?? "");
  const [code, setCode] = useState("");
  const [message, setMessage] = useState("");
  const [turnstileToken, setTurnstileToken] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  useEffect(() => {
    const query = searchParams.toString();
    const pathWithQuery = query ? `${pathname}?${query}` : pathname;

    setContextUri(window.location.origin + pathWithQuery);
  }, [pathname, searchParams]);

  useEffect(() => {
    if (isAuthenticated && !requiresEmailVerification) {
      setEmail(user?.email ?? "");
      setStep("message");
    }
  }, [isAuthenticated, requiresEmailVerification, user?.email]);

  const messageCharactersLeft = useMemo(() => {
    return MESSAGE_LIMIT - message.length;
  }, [message]);

  async function requestJson<T>(
    uri: string,
    payload: Record<string, unknown>,
  ): Promise<T> {
    const response = await fetch(uri, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(payload),
    });
    const json = (await response.json()) as ApiErrorPayload & T;

    if (!response.ok) {
      const validationError = json.errors
        ? Object.values(json.errors).flat()[0]
        : null;

      throw new ApiRequestError(
        validationError ?? json.message ?? "Request failed.",
        response.status,
      );
    }

    return json;
  }

  async function handleEmailSubmit(event: SyntheticEvent<HTMLFormElement>) {
    event.preventDefault();
    setErrorMessage(null);
    setIsSubmitting(true);

    try {
      await requestJson("/api/feedback/email-verification", {
        email,
        turnstile_token: turnstileToken,
      });
      setStep("code");
      addToast({
        title: "Verification code sent",
        color: "success",
        shouldShowTimeoutProgress: true,
        timeout: 5000,
      });
    } catch (error) {
      setErrorMessage(
        error instanceof Error ? error.message : "Verification code failed.",
      );
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleCodeSubmit(event: SyntheticEvent<HTMLFormElement>) {
    event.preventDefault();
    setErrorMessage(null);
    setIsSubmitting(true);

    try {
      const data = await requestJson<VerificationPayload>(
        "/api/feedback/email-verification/verify",
        { email, code },
      );
      setEmail(data.email ?? email);
      setAuthenticatedViaEmail(true);
      setRequiresEmailVerification(false);
      setStep("message");
      router.refresh();
    } catch (error) {
      setErrorMessage(
        error instanceof Error ? error.message : "Email verification failed.",
      );
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleFeedbackSubmit(event: SyntheticEvent<HTMLFormElement>) {
    event.preventDefault();
    setErrorMessage(null);
    setIsSubmitting(true);

    try {
      await requestJson("/api/feedback", {
        context: contextUri,
        message,
      });
      setMessage("");
      setStep("success");
    } catch (error) {
      if (error instanceof ApiRequestError && error.status === 401) {
        setRequiresEmailVerification(true);
        setStep("email");
        setErrorMessage(
          "Your session could not be verified. Please confirm your email address to send feedback.",
        );

        return;
      }

      setErrorMessage(
        error instanceof Error ? error.message : "Feedback could not be sent.",
      );
    } finally {
      setIsSubmitting(false);
    }
  }

  function resetForm() {
    if (isAuthenticated && !requiresEmailVerification) {
      setStep("message");
      setEmail(user?.email ?? email);
    } else {
      setStep("email");
    }
    setCode("");
    setTurnstileToken(null);
    setErrorMessage(null);
    setMessage("");
  }

  function closeFeedback() {
    resetForm();
    setIsOpen(false);
  }

  return (
    <div className="relative pointer-events-auto">
      <div
        aria-hidden={!isOpen}
        className={[
          "absolute bottom-[calc(100%+12px)]",
          align === "left" ? "left-0 origin-bottom-left" : "right-0 origin-bottom-right",
          "max-h-[calc(100dvh-6rem)] w-[360px] max-w-[calc(100vw-2rem)] overflow-y-auto rounded-lg border border-default-200 bg-white shadow-2xl transition-all duration-200 ease-out motion-reduce:transition-none dark:border-default-100 dark:bg-zinc-950",
          isOpen
            ? "pointer-events-auto translate-y-0 scale-100 opacity-100"
            : "pointer-events-none translate-y-3 scale-95 opacity-0",
        ].join(" ")}
        inert={!isOpen ? true : undefined}
      >
        <div className="flex items-center justify-between gap-3 border-b border-default-200 px-4 py-3 dark:border-default-100">
          <span className="text-sm font-semibold text-foreground">
            Feedback
          </span>
          <Button
            isIconOnly
            aria-label="Close feedback form"
            size="sm"
            variant="light"
            onPress={closeFeedback}
          >
            <FiX />
          </Button>
        </div>
        <div className="flex flex-col gap-4">
          {errorMessage && (
            <div
              className="rounded-lg border border-danger-200 bg-danger-50 px-4 py-3 text-sm text-danger-700"
              style={{ margin: "16px 16px 0" }}
            >
              {errorMessage}
            </div>
          )}

          {shouldVerifyEmail && step === "email" && (
            <form
              className="flex flex-col gap-4 p-4"
              onSubmit={handleEmailSubmit}
            >
              <p className="text-sm text-foreground-600">
                {requiresEmailVerification
                  ? "We need to confirm your email before sending this feedback."
                  : "Enter your email address. We will send you a verification code before you can submit feedback."}
              </p>
              <Input
                isRequired
                isDisabled={isAuthenticated}
                label="Email"
                type="email"
                value={email}
                variant="bordered"
                onValueChange={setEmail}
              />
              <Turnstile
                name="turnstile_token"
                siteKey={turnstileSiteKey}
                onVerify={setTurnstileToken}
              />
              <div className="flex justify-end">
                <Button
                  color="primary"
                  isDisabled={!turnstileSiteKey || !turnstileToken}
                  isLoading={isSubmitting}
                  startContent={!isSubmitting ? <FiSend /> : null}
                  type="submit"
                >
                  Send code
                </Button>
              </div>
            </form>
          )}

          {shouldVerifyEmail && step === "code" && (
            <form
              className="flex flex-col gap-4 p-4"
              onSubmit={handleCodeSubmit}
            >
              <Input
                isDisabled
                label="Email"
                value={email}
                variant="bordered"
              />
              <Input
                isRequired
                label="Verification code"
                maxLength={6}
                value={code}
                variant="bordered"
                onValueChange={setCode}
              />
              <div className="flex justify-end gap-2">
                <Button variant="flat" onPress={() => setStep("email")}>
                  Change email
                </Button>
                <Button color="primary" isLoading={isSubmitting} type="submit">
                  Verify
                </Button>
              </div>
            </form>
          )}

          {step === "message" && (
            <form
              className="flex flex-col gap-3 p-4"
              onSubmit={handleFeedbackSubmit}
            >
              <Input
                isDisabled
                label="Email"
                size="sm"
                value={email}
                variant="bordered"
              />
              <Input
                isDisabled
                label="Context"
                size="sm"
                value={contextUri}
                variant="bordered"
              />
              <Textarea
                isRequired
                label="Message"
                maxLength={MESSAGE_LIMIT}
                minRows={5}
                value={message}
                variant="bordered"
                onValueChange={setMessage}
              />
              <div className="-mt-1 text-right text-xs text-foreground-500">
                {messageCharactersLeft} characters left
              </div>
              <div className="flex justify-end">
                <Button
                  color="primary"
                  isLoading={isSubmitting}
                  startContent={!isSubmitting ? <FiSend /> : null}
                  type="submit"
                >
                  Send feedback
                </Button>
              </div>
            </form>
          )}

          {step === "success" && (
            <div className="flex flex-col items-center gap-4 px-4 py-8 text-center">
              <FiCheckCircle className="h-12 w-12 text-success" />
              <div>
                <div className="text-lg font-semibold">Thank you</div>
                <div className="text-sm text-foreground-600">
                  Your feedback has been sent.
                </div>
              </div>
              <Button
                color="primary"
                onPress={() => {
                  resetForm();
                  setIsOpen(false);
                }}
              >
                Close
              </Button>
            </div>
          )}
        </div>
      </div>

      <Tooltip content="Send feedback" placement="top">
        <Button
          isIconOnly
          aria-label="Send feedback"
          className="pointer-events-auto h-12 w-12 shadow-lg"
          color="primary"
          radius="full"
          onPress={() => setIsOpen((current) => !current)}
        >
          <FiMessageCircle className="h-5 w-5" />
        </Button>
      </Tooltip>
    </div>
  );
}
