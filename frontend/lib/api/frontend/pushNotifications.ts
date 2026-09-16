import { deleteJson, postJson } from "@/lib/api/admin";

const VAPID_PUBLIC_KEY = process.env.NEXT_PUBLIC_VAPID_PUBLIC_KEY;

function urlBase64ToUint8Array(base64String: string): Uint8Array {
  const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, "+").replace(/_/g, "/");
  const rawData = atob(base64);

  return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
}

export function isPushSupported(): boolean {
  return (
    typeof window !== "undefined" &&
    "serviceWorker" in navigator &&
    "PushManager" in window &&
    Boolean(VAPID_PUBLIC_KEY)
  );
}

export async function getExistingPushSubscription(): Promise<PushSubscription | null> {
  if (!isPushSupported()) {
    return null;
  }

  const registration = await navigator.serviceWorker.getRegistration();

  return (await registration?.pushManager.getSubscription()) ?? null;
}

/**
 * Registers the service worker, requests browser notification permission,
 * subscribes to push, and saves the subscription on the backend.
 */
export async function subscribeToPush(): Promise<boolean> {
  if (!isPushSupported()) {
    return false;
  }

  const permission = await Notification.requestPermission();

  if (permission !== "granted") {
    return false;
  }

  const registration = await navigator.serviceWorker.register("/sw.js");
  await navigator.serviceWorker.ready;

  const subscription =
    (await registration.pushManager.getSubscription()) ??
    (await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY as string),
    }));

  const response = await postJson(
    "/api/notifications/push-subscriptions",
    subscription.toJSON() as Record<string, unknown>,
  );

  return Boolean(response && response.code < 400);
}

export async function unsubscribeFromPush(): Promise<void> {
  const subscription = await getExistingPushSubscription();

  if (!subscription) {
    return;
  }

  await deleteJson("/api/notifications/push-subscriptions", {
    endpoint: subscription.endpoint,
  });

  await subscription.unsubscribe();
}
