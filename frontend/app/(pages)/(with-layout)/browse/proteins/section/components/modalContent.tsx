"use client";

import { getJson } from "@/lib/api/admin";
import IProtein, { IProteinStats } from "@/lib/api/admin/interfaces/Protein";
import {
  addToast,
  Button,
  cn,
  ModalBody,
  ModalFooter,
  ModalHeader,
  Spinner,
} from "@heroui/react";
import Image from "next/image";
import { useEffect, useState } from "react";
import { MdDownload } from "react-icons/md";

export default function ProteinModalContent(props: {
  data: IProtein;
  onClose: () => void;
}) {
  const [stats, setStats] = useState<IProteinStats | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [isExporting, setIsExporting] = useState(false);

  const downloadFilename = (contentDisposition: string | null) => {
    const match = contentDisposition?.match(/filename="?([^"]+)"?/);

    return match?.[1] || `protein-${props.data.uniprot_id}-interactions.csv`;
  };

  const handleExport = async () => {
    if (!stats?.interactions_count || isExporting) {
      return;
    }

    setIsExporting(true);

    try {
      const response = await fetch(
        `/api/export/protein/${props.data.id}/interactions`,
      );

      if (!response.ok) {
        throw new Error("Export failed.");
      }

      const blob = await response.blob();
      const url = URL.createObjectURL(blob);
      const link = document.createElement("a");

      link.href = url;
      link.download = downloadFilename(response.headers.get("content-disposition"));
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(url);
    } catch {
      addToast({
        title: "Export failed",
        description:
          "An error occurred while preparing the file. Please try again later.",
        color: "danger",
        shouldShowTimeoutProgress: true,
        timeout: 6000,
      });
    } finally {
      setIsExporting(false);
    }
  };

  useEffect(() => {
    getJson("/api/protein/" + props.data.id + "/stats").then((response) => {
      if (response && response.code === 200) {
        setStats(response.data.data);
        setIsLoading(false);
        return;
      }

      addToast({
        title: "Error",
        description: "Failed to load publication data. Please, try again.",
        color: "danger",
        shouldShowTimeoutProgress: true,
        timeout: 4500,
      });
      props.onClose();
    });
  }, [props.data.id]);

  return (
    <>
      <ModalHeader>
        <h1>Uniprot ID: {props.data.uniprot_id}</h1>
      </ModalHeader>
      <ModalBody>
        {isLoading || !stats ? (
          <div className="h-64 w-full flex flex-row justify-center items-center">
            <Spinner variant="wave" size="lg" color="primary" />
          </div>
        ) : (
          <div className="flex flex-col gap-8">
            <h3 className="text-sm text-foreground-500">
              {stats.protein.uniprot_id}
            </h3>
            <div className="flex flex-col items-center gap-4">
              <div className="flex flex-col gap-1 items-center">
                <Image
                  src="/assets/icons/csv_file.png"
                  alt="CSV file icon"
                  width={125}
                  height={125}
                />
                <h1 className="text-2xl font-bold">Export data</h1>
                <p>
                  You can export all interactions data measured with this
                  protein.
                </p>
              </div>
              <div className="flex flex-col gap-0.5 w-full">
                <h4 className="text-primary font-bold">Statistics</h4>
                <div className="flex flex-row justify-between border-b-1 p-1">
                  <p>Total passive interactions</p>
                  <p
                    className={cn(
                      stats?.interactions_count == 0
                        ? "text-red-500"
                        : "text-secondary"
                    )}
                  >
                    {stats?.interactions_count}
                  </p>
                </div>
                <div className="flex flex-row justify-between border-b-1 p-1">
                  <p>Total measured structures</p>
                  <p
                    className={cn(
                      stats.structures_count == 0
                        ? "text-red-500"
                        : "text-secondary"
                    )}
                  >
                    {stats.structures_count}
                  </p>
                </div>
              </div>
              {stats?.interactions_count ? (
                <p className="text-sm text-foreground/60">
                  Click below to download the data
                </p>
              ) : null}
              <div className="flex flex-col gap-1">
                <Button
                  isDisabled={!stats?.interactions_count || isExporting}
                  isLoading={isExporting}
                  color="secondary"
                  size="lg"
                  startContent={!isExporting ? <MdDownload size={22} /> : null}
                  onPress={handleExport}
                >
                  {isExporting ? "Preparing export..." : "Export"}
                </Button>
                {stats?.interactions_count ? (
                  <p className="text-sm text-foreground/50">
                    Last update: Up-to-date
                  </p>
                ) : (
                  <p className="text-sm text-foreground/50">
                    No data found for current protein.
                  </p>
                )}
              </div>
            </div>
          </div>
        )}
      </ModalBody>
      <ModalFooter></ModalFooter>
    </>
  );
}
