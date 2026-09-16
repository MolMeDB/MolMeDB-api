"use client";

import { Alert, Button, Input } from "@heroui/react";
import { useActionState, useState } from "react";
import submitSignUp from "../(actions)/submitSignUp";
import Turnstile from "@/components/auth/Turnstile";

const turnstileSiteKey = process.env.NEXT_PUBLIC_TURNSTILE_SITE_KEY ?? "";

export default function SignUpForm() {
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  const [actionState, action, isPending] = useActionState(submitSignUp, null);

  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [affiliation, setAffiliation] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirm, setPasswordConfirm] = useState("");
  const [turnstileToken, setTurnstileToken] = useState<string | null>(null);

  return (
    <div className="w-full max-w-96 flex flex-col items-center">
      <form className="w-full" action={action} autoComplete="on">
        {actionState?.message && (
          <div>
            <Alert
              color={actionState.status === 201 ? "success" : "danger"}
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
          type="text"
          label="Name"
          required
          isDisabled={actionState?.status === 201}
          name="name"
          value={name}
          onChange={(e) => setName(e.target.value)}
          autoFocus
          autoComplete="name"
        />
        <Input
          size="sm"
          classNames={{
            base: "mb-2",
            input: "text-[16px] lg:text-md",
          }}
          type="text"
          label="Affiliation"
          required
          isDisabled={actionState?.status === 201}
          autoComplete="affiliation"
          name="affiliation"
          maxLength={255}
          minLength={3}
          value={affiliation}
          onChange={(e) => setAffiliation(e.target.value)}
          autoFocus
        />
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
          isDisabled={actionState?.status === 201}
          value={email}
          required
          onChange={(e) => setEmail(e.target.value)}
          autoFocus
        />
        <Input
          className="mb-4"
          type="password"
          size="sm"
          minLength={8}
          autoComplete="new-password"
          value={password}
          isDisabled={actionState?.status === 201}
          required
          onChange={(e) => setPassword(e.target.value)}
          classNames={{
            input: "text-[16px] lg:text-md",
          }}
          label="Password"
          name="password"
        />
        <Input
          className="mb-4"
          type="password"
          size="sm"
          value={passwordConfirm}
          required
          isDisabled={actionState?.status === 201}
          onChange={(e) => setPasswordConfirm(e.target.value)}
          classNames={{
            input: "text-[16px] lg:text-md",
          }}
          label="Confirm Password"
          name="password_confirmation"
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
            actionState?.status === 201 || !turnstileSiteKey || !turnstileToken
          }
          isLoading={isPending}
        >
          Create new account
        </Button>
        <p className="text-sm text-gray-600 mt-4 text-center">
          Already have an account?{" "}
          <a
            href="/login"
            className="text-primary hover:underline dark:text-primary"
          >
            Log in now
          </a>
        </p>
      </form>
    </div>
  );
}
