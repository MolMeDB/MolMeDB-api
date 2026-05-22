"use client";

import { Alert, Button, Input } from "@heroui/react";
import { useActionState, useState } from "react";
import submitResetPassword from "../(actions)/submitResetPassword";

export default function ResetPasswordForm(props: {
  hash: string;
  email: string;
}) {
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  const [actionState, action, isPending] = useActionState(
    submitResetPassword,
    null,
  );

  const [email, setEmail] = useState(props.email);
  const [password, setPassword] = useState("");
  const [passwordConfirm, setPasswordConfirm] = useState("");

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
        <input name="hash" value={props.hash} hidden readOnly />
        <input name="email" value={props.email} hidden readOnly />
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
          isDisabled
          value={email}
          required
          onChange={(e) => setEmail(e.target.value)}
          autoFocus
        />
        <Input
          size="sm"
          classNames={{
            base: "mb-2",
            input: "text-[16px] lg:text-md",
          }}
          type="password"
          label="New password"
          name="password"
          autoComplete="new-password"
          isDisabled={actionState?.status === 200}
          value={password}
          onChange={(e) => setPassword(e.target.value)}
        />
        <Input
          size="sm"
          classNames={{
            base: "mb-2",
            input: "text-[16px] lg:text-md",
          }}
          type="password"
          label="Confirm new password"
          name="password_confirmation"
          autoComplete="new-password"
          isDisabled={actionState?.status === 200}
          value={passwordConfirm}
          onChange={(e) => setPasswordConfirm(e.target.value)}
        />

        <Button
          className="w-full mt-4"
          type="submit"
          color="primary"
          size="lg"
          isDisabled={actionState?.status === 200}
          isLoading={isPending}
        >
          Reset password
        </Button>
      </form>
    </div>
  );
}
