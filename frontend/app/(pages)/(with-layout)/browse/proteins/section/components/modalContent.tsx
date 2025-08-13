"use client";

import { getJson } from "@/lib/api/admin";
import IProtein, { IProteinStats } from "@/lib/api/admin/interfaces/Protein";
import {
  addToast,
  cn,
  ModalBody,
  ModalFooter,
  ModalHeader,
  Spinner,
} from "@heroui/react";
import { useEffect, useState } from "react";

export default function ProteinModalContent(props: {
  data: IProtein;
  onClose: () => void;
}) {
  const [stats, setStats] = useState<IProteinStats | null>(null);
  const [isLoading, setIsLoading] = useState(false);

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
            {/* <h3 className="text-sm text-foreground-500">{stats.protein.}</h3> */}
            <div className="flex flex-row gap-4">
              <div className="flex flex-col gap-0.5 w-1/2">
                <h4 className="text-primary font-bold">Statistics</h4>
                <div className="flex flex-row justify-between border-b-1 p-1">
                  <p>Total interactions</p>
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
              <div className="flex flex-col gap-2">
                <div className="flex flex-col gap-0.5 w-1/2">
                  <h4 className="text-primary font-bold">Export</h4>
                </div>
              </div>
            </div>
          </div>
        )}
      </ModalBody>
      <ModalFooter></ModalFooter>
    </>
  );
}
