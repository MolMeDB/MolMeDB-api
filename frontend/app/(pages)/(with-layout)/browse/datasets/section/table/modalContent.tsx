"use client";

import { getJson } from "@/lib/api/admin";
import IPublication from "@/lib/api/admin/interfaces/Publication";
import {
  addToast,
  cn,
  ModalBody,
  ModalFooter,
  ModalHeader,
  Spinner,
} from "@heroui/react";
import { useEffect, useState } from "react";

export default function PublicationModalContent(props: {
  id: number;
  onClose: () => void;
}) {
  const [publication, setPublication] = useState<IPublication | null>(null);
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    getJson("/api/publication/" + props.id + "/stats").then((response) => {
      if (response && response.code === 200) {
        setPublication(response.data.data);
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
    publication?.stats?.passive_interactions ??
    0 + (publication?.stats?.active_interactions ?? 0);
  const totalSecondaryInteracitons =
    publication?.stats?.dataset_active_interactions ??
    0 + (publication?.stats?.dataset_passive_interactions ?? 0);

  return (
    <>
      <ModalHeader className="flex flex-col gap-1">Source detail</ModalHeader>
      <ModalBody>
        {isLoading || !publication ? (
          <div className="h-64 w-full flex flex-row justify-center items-center">
            <Spinner variant="wave" size="lg" color="primary" />
          </div>
        ) : (
          <div className="flex flex-col gap-8">
            <h3 className="text-sm text-foreground-500">
              {publication.citation}
            </h3>
            <div className="flex flex-row gap-4">
              <div className="flex flex-col gap-0.5 w-1/2">
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
                  <p>Total interactions (primary source)</p>
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
                  <p>Total interactions (secondary source)</p>
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
              <div className="flex flex-col gap-2">
                <div className="flex flex-col gap-0.5 w-1/2">
                  <h4 className="text-primary font-bold">Export</h4>
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
