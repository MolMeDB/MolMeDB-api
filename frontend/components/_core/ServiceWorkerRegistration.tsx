"use client";

import { useEffect } from "react";

/**
 * Registers the service worker app-wide (not just on the notification
 * settings page) — Chrome's PWA installability check requires an active
 * service worker with a fetch handler to be present regardless of which
 * page the user is on. Push subscription itself stays an explicit,
 * separate opt-in (see lib/api/frontend/pushNotifications.ts).
 */
export default function ServiceWorkerRegistration() {
  useEffect(() => {
    if ("serviceWorker" in navigator) {
      navigator.serviceWorker.register("/sw.js").catch(() => {});
    }
  }, []);

  return null;
}
