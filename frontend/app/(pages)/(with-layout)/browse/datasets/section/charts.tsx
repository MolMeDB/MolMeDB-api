"use client";
import { getJson } from "@/lib/api/admin";
import SectionChartsHistory from "./charts/history";
import {
  IBarChartSetting,
  ILineChartSetting,
} from "@/lib/api/admin/interfaces/Stats";
import { useEffect, useState } from "react";
import { addToast, Spinner } from "@heroui/react";
import SectionChartsJournals from "./charts/journals";

export default function SectionStats() {
  const [isLoading, setIsLoading] = useState(true);
  const [hasError, setHasError] = useState(false);
  const [data, setData] = useState<{
    total: {
      publications: number;
    };
    minPublishedYear: number;
    byYear: ILineChartSetting;
    byJournal: IBarChartSetting;
  } | null>(null);

  useEffect(() => {
    getJson("/api/stats/publications").then((response) => {
      if (response && response.code === 200) {
        setData(response.data.data);
        setHasError(false);
        setIsLoading(false);
        return;
      }

      setHasError(true);
      setIsLoading(false);

      addToast({
        title: "Error",
        description: "Failed to load stats data. Please, try again.",
        color: "danger",
        shouldShowTimeoutProgress: true,
        // timeout: 4500,
      });
    });
  }, []);

  if (isLoading) {
    return (
      <div className="flex min-h-[420px] w-full items-center justify-center">
        <Spinner
          variant="wave"
          size="lg"
          color="primary"
          label="Loading stats..."
        />
      </div>
    );
  }

  if (hasError || !data?.byYear) {
    return (
      <div className="flex min-h-[240px] w-full items-center justify-center px-4 text-center text-foreground-500">
        Stats data could not be loaded.
      </div>
    );
  }

  return (
    <div className="relative flex flex-col gap-8">
      <SectionChartsHistory
        data={data.byYear}
        minPublishedYear={data.minPublishedYear}
        totalPublications={data.total.publications}
      />
      <SectionChartsJournals data={data.byJournal} />
    </div>
  );
}
