"use client";

import { getJson } from "@/lib/api/admin";
import HttpJsonResponse from "@/lib/api/admin/interfaces/http/jsonResponse";
import IMembrane from "@/lib/api/admin/interfaces/Membrane";
import {
  addToast,
  Button,
  Modal,
  ModalContent,
  Spinner,
  useDisclosure,
} from "@heroui/react";
import { useEffect, useRef, useState } from "react";
import { MdCloudDownload } from "react-icons/md";
import MembraneModalContent from "./components/modalContent";

export default function SectionDetail(props: { membraneId: string }) {
  const [data, setData] = useState<IMembrane | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const detailSectionRef = useRef<HTMLDivElement>(null);
  const { isOpen, onOpen, onOpenChange } = useDisclosure();

  const scrollToElement = () => {
    if (detailSectionRef.current == null) return;
    const y =
      detailSectionRef.current.getBoundingClientRect().top + window.scrollY;
    const offset = 250;
    window.scrollTo({ top: y - offset, behavior: "smooth" });
  };

  useEffect(() => {
    if (props.membraneId == "") {
      setData(null);
      return;
    }

    setIsLoading(true);

    getJson("/api/membrane/" + props.membraneId).then(
      (d: HttpJsonResponse | null) => {
        if (!d || d.code !== 200) {
          setData(null);
          addToast({
            title: "Error",
            description:
              "Problem occured during membrane data loading. Please, try again.",
            color: "danger",
            shouldShowTimeoutProgress: true,
            timeout: 8000,
          });
          return;
        }

        setData(d.data?.data);
        scrollToElement();
        setIsLoading(false);
      }
    );
  }, [props.membraneId]);

  return (
    <div ref={detailSectionRef} className="min-h-64 w-full">
      {isLoading ? (
        <div className="flex-1 flex justify-center items-center">
          <Spinner label="Loading..." variant="wave" />
        </div>
      ) : (
        <div className="flex flex-col gap-4">
          <div className="flex justify-between items-center gap-16">
            <h1 className="text-2xl font-bold text-secondary">{data?.name}</h1>
            {data ? (
              <Button
                color="primary"
                size="md"
                variant="flat"
                startContent={<MdCloudDownload />}
                onPress={onOpen}
              >
                Measurements
              </Button>
            ) : null}
          </div>
          <div
            className="html-content-block"
            dangerouslySetInnerHTML={{ __html: data?.description || "" }}
          />
        </div>
      )}
      {data ? (
        <Modal isOpen={isOpen} onOpenChange={onOpenChange} size="3xl">
          <ModalContent>
            {(onClose) => (
              <>
                <MembraneModalContent onClose={onClose} data={data} />
              </>
            )}
          </ModalContent>
        </Modal>
      ) : null}
    </div>
  );
}
