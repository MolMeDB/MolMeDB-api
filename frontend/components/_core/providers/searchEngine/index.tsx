"use client";
import { ISearchQuery } from "@/lib/api/admin/interfaces/SearchEngine";
import {
  Input,
  Button,
  Kbd,
  Modal,
  ModalBody,
  ModalContent,
  useDisclosure,
  cn,
} from "@heroui/react";
import { type ReactNode, useCallback, useEffect, useState } from "react";
import { GiMolecule } from "react-icons/gi";
import {
  MdBiotech,
  MdDataset,
  MdDraw,
  MdSearch,
  MdWaterDrop,
} from "react-icons/md";
import { PiAtomBold } from "react-icons/pi";
import RecentSearchList from "./components/recent";
import SearchListItems from "./components/list";
import KetcherModal from "./components/KetcherModal";

export default function SearchEngine({ isOpenSE = false, onClose = () => {} }) {
  const { isOpen, onOpen, onOpenChange } = useDisclosure();
  const [currentQuery, setCurrentQuery] = useState<ISearchQuery>({
    query: "",
    type: "Structures",
  });
  const [submittedQuery, setSubmittedQuery] = useState<ISearchQuery>({
    query: "",
    type: "Structures",
  });
  const [isSubmitted, setIsSubmitted] = useState(false);
  const [isStructureEditorOpen, setIsStructureEditorOpen] = useState(false);

  useEffect(() => {
    if (isOpenSE) onOpen();
  }, [isOpenSE]);

  //////////////////////
  // Keydown handlers //
  //////////////////////
  const keyDownHandler = useCallback(
    (event: KeyboardEvent) => {
      if (isStructureEditorOpen) {
        return;
      }

      if ((event.ctrlKey || event.metaKey) && event.key === "k" && !isOpen) {
        onOpen();
      }

      if (isOpen && event.key === "Escape") {
        onClose();
      }

      if (isOpen && event.key === "Enter") {
        submitQuery();
      }
    },
    [isOpen, currentQuery, isStructureEditorOpen],
  );

  useEffect(() => {
    window.addEventListener("keydown", keyDownHandler);
    return () => window.removeEventListener("keydown", keyDownHandler);
  }, [keyDownHandler]);

  useEffect(() => {
    if (!isOpen) onClose();
  }, [isOpen]);

  const submitQuery = (query?: ISearchQuery) => {
    if (query == undefined) {
      query = currentQuery;
    }

    const normalizedQuery = query.query.trim();

    if (normalizedQuery == "") {
      setIsSubmitted(false);
      return;
    }

    const submitted = {
      ...query,
      query: normalizedQuery,
    };

    setCurrentQuery(submitted);
    setSubmittedQuery(submitted);
    setIsSubmitted(true);
  };

  useEffect(() => {
    if (currentQuery.query == "") submitQuery();
  }, [currentQuery.query]);

  //////////////////////
  /// Search groups ////
  //////////////////////
  const searchGroups: {
    key: ISearchQuery["type"];
    title: string;
    placeholder: string;
    icon: ReactNode;
  }[] = [
    {
      key: "Structures",
      title: "Structures",
      placeholder: "Name, identifier, SMILES, ...",
      icon: <GiMolecule size={18} />,
    },
    {
      key: "Membranes",
      title: "Membranes",
      placeholder: "Membrane name, category, ...",
      icon: <MdWaterDrop size={18} />,
    },
    {
      key: "Methods",
      title: "Methods",
      placeholder: "Method name, category, ...",
      icon: <MdBiotech size={18} />,
    },
    {
      key: "Proteins",
      title: "Proteins",
      placeholder: "Uniprot ID, name, ...",
      icon: <PiAtomBold size={18} />,
    },
    {
      key: "Datasets",
      title: "Datasets",
      placeholder: "Author, title, DOI, ...",
      icon: <MdDataset size={18} />,
    },
  ];

  const selectedGroup = searchGroups.find(
    (group) => group.key === currentQuery.type,
  );

  function searchDrawnStructure(smiles: string) {
    submitQuery({
      query: smiles,
      type: "Structures",
      isDrawnStructure: true,
    });
    setIsStructureEditorOpen(false);
  }

  return (
    <>
      <Modal
      isOpen={isOpen}
      onOpenChange={onOpenChange}
      scrollBehavior="outside"
      isDismissable={false}
      backdrop="opaque"
      size="3xl"
      placement="top-center"
      classNames={{
        base: "bg-white dark:bg-background-dark",
        backdrop: "bg-background/40 backdrop-blur-sm",
        body: "p-0",
        closeButton:
          "top-4 right-4 text-foreground-500 hover:bg-default-100 dark:hover:bg-background-dark-2",
      }}
      motionProps={{
        variants: {
          enter: {
            y: 20,
            opacity: 1,
            transition: {
              duration: 0.5,
              ease: "easeOut",
            },
          },
          exit: {
            y: -20,
            opacity: 0,
            transition: {
              duration: 0.3,
              ease: "easeIn",
            },
          },
        },
      }}
    >
      <ModalContent className="overflow-hidden border border-default-200 bg-white shadow-2xl dark:bg-background-dark">
        {(onClose) => (
          <>
            <ModalBody>
              <div className="flex flex-col">
                <div className="border-b border-default-200 bg-white px-8 py-6 pr-14 dark:bg-background-dark">
                  <div className="flex min-w-0 flex-col gap-1 py-4">
                    <div className="flex items-center gap-2 text-lg font-semibold text-foreground">
                      <MdSearch size={22} />
                      <span>Search</span>
                    </div>
                    <p className="text-sm text-foreground-500">
                      Find records across MolMeDB. Supported results can be
                      added directly to the Downloader.
                    </p>
                  </div>
                </div>

                <div className="flex flex-col gap-6 bg-white px-8 pb-8 pt-6 dark:bg-background-dark">
                  <div className="flex flex-col gap-2 sm:flex-row">
                    <Input
                      type="text"
                      autoFocus
                      size="lg"
                      value={currentQuery.query}
                      onChange={(e) =>
                        setCurrentQuery({
                          ...currentQuery,
                          query: e.target.value,
                          isDrawnStructure: false,
                        })
                      }
                      placeholder={selectedGroup?.placeholder}
                      aria-label="Search query"
                      labelPlacement="outside"
                      startContent={
                        <MdSearch
                          size={24}
                          className="text-default-400 pointer-events-none flex-shrink-0"
                        />
                      }
                      endContent={
                        <button
                          type="button"
                          className="flex items-center"
                          onClick={() => submitQuery()}
                          aria-label="Submit search"
                        >
                          <Kbd keys={["enter"]}></Kbd>
                        </button>
                      }
                      classNames={{
                        inputWrapper:
                          "h-14 rounded-lg border border-default-200 bg-default-100 px-2 shadow-none data-[hover=true]:bg-default-100 group-data-[focus=true]:bg-white dark:bg-background-dark-2 dark:group-data-[focus=true]:bg-background-dark-2",
                        input: "text-md font-sans",
                      }}
                    />
                    {currentQuery.type === "Structures" ? (
                      <Button
                        className="h-14 shrink-0"
                        color="secondary"
                        startContent={<MdDraw size={20} />}
                        variant="flat"
                        onPress={() => setIsStructureEditorOpen(true)}
                      >
                        Draw structure
                      </Button>
                    ) : null}
                  </div>

                  <div className="grid grid-cols-1 gap-2 rounded-lg bg-default-50 p-2 dark:bg-background-dark-2 sm:grid-cols-2 lg:grid-cols-5">
                    {searchGroups.map((group) => {
                      const isSelected = currentQuery.type === group.key;

                      return (
                        <Button
                          key={group.key}
                          size="md"
                          variant={isSelected ? "solid" : "flat"}
                          color={isSelected ? "primary" : "default"}
                          startContent={group.icon}
                          className={cn(
                            "h-10 justify-start rounded-md text-sm font-medium",
                            isSelected
                              ? "shadow-sm"
                              : "bg-white text-foreground-600 dark:bg-background-dark",
                          )}
                          onPress={() =>
                            setCurrentQuery({
                              ...currentQuery,
                              type: group.key,
                              isDrawnStructure: false,
                            })
                          }
                        >
                          {group.title}
                        </Button>
                      );
                    })}
                  </div>

                  <div className="min-h-48 pb-4">
                    <div className={cn(isSubmitted && "hidden")}>
                      <RecentSearchList
                        onSubmitQuery={submitQuery}
                        submittedQuery={submittedQuery}
                      />
                    </div>
                    <div className={cn(!isSubmitted && "hidden")}>
                      <SearchListItems searchOptions={submittedQuery} />
                    </div>
                  </div>
                </div>
              </div>
            </ModalBody>
          </>
        )}
      </ModalContent>
      </Modal>
      <KetcherModal
        isOpen={isStructureEditorOpen}
        onClose={() => setIsStructureEditorOpen(false)}
        onSearch={searchDrawnStructure}
      />
    </>
  );
}
