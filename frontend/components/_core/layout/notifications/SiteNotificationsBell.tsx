"use client";

import {
  Badge,
  Button,
  Dropdown,
  DropdownItem,
  DropdownMenu,
  DropdownTrigger,
  Spinner,
} from "@heroui/react";
import { useCallback, useEffect, useState } from "react";
import { FiBell } from "react-icons/fi";

type UserNotification = {
  id: number;
  state: "new" | "read";
  title: string;
  body: string;
  created_at?: string | null;
  read_at?: string | null;
};

type NotificationsResponse = {
  data?: UserNotification[];
  meta?: {
    unread_count?: number;
  };
};

export default function SiteNotificationsBell() {
  const [notifications, setNotifications] = useState<UserNotification[]>([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [isLoading, setIsLoading] = useState(false);

  const loadNotifications = useCallback(async () => {
    setIsLoading(true);

    try {
      const response = await fetch("/api/notifications", {
        headers: {
          Accept: "application/json",
        },
      });

      if (!response.ok) {
        return;
      }

      const json = (await response.json()) as NotificationsResponse;
      setNotifications(json.data ?? []);
      setUnreadCount(json.meta?.unread_count ?? 0);
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    loadNotifications();
  }, [loadNotifications]);

  async function markAsRead() {
    if (unreadCount === 0) {
      return;
    }

    setUnreadCount(0);
    setNotifications((current) =>
      current.map((notification) => ({
        ...notification,
        state: "read",
      })),
    );

    await fetch("/api/notifications/read", {
      method: "POST",
      headers: {
        Accept: "application/json",
      },
    });
  }

  return (
    <Dropdown
      placement="bottom-end"
      onOpenChange={(isOpen) => {
        if (isOpen) {
          markAsRead();
        }
      }}
    >
      <DropdownTrigger>
        <Button
          isIconOnly
          aria-label="Notifications"
          radius="full"
          variant="light"
        >
          <Badge
            color="danger"
            content={unreadCount > 9 ? "9+" : unreadCount}
            isInvisible={unreadCount === 0}
            shape="circle"
          >
            <FiBell className="h-5 w-5" />
          </Badge>
        </Button>
      </DropdownTrigger>
      <DropdownMenu
        aria-label="Notifications"
        className="w-80"
        emptyContent={isLoading ? "Loading notifications..." : "No notifications"}
        disabledKeys={["loading"]}
      >
        {isLoading ? (
          <DropdownItem key="loading" textValue="Loading">
            <div className="flex items-center justify-center py-4">
              <Spinner size="sm" />
            </div>
          </DropdownItem>
        ) : (
          notifications.map((notification) => (
            <DropdownItem
              key={notification.id}
              textValue={notification.title}
              className={notification.state === "new" ? "bg-primary-50" : ""}
            >
              <div className="flex flex-col gap-1 whitespace-normal py-1">
                <div className="text-sm font-semibold">
                  {notification.title}
                </div>
                <div className="text-xs text-foreground-600">
                  {notification.body}
                </div>
                {notification.created_at ? (
                  <div className="text-[11px] text-foreground-400">
                    {new Date(notification.created_at).toLocaleString()}
                  </div>
                ) : null}
              </div>
            </DropdownItem>
          ))
        )}
      </DropdownMenu>
    </Dropdown>
  );
}
