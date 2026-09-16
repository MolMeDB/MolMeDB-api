"use client";

import { getJson } from "@/lib/api/admin";
import IFile from "@/lib/api/admin/interfaces/File";
import IMethod, { IMethodStats } from "@/lib/api/admin/interfaces/Method";
import { downloadFile } from "@/utils/downloadFile";
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

export default function MethodModalContent(props: {
  data: IMethod;
  onClose: () => void;
}) {
  const [stats, setStats] = useState<IMethodStats | null>(null);
  const [lastExport, setLastExport] = useState<IFile | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [isExporting, setIsExporting] = useState(false);

  const handleDownload = async () => {
    if (!lastExport?.hash || isExporting) {
      return;
    }

    setIsExporting(true);

    try {
      await downloadFile(
        `/api/export/files/${lastExport.hash}`,
        `method-${props.data.abbreviation}-interactions.zip`,
      );
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
    getJson("/api/method/" + props.data.id + "/stats").then((response) => {
      if (response && response.code === 200) {
        setStats(response.data.data);
        if (response.data.data?.method?.datasets.length > 0)
          setLastExport(response.data.data?.method?.datasets[0]);
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
        <h1>Method: {props.data.abbreviation}</h1>
      </ModalHeader>
      <ModalBody>
        {isLoading || !stats ? (
          <div className="h-64 w-full flex flex-row justify-center items-center">
            <Spinner variant="wave" size="lg" color="primary" />
          </div>
        ) : (
          <div className="flex flex-col gap-8">
            <h3 className="text-sm text-foreground-500">{stats.method.name}</h3>
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
                  method.
                </p>
              </div>
              <div className="flex flex-col gap-0.5 w-full">
                <h4 className="text-primary font-bold">Statistics</h4>
                <div className="flex flex-row justify-between border-b-1 p-1">
                  <p>Total passive interactions</p>
                  <p
                    className={cn(
                      stats?.total.interactions_passive == 0
                        ? "text-red-500"
                        : "text-secondary"
                    )}
                  >
                    {stats?.total.interactions_passive}
                  </p>
                </div>
                <div className="flex flex-row justify-between border-b-1 p-1">
                  <p>Total measured structures</p>
                  <p
                    className={cn(
                      stats.total.structures == 0
                        ? "text-red-500"
                        : "text-secondary"
                    )}
                  >
                    {stats.total.structures}
                  </p>
                </div>
              </div>
              {lastExport?.hash ? (
                <p className="text-sm text-foreground/60">
                  Click below to download the data
                </p>
              ) : null}
              <div className="flex flex-col gap-1">
                <Button
                  isDisabled={!lastExport?.hash || isExporting}
                  isLoading={isExporting}
                  color="secondary"
                  size="lg"
                  onPress={handleDownload}
                >
                  {isExporting ? "Preparing export..." : "Export"}
                </Button>
                {lastExport && lastExport.created_at ? (
                  <p className="text-sm text-foreground/50">
                    Last update: {lastExport.created_at}
                  </p>
                ) : (
                  <p className="text-sm text-foreground/50">
                    No data prepared for current method.
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
