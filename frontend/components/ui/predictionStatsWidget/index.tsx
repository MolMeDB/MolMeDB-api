"use client";

import { useEffect, useState } from "react";
import { Chip, Skeleton, Tooltip } from "@heroui/react";
import {
  MdCheckCircleOutline,
  MdOutlineHourglassEmpty,
  MdOutlineRunCircle,
  MdPauseCircleOutline,
  MdRefresh,
} from "react-icons/md";

interface PredictionServerStats {
  jobs: {
    queued: number;
    running: number;
    paused?: number;
    completed: number;
    failed: number;
  };
  remote: {
    payload: Record<string, unknown>;
    fetched_at: string | null;
    server_url: string;
  } | null;
}

function formatRelativeTime(iso: string | null | undefined): string {
  if (!iso) return "N/A";
  const diff = Math.max(
    0,
    Math.floor((Date.now() - new Date(iso).getTime()) / 1000),
  );
  if (diff < 60) return `${diff}s ago`;
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
  return `${Math.floor(diff / 86400)}d ago`;
}

export default function PredictionStatsWidget() {
  const [stats, setStats] = useState<PredictionServerStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [fetchedAt, setFetchedAt] = useState<Date | null>(null);

  const load = async () => {
    try {
      setLoading(true);
      const res = await fetch("/api/predictions/server-stats");
      if (res.ok) {
        const json = await res.json();
        if (json?.data) {
          setStats(json.data);
          setFetchedAt(new Date());
        }
      }
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
  }, []);

  return (
    <div className="w-full rounded-xl border border-default-200 bg-content1 shadow-sm px-6 py-4">
      <div className="flex flex-wrap items-center gap-4 justify-between">
        <div className="flex flex-wrap items-center gap-3">
          <span className="text-sm font-semibold text-default-600">
            Server jobs
          </span>
          <div className="flex flex-wrap gap-2">
            {loading ? (
              <>
                <Skeleton aria-label="Loading stats" className="h-6 w-20 rounded-full" />
                <Skeleton aria-label="Loading stats" className="h-6 w-20 rounded-full" />
                <Skeleton aria-label="Loading stats" className="h-6 w-24 rounded-full" />
                <Skeleton aria-label="Loading stats" className="h-6 w-16 rounded-full" />
              </>
            ) : stats ? (
              <>
                <Chip
                  size="sm"
                  variant="flat"
                  color="default"
                  startContent={<MdOutlineHourglassEmpty size={13} />}
                >
                  Queued: {stats.jobs.queued}
                </Chip>
                <Chip
                  size="sm"
                  variant="flat"
                  color="warning"
                  startContent={<MdOutlineRunCircle size={13} />}
                >
                  Running: {stats.jobs.running}
                </Chip>
                {(stats.jobs.paused ?? 0) > 0 && (
                  <Chip
                    size="sm"
                    variant="flat"
                    color="default"
                    startContent={<MdPauseCircleOutline size={13} />}
                  >
                    Paused: {stats.jobs.paused ?? 0}
                  </Chip>
                )}
                <Chip
                  size="sm"
                  variant="flat"
                  color="success"
                  startContent={<MdCheckCircleOutline size={13} />}
                >
                  Completed: {stats.jobs.completed}
                </Chip>
              </>
            ) : (
              <span className="text-xs text-default-400">Unavailable</span>
            )}
          </div>
        </div>
        <div className="flex items-center gap-3 text-xs text-default-400">
          {stats?.remote?.fetched_at && (
            <Tooltip
              content={`Remote stats fetched from server ${formatRelativeTime(stats.remote.fetched_at)}`}
              placement="left"
            >
              <span className="cursor-default">
                Server stats:{" "}
                <span className="font-medium text-default-500">
                  {formatRelativeTime(stats.remote.fetched_at)}
                </span>
              </span>
            </Tooltip>
          )}
          {fetchedAt && (
            <button
              aria-label="Refresh server stats"
              onClick={load}
              className="flex items-center gap-1 hover:text-primary transition-colors cursor-pointer"
            >
              <MdRefresh size={14} />
              <span>Refresh</span>
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
