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
        setIsLoading(false);
        return;
      }

      addToast({
        title: "Error",
        description: "Failed to load stats data. Please, try again.",
        color: "danger",
        shouldShowTimeoutProgress: true,
        // timeout: 4500,
      });
    });
  }, []);

  return data && data?.byYear ? (
    <div className="relative flex flex-col gap-8 min-h-xl">
      {isLoading ? (
        <div className="absolute top-0 left-0 w-full h-32 flex flex-row justify-center items-center">
          <Spinner
            variant="wave"
            size="lg"
            color="primary"
            label="Loading..."
          />
        </div>
      ) : (
        <>
          <SectionChartsHistory
            data={data.byYear}
            minPublishedYear={data.minPublishedYear}
            totalPublications={data.total.publications}
          />
          <SectionChartsJournals data={data.byJournal} />
        </>
      )}
    </div>
  ) : null;
}
