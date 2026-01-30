"use client";

import { Alert, Button, Input } from "@heroui/react";
import submitLogin from "../(actions)/submitLogin";
import { useActionState, useState } from "react";

export default function LoginForm() {
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  const [actionState, action, isPending] = useActionState(submitLogin, null);

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");

  return (
    <div className="w-full max-w-96 flex flex-col items-center">
      <form className="w-full" action={action}>
        {actionState?.message && (
          <div>
            <Alert
              color="danger"
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
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          autoFocus
        />
        <Input
          className="mb-4"
          type="password"
          size="sm"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          classNames={{
            input: "text-[16px] lg:text-md",
          }}
          label="Password"
          name="password"
        />
        <div className="flex justify-between mb-4">
          <div className="flex items-center gap-2">
            <input
              type="checkbox"
              id="remember"
              name="remember"
              className="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary"
            />
            <label htmlFor="remember" className="text-sm text-gray-600">
              Keep logged in
            </label>
          </div>
          <a
            href="#"
            className="text-sm text-primary hover:underline dark:text-primary"
          >
            Forgotten password?
          </a>
        </div>
        <Button
          className="w-full mt-4"
          type="submit"
          color="primary"
          size="lg"
          isLoading={isPending}
        >
          Login
        </Button>
        <p className="text-sm text-gray-600 mt-4 text-center">
          Don&apos;t have an account?{" "}
          <a
            href="/register"
            className="text-primary hover:underline dark:text-primary"
          >
            Sign up for free
          </a>
        </p>
      </form>
    </div>
  );
}
