"use client";

import { getJson } from "@/lib/api/admin";
import IFile, {
  IFileTypeExportIntAct,
  IFileTypeExportIntPass,
} from "@/lib/api/admin/interfaces/File";
import IPublication from "@/lib/api/admin/interfaces/Publication";
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
import Link from "next/link";
import { useEffect, useState } from "react";

const BACKEND_URL = process.env.NEXT_PUBLIC_BACKEND_URL;

export default function PublicationModalContent(props: {
  id: number;
  onClose: () => void;
}) {
  const [publication, setPublication] = useState<IPublication | null>(null);
  const [lastPassiveExport, setLastPassiveExport] = useState<IFile | null>(
    null
  );
  const [lastActiveExport, setLastActiveExport] = useState<IFile | null>(null);

  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    getJson("/api/publication/" + props.id + "/stats").then((response) => {
      if (response && response.code === 200) {
        setPublication(response.data.data);
        if (response.data.data?.datasets.length > 0)
          setLastPassiveExport(
            response.data.data?.datasets.find(
              (d: IFile) => d.type == IFileTypeExportIntPass
            )
          );
        setLastActiveExport(
          response.data.data?.datasets.find(
            (d: IFile) => d.type == IFileTypeExportIntAct
          )
        );
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
  }, [props.id]);

  const totalPrimaryInteracitons =
    (publication?.stats?.passive_interactions ?? 0) +
    (publication?.stats?.active_interactions ?? 0);
  const totalSecondaryInteracitons =
    (publication?.stats?.dataset_active_interactions ?? 0) +
    (publication?.stats?.dataset_passive_interactions ?? 0);

  return (
    <>
      <ModalHeader className="flex flex-col gap-1">Source detail</ModalHeader>
      <ModalBody className="">
        {isLoading || !publication ? (
          <div className="h-64 w-full flex flex-row justify-center items-center">
            <Spinner variant="wave" size="lg" color="primary" />
          </div>
        ) : (
          <div className="flex flex-col gap-8">
            <h3 className="text-sm text-foreground-500">
              {publication.citation}
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
                <p>You can export all interactions assigned to this dataset.</p>
              </div>
              <div className="flex flex-col gap-0.5 w-full">
                <h4 className="text-primary font-bold">Statistics</h4>
                <div className="flex flex-row justify-between border-b-1 p-1">
                  <p>Total membranes</p>
                  <p
                    className={cn(
                      publication.stats?.membranes == 0
                        ? "text-red-500"
                        : "text-secondary"
                    )}
                  >
                    {publication.stats?.membranes}
                  </p>
                </div>
                <div className="flex flex-row justify-between border-b-1 p-1">
                  <p>Total methods</p>
                  <p
                    className={cn(
                      publication.stats?.methods == 0
                        ? "text-red-500"
                        : "text-secondary"
                    )}
                  >
                    {publication.stats?.methods}
                  </p>
                </div>
                <div className="flex flex-row justify-between border-b-1 p-1">
                  <p>Total interactions (as primary source)</p>
                  <p
                    className={cn(
                      totalPrimaryInteracitons == 0
                        ? "text-red-500"
                        : "text-secondary"
                    )}
                  >
                    {totalPrimaryInteracitons}
                  </p>
                </div>
                <div className="flex flex-row justify-between border-b-1 p-1">
                  <p>Total interactions (as secondary source)</p>
                  <p
                    className={cn(
                      totalSecondaryInteracitons == 0
                        ? "text-red-500"
                        : "text-secondary"
                    )}
                  >
                    {totalSecondaryInteracitons}
                  </p>
                </div>
              </div>
              {lastPassiveExport?.hash || lastActiveExport?.hash ? (
                <p className="text-sm text-foreground/60">
                  Click below to download the data
                </p>
              ) : null}
              <div className="flex flex-row justify-center gap-4">
                <div className="flex flex-col justify-center items-center gap-1">
                  <Button
                    as={Link}
                    href={`${BACKEND_URL}/download/public/${lastPassiveExport?.hash}`}
                    isDisabled={!lastPassiveExport || !lastPassiveExport.hash}
                    color="secondary"
                    size="lg"
                  >
                    Passive interactions
                  </Button>
                  {lastPassiveExport && lastPassiveExport.created_at ? (
                    <p className="text-sm text-foreground/50">
                      Last update: {lastPassiveExport.created_at}
                    </p>
                  ) : (
                    <p className="text-sm text-foreground/50">
                      No data prepared for current dataset
                    </p>
                  )}
                </div>
                <div className="flex flex-col justify-center items-center gap-1">
                  <Button
                    as={Link}
                    href={`${BACKEND_URL}/download/public/${lastActiveExport?.hash}`}
                    isDisabled={!lastActiveExport || !lastActiveExport.hash}
                    color="secondary"
                    size="lg"
                  >
                    Active interactions
                  </Button>
                  {lastActiveExport && lastActiveExport.created_at ? (
                    <p className="text-sm text-foreground/50">
                      Last update: {lastActiveExport.created_at}
                    </p>
                  ) : (
                    <p className="text-sm text-foreground/50">
                      No data prepared for current dataset
                    </p>
                  )}
                </div>
              </div>
            </div>
          </div>
        )}
      </ModalBody>
      <ModalFooter />
    </>
  );
}
