"use client";

import { Alert } from "@heroui/react";
import Script from "next/script";
import { useCallback, useEffect, useRef, useState } from "react";

type TurnstileWidget = {
  render: (
    element: HTMLElement,
    options: {
      sitekey: string;
      callback: (token: string) => void;
      "expired-callback": () => void;
      "error-callback": () => void;
    },
  ) => string;
  remove: (widgetId: string) => void;
};

declare global {
  interface Window {
    turnstile?: TurnstileWidget;
  }
}

type Props = {
  name: string;
  siteKey: string;
  onVerify: (token: string | null) => void;
};

export default function Turnstile({ name, siteKey, onVerify }: Props) {
  const containerRef = useRef<HTMLDivElement>(null);
  const widgetIdRef = useRef<string | null>(null);
  const [token, setToken] = useState("");
  const [scriptLoaded, setScriptLoaded] = useState(() => {
    return typeof window !== "undefined" && Boolean(window.turnstile);
  });

  const resetToken = useCallback(() => {
    setToken("");
    onVerify(null);
  }, [onVerify]);

  const renderWidget = useCallback(() => {
    if (!siteKey || !scriptLoaded || !containerRef.current || !window.turnstile) {
      return;
    }

    if (widgetIdRef.current) {
      return;
    }

    widgetIdRef.current = window.turnstile.render(containerRef.current, {
      sitekey: siteKey,
      callback: (value) => {
        setToken(value);
        onVerify(value);
      },
      "expired-callback": resetToken,
      "error-callback": resetToken,
    });
  }, [onVerify, resetToken, scriptLoaded, siteKey]);

  useEffect(() => {
    if (!scriptLoaded && window.turnstile) {
      setScriptLoaded(true);
      return;
    }

    renderWidget();

    return () => {
      if (widgetIdRef.current && window.turnstile) {
        window.turnstile.remove(widgetIdRef.current);
        widgetIdRef.current = null;
      }
    };
  }, [renderWidget]);

  if (!siteKey) {
    return (
      <Alert
        color="warning"
        className="mb-2"
        title="Captcha is not configured."
      />
    );
  }

  return (
    <div className="mb-2 flex justify-center">
      <Script
        src="https://challenges.cloudflare.com/turnstile/v0/api.js"
        strategy="afterInteractive"
        onLoad={() => setScriptLoaded(true)}
        onReady={() => setScriptLoaded(true)}
      />
      <input type="hidden" name={name} value={token} readOnly />
      <div ref={containerRef} />
    </div>
  );
}
