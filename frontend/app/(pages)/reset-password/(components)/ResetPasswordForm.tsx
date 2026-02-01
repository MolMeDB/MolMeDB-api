"use client";

import { Alert, Button, Input } from "@heroui/react";
import { useActionState, useState } from "react";
import submitResetPassword from "../(actions)/submitResetPassword";

export default function ResetPasswordForm() {
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  const [actionState, action, isPending] = useActionState(
    submitResetPassword,
    null,
  );

  const [email, setEmail] = useState("");

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
          isDisabled={actionState?.status === 201}
          value={email}
          required
          onChange={(e) => setEmail(e.target.value)}
          autoFocus
        />
        <Button
          className="w-full mt-4"
          type="submit"
          color="primary"
          size="lg"
          isDisabled={actionState?.status === 201}
          isLoading={isPending}
        >
          Send email
        </Button>
      </form>
    </div>
  );
}
