"use client";

import Notification from "@/lib/api/admin/interfaces/Notification";
import { addToast } from "@heroui/react";
import { useEffect } from "react";

export default function SiteNotifications(props: {
  notifications?: Notification[];
}) {
  const notifications = props.notifications ?? [];

  useEffect(() => {
    notifications.forEach((notification) => {
      addToast({
        title: notification.title,
        description: notification.message,
        color: notification.type,
        timeout: 8000,
      });
    });
  }, [notifications]);

  return <></>;
}
