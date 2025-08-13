"use client";
import { ISearchQuery } from "@/lib/api/admin/interfaces/SearchEngine";
import {
  Input,
  Button,
  Kbd,
  Modal,
  ModalBody,
  ModalContent,
  ModalHeader,
  useDisclosure,
  cn,
} from "@heroui/react";
import { useCallback, useEffect, useState } from "react";
import { MdSearch } from "react-icons/md";
import RecentSearchList from "./components/recent";
import SearchListItems from "./components/list";

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

  useEffect(() => {
    if (isOpenSE) onOpen();
  }, [isOpenSE]);

  //////////////////////
  // Keydown handlers //
  //////////////////////
  const keyDownHandler = useCallback(
    (event: KeyboardEvent) => {
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
    [isOpen, currentQuery]
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

    if (query.query == "") {
      setIsSubmitted(false);
      return;
    }

    setCurrentQuery(query);
    setSubmittedQuery(query);
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
  }[] = [
    {
      key: "Structures",
      title: "Structures",
      placeholder: "Name, identfier, SMILES, ...",
    },
    {
      key: "Membranes",
      title: "Membranes",
      placeholder: "Membrane name, category, ...",
    },
    {
      key: "Methods",
      title: "Methods",
      placeholder: "Method name, category, ...",
    },
    {
      key: "Proteins",
      title: "Proteins",
      placeholder: "Uniprot ID, name, ...",
    },
    {
      key: "Datasets",
      title: "Datasets",
      placeholder: "Author, title, DOI, ...",
    },
  ];

  return (
    <Modal
      isOpen={isOpen}
      onOpenChange={onOpenChange}
      scrollBehavior="outside"
      isDismissable={false}
      backdrop="opaque"
      size="3xl"
      placement="top-center"
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
      <ModalContent className="">
        {(onClose) => (
          <>
            <ModalHeader>
              <div>
                <div>Search </div>
                <div></div>
              </div>
            </ModalHeader>
            <ModalBody>
              <div className="flex flex-col gap-6">
                <Input
                  type="text"
                  autoFocus
                  // label=""
                  size="md"
                  value={currentQuery.query}
                  onChange={(e) =>
                    setCurrentQuery({
                      ...currentQuery,
                      query: e.target.value.trim(),
                    })
                  }
                  placeholder={
                    searchGroups.find(
                      (group) => group.key === currentQuery.type
                    )?.placeholder
                  }
                  labelPlacement="outside"
                  startContent={
                    <MdSearch
                      size={25}
                      className="text-xl text-default-400 pointer-events-none flex-shrink-0"
                    />
                  }
                  endContent={
                    <Kbd
                      className="cursor-pointer"
                      onClick={() => submitQuery()}
                      keys={["enter"]}
                    ></Kbd>
                  }
                  className="focus:outline-none focus:border-0"
                  classNames={{
                    inputWrapper: [
                      "shadow-xl",
                      "bg-default-200/50",
                      "py-7 xpx-2",
                    ],
                    input: [
                      "text-md",
                      "font-sans",
                      "focus:border-0 active:border-0",
                    ],
                  }}
                />
                <div className="grid grid-cols-5 gap-4">
                  {searchGroups.map((group) => (
                    <Button
                      key={group.key}
                      size="md"
                      color={
                        currentQuery.type === group.key ? "warning" : "default"
                      }
                      onPress={() =>
                        setCurrentQuery({
                          ...currentQuery,
                          type: group.key,
                        })
                      }
                    >
                      {group.title}
                    </Button>
                  ))}
                </div>
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
            </ModalBody>
          </>
        )}
      </ModalContent>
    </Modal>
  );
}
