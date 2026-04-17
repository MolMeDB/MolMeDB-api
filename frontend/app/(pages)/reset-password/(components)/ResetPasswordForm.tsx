"use client";

import { Alert, Button, Input } from "@heroui/react";
import { useActionState, useState } from "react";
import submitResetPassword from "../(actions)/submitResetPassword";
import Turnstile from "@/components/auth/Turnstile";

const turnstileSiteKey = process.env.NEXT_PUBLIC_TURNSTILE_SITE_KEY ?? "";

export default function ResetPasswordForm() {
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  const [actionState, action, isPending] = useActionState(
    submitResetPassword,
    null,
  );

  const [email, setEmail] = useState("");
  const [turnstileToken, setTurnstileToken] = useState<string | null>(null);

  return (
    <div className="w-full max-w-96 flex flex-col items-center">
      <form className="w-full" action={action} autoComplete="on">
        {actionState?.message && (
          <div>
            <Alert
              color={actionState.status === 200 ? "success" : "warning"}
              className="mb-4"
              title={actionState.message}
            />
          </div>
        )}

        <Input
          size="sm"
          classNames={{
            base: "mb-2",
            input: "text-[16px] lg:text-md",
          }}
          type="email"
          label="Email"
          name="email"
          autoComplete="email"
          isDisabled={actionState?.status === 200}
          value={email}
          required
          onChange={(e) => setEmail(e.target.value)}
          autoFocus
        />
        <Turnstile
          name="turnstile_token"
          siteKey={turnstileSiteKey}
          onVerify={setTurnstileToken}
        />
        <Button
          className="w-full mt-4"
          type="submit"
          color="primary"
          size="lg"
          isDisabled={
            actionState?.status === 200 || !turnstileSiteKey || !turnstileToken
          }
          isLoading={isPending}
        >
          Send email
        </Button>
      </form>
    </div>
  );
}
