"use client";

import {
  Alert,
  Button,
  Card,
  CardBody,
  CardHeader,
  Divider,
  Input,
} from "@heroui/react";
import { useActionState, useEffect, useState } from "react";
import submitChangePassword from "../(actions)/submitChangePassword";

export default function AccountSettingsForm(props: { email: string }) {
  const [actionState, action, isPending] = useActionState(
    submitChangePassword,
    null,
  );

  const [currentPassword, setCurrentPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [newPasswordConfirmation, setNewPasswordConfirmation] = useState("");

  useEffect(() => {
    if (actionState?.status === 200) {
      setCurrentPassword("");
      setNewPassword("");
      setNewPasswordConfirmation("");
    }
  }, [actionState?.status]);

  return (
    <div className="grid grid-cols-1 gap-6 lg:grid-cols-12">
      <aside className="lg:col-span-4 xl:col-span-3">
        <Card shadow="sm" className="border border-default-200/70">
          <CardHeader className="pb-2">
            <h2 className="text-lg font-semibold">Settings</h2>
          </CardHeader>
          <Divider />
          <CardBody className="gap-2 text-sm">
            <div className="rounded-md bg-primary-50 px-3 py-2 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
              Change password
            </div>
            <div className="rounded-md bg-default-100 px-3 py-2 text-default-500">
              Notifications (coming soon)
            </div>
            <div className="rounded-md bg-default-100 px-3 py-2 text-default-500">
              Privacy (coming soon)
            </div>
          </CardBody>
        </Card>
      </aside>

      <section className="lg:col-span-8 xl:col-span-9">
        <Card shadow="sm" className="border border-default-200/70">
          <CardHeader className="flex flex-col items-start gap-1">
            <h2 className="text-xl font-semibold">Password</h2>
            <p className="text-sm text-default-500">
              Manage your sign-in password for {props.email}.
            </p>
          </CardHeader>
          <Divider />
          <CardBody>
            <form
              className="grid grid-cols-1 gap-4 max-w-xl"
              action={action}
              autoComplete="on"
            >
              {actionState?.message ? (
                <Alert
                  color={actionState.status === 200 ? "success" : "warning"}
                  title={actionState.message}
                />
              ) : null}

              <Input
                type="password"
                label="Current password"
                name="current_password"
                value={currentPassword}
                onChange={(event) => setCurrentPassword(event.target.value)}
                isRequired
                isDisabled={isPending}
                autoComplete="current-password"
                errorMessage={actionState?.data?.current_password?.[0]}
                isInvalid={Boolean(actionState?.data?.current_password?.[0])}
              />

              <Input
                type="password"
                label="New password"
                name="password"
                value={newPassword}
                onChange={(event) => setNewPassword(event.target.value)}
                isRequired
                isDisabled={isPending}
                autoComplete="new-password"
                errorMessage={actionState?.data?.password?.[0]}
                isInvalid={Boolean(actionState?.data?.password?.[0])}
              />

              <Input
                type="password"
                label="Confirm new password"
                name="password_confirmation"
                value={newPasswordConfirmation}
                onChange={(event) =>
                  setNewPasswordConfirmation(event.target.value)
                }
                isRequired
                isDisabled={isPending}
                autoComplete="new-password"
                errorMessage={actionState?.data?.password_confirmation?.[0]}
                isInvalid={Boolean(
                  actionState?.data?.password_confirmation?.[0],
                )}
              />

              <div className="pt-2">
                <Button type="submit" color="primary" isLoading={isPending}>
                  Save password
                </Button>
              </div>
            </form>
          </CardBody>
        </Card>
      </section>
    </div>
  );
}
