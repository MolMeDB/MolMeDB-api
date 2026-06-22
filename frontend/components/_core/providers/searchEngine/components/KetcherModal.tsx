"use client";

import type { Ketcher } from "ketcher-core";
import dynamic from "next/dynamic";
import { useEffect, useRef, useState } from "react";
import { FiSearch } from "react-icons/fi";
import {
  Alert,
  Button,
  Modal,
  ModalBody,
  ModalContent,
  ModalFooter,
  ModalHeader,
  Spinner,
} from "@heroui/react";

const KetcherEditor = dynamic(() => import("./KetcherEditor"), {
  ssr: false,
  loading: () => (
    <div className="flex h-full items-center justify-center">
      <Spinner label="Loading structure editor..." variant="wave" />
    </div>
  ),
});

export default function KetcherModal(props: {
  isOpen: boolean;
  onClose: () => void;
  onSearch: (smiles: string) => void;
}) {
  const ketcherRef = useRef<Ketcher | null>(null);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [isReady, setIsReady] = useState(false);
  const [isConverting, setIsConverting] = useState(false);

  useEffect(() => {
    if (!props.isOpen) {
      ketcherRef.current = null;
      setIsReady(false);
      setIsConverting(false);
      setErrorMessage(null);
    }
  }, [props.isOpen]);

  function handleInit(ketcher: Ketcher) {
    ketcherRef.current = ketcher;
    setIsReady(true);
    setErrorMessage(null);
  }

  async function handleSearch() {
    if (!ketcherRef.current) {
      return;
    }

    setIsConverting(true);
    setErrorMessage(null);

    try {
      if (ketcherRef.current.containsReaction()) {
        throw new Error("Draw one molecule without reaction arrows.");
      }

      const smiles = (await ketcherRef.current.getSmiles()).trim();

      if (!smiles) {
        throw new Error("Draw a molecule before starting the search.");
      }

      if (smiles.includes(".")) {
        throw new Error(
          "Only one connected molecule can be searched at a time.",
        );
      }

      props.onSearch(smiles);
    } catch (error) {
      setErrorMessage(
        error instanceof Error
          ? error.message
          : "The structure could not be converted to SMILES.",
      );
    } finally {
      setIsConverting(false);
    }
  }

  return (
    <Modal
      backdrop="opaque"
      isDismissable={false}
      isOpen={props.isOpen}
      scrollBehavior="inside"
      size="5xl"
      onClose={props.onClose}
    >
      <ModalContent className="max-h-[calc(100dvh-2rem)]">
        <ModalHeader className="flex flex-col gap-1">
          <span>Draw molecular structure</span>
          <span className="text-sm font-normal text-foreground-500">
            Draw one connected molecule. It will be converted to SMILES and
            searched in MolMeDB.
          </span>
        </ModalHeader>
        <ModalBody className="gap-3">
          {errorMessage ? (
            <Alert color="danger" title="Structure cannot be used">
              {errorMessage}
            </Alert>
          ) : null}
          <div className="h-[min(68dvh,680px)] min-h-[420px] overflow-hidden rounded-md border border-default-200 bg-white">
            {props.isOpen ? (
              <KetcherEditor
                onError={setErrorMessage}
                onInit={handleInit}
              />
            ) : null}
          </div>
        </ModalBody>
        <ModalFooter>
          <Button variant="light" onPress={props.onClose}>
            Cancel
          </Button>
          <Button
            color="primary"
            isDisabled={!isReady}
            isLoading={isConverting}
            startContent={!isConverting ? <FiSearch /> : null}
            onPress={handleSearch}
          >
            Use for search
          </Button>
        </ModalFooter>
      </ModalContent>
    </Modal>
  );
}
