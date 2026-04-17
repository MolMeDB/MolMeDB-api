"use client";

import { get } from "@/lib/api/admin";
import {
  Alert,
  Button,
  Card,
  CardBody,
  CardFooter,
  CardHeader,
  Input,
  Modal,
  ModalContent,
  Textarea,
  useDisclosure,
} from "@heroui/react";
import Link from "next/link";
import { useEffect, useState } from "react";
import { FaCheck } from "react-icons/fa6";
import { IoSend } from "react-icons/io5";

export default function AddNewCalculationForm() {
  const [setting, setSetting] = useState<{
    membranes: null | any[];
    methods: null | any[];
    priority: string | number | null;
    description: null | string;
    smiles: null | string;
    validated_smiles?: string[];
    temperature: number;
  }>({
    membranes: null,
    methods: null,
    priority: null,
    description: null,
    smiles: null,
    validated_smiles: undefined,
    temperature: 25,
  });

  const [isValidated, setIsValidated] = useState<boolean>(false);

  const [isValidating, setIsValidating] = useState<boolean>(false);
  const [isSaving, setIsSaving] = useState<boolean>(false);

  const [validatorErrors, setValidatorErrors] = useState<string[]>([]);
  const [validatingState, setValidatingState] =
    useState<string>("Please wait...");

  const { isOpen, onOpen, onOpenChange } = useDisclosure();

  useEffect(() => {
    setIsValidated(false);
    setValidatingState("Please wait...");
  }, [setting.membranes, setting.methods, setting.smiles, setting.temperature]);

  useEffect(() => {
    if (!setting.description || !setting.priority) {
      setIsValidated(false);
      setValidatingState("Please wait...");
    }
  }, [setting.description, setting.priority]);

  async function validate() {
    setIsValidating(true);
    onOpen();
    const errors: string[] = [];

    // Sleep
    await new Promise((resolve) => setTimeout(resolve, 1000));

    // Validate membrane
    if (!setting.membranes || setting.membranes.length === 0) {
      errors.push("Please select at least one membrane.");
    }

    // Validate method
    if (!setting.methods || setting.methods.length === 0) {
      errors.push("Please select at least one method.");
    }

    // Validate description
    if (!setting.description || setting.description.trim().length === 0) {
      errors.push("Please provide a description for the calculation.");
    }

    // Validate SMILES
    var validated_smiles: string[] = [];
    var processed_smiles: string[] = [];
    var total_duplicates = 0;

    if (!setting.smiles || setting.smiles.trim().length === 0) {
      errors.push("Please provide at least one molecule in SMILES format.");
    } else if (errors.length === 0) {
      const mols = setting.smiles.split("\n");

      var i = 1;
      for (const mol of mols) {
        setValidatingState(
          "Checking SMILES of molecule " + i + "/" + mols.length + "...",
        );

        if (
          processed_smiles.includes(mol.trim()) ||
          validated_smiles.includes(mol.trim())
        ) {
          total_duplicates++;
          continue;
        }

        if (mol.trim().length === 0 || mol.trim().startsWith("#")) {
          continue;
        }

        await new Promise((resolve) => setTimeout(resolve, 1500));

        // Todo: Validate molecule + pridat validaci na slozeni, apod.
        const validation_response = await fetch(
          "/api/mol/smiles/canonize?smiles=" + mol,
        );

        if (validation_response.status === 503) {
          errors.push(
            "Cannot canonize smiles. Service is temporarily unavailable.",
          );
          break;
        }

        if (validation_response.status !== 200) {
          errors.push(
            "Cannot canonize smiles on line " +
              i +
              ". Please, check the smiles and try again.",
          );
          break;
        }

        const response = await validation_response.json();

        if (!response.canonized_smiles) {
          errors.push(
            "Cannot canonize smiles on line " +
              i +
              ". Please, check the smiles and try again.",
          );
          break;
        }

        validated_smiles.push(response.canonized_smiles);
        i++;
      }
    }

    if (
      !setting.temperature ||
      setting.temperature < 20 ||
      setting.temperature > 45
    ) {
      errors.push("Please provide a temperature between 20 and 45 °C.");
    }

    // Validate priority
    if (setting.priority === null) {
      errors.push("Please provide a priority for the calculation.");
    }

    if (errors.length === 0) {
      setValidatingState(
        "Everything looks good! You can submit the calculation." +
          (total_duplicates > 0
            ? " Total " +
              total_duplicates +
              " duplicate SMILES will be skipped."
            : ""),
      );
    }

    setIsValidated(errors.length === 0);
    setValidatorErrors(errors);
    setIsValidating(false);
  }

  return (
    <>
      <div className="w-full">
        <div className="flex flex-col min-w-lg bg-foreground-200 rounded-xl p-4 gap-2">
          <h2 className="font-bold text-lg">Add new calculation</h2>
          <Divider />
          <div>
            <h3 className="text-md text-secondary font-semibold">
              Select membrane
            </h3>
            <label className="text-sm block text-warning-600 text-right">
              Choose one of the available membranes for the simulation
            </label>
            <div className="border-1 border-foreground-300 p-4 bg-background rounded-xl">
              <GridSelection
                onSelectionChange={(selected) =>
                  setSetting((prev) => ({
                    ...prev,
                    membranes: selected?.length ? selected : null,
                  }))
                }
                multiple
                items={[
                  {
                    id: 1,
                    short_name: "DMPC",
                    long_name: "1,2-dimyristoyl-sn-glycero-3-phosphocholine",
                    description:
                      "DMPC is a phospholipid commonly found in biological membranes. It consists of a glycerol backbone linked to two myristic acid (14:0) chains and a phosphocholine headgroup. DMPC is often used in model membrane studies due to its well-defined phase behavior and ability to form bilayers.",
                    show_more_link: "/browse/membranes?id=35",
                  },
                  {
                    id: 3,
                    short_name: "DOPC",
                    long_name: "1,2-dioleoyl-sn-glycero-3-phosphocholine",
                    description:
                      "DOPC is a phospholipid that is a major component of cell membranes. It contains two oleic acid (18:1) chains attached to a glycerol backbone with a phosphocholine headgroup. DOPC is known for its fluidity at physiological temperatures and is frequently used in biophysical studies of lipid bilayers and membrane proteins.",
                    show_more_link: "/browse/membranes?id=35",
                  },
                  {
                    id: 2,
                    short_name: "POPC",
                    long_name:
                      "1-palmitoyl-2-oleoyl-sn-glycero-3-phosphocholine",
                    description:
                      "POPC is a phospholipid commonly found in eukaryotic cell membranes. It consists of a glycerol backbone linked to one palmitic acid (16:0) chain and one oleic acid (18:1) chain, along with a phosphocholine headgroup. POPC is widely used in membrane research due to its prevalence in biological systems and its ability to form stable bilayers.",
                    show_more_link: "/browse/membranes?id=35",
                  },
                ]}
              />
            </div>
          </div>
          <Divider />
          <div>
            <h3 className="text-md text-secondary font-semibold">
              Select method
            </h3>
            <label className="text-sm block text-warning-600 text-right">
              Choose one of the available methods for the simulation
            </label>
            <div className="border-1 border-foreground-300 p-4 bg-background rounded-xl">
              <GridSelection
                onSelectionChange={(selected) =>
                  setSetting((prev) => ({
                    ...prev,
                    methods: selected?.length ? selected : null,
                  }))
                }
                multiple
                items={[
                  {
                    id: 1,
                    short_name: "DMPC",
                    long_name: "1,2-dimyristoyl-sn-glycero-3-phosphocholine",
                    description:
                      "DMPC is a phospholipid commonly found in biological membranes. It consists of a glycerol backbone linked to two myristic acid (14:0) chains and a phosphocholine headgroup. DMPC is often used in model membrane studies due to its well-defined phase behavior and ability to form bilayers.",
                    show_more_link: "/browse/membranes?id=35",
                  },
                  {
                    id: 2,
                    short_name: "DOPC",
                    long_name: "1,2-dioleoyl-sn-glycero-3-phosphocholine",
                    description:
                      "DOPC is a phospholipid that is a major component of cell membranes. It contains two oleic acid (18:1) chains attached to a glycerol backbone with a phosphocholine headgroup. DOPC is known for its fluidity at physiological temperatures and is frequently used in biophysical studies of lipid bilayers and membrane proteins.",
                    show_more_link: "/browse/membranes?id=35",
                  },
                  {
                    id: 3,
                    short_name: "POPC",
                    long_name:
                      "1-palmitoyl-2-oleoyl-sn-glycero-3-phosphocholine",
                    description:
                      "POPC is a phospholipid commonly found in eukaryotic cell membranes. It consists of a glycerol backbone linked to one palmitic acid (16:0) chain and one oleic acid (18:1) chain, along with a phosphocholine headgroup. POPC is widely used in membrane research due to its prevalence in biological systems and its ability to form stable bilayers.",
                    show_more_link: "/browse/membranes?id=35",
                  },
                ]}
              />
            </div>
          </div>
          <Divider />
          <div>
            <h3 className="text-md text-secondary font-semibold">Priority</h3>
            <label className="text-sm block text-warning-600 text-right">
              Set the priority of your calculation among other of your pending
              calculations
            </label>
            <div className="border-1 border-foreground-300 p-4 bg-background rounded-xl">
              <GridSelection
                onSelectionChange={(selected) =>
                  setSetting((prev) => ({
                    ...prev,
                    priority: selected.length ? selected[0] : null,
                  }))
                }
                items={[
                  {
                    id: "low",
                    short_name: "Low",
                  },
                  {
                    id: "medium",
                    short_name: "Medium",
                  },
                  {
                    id: "high",
                    short_name: "High",
                  },
                ]}
              />
            </div>
          </div>
          <Divider />
          <div>
            <h3 className="text-md text-secondary font-semibold">
              Temperature [°C]
            </h3>
            <label className="text-sm block text-warning-600 text-right"></label>
            <div className="border-1 border-foreground-300 p-4 bg-white rounded-xl">
              <Input
                type="number"
                className=""
                value={setting.temperature.toString()}
                onChange={(value) => {
                  setSetting((prev) => ({
                    ...prev,
                    temperature: Number(value.target.value),
                  }));
                }}
                min={20}
                max={45}
              />
            </div>
          </div>
          <Divider />
          <div>
            <h3 className="text-md text-secondary font-semibold">
              Molecules (in SMILES format)
            </h3>
            <label className="text-sm block text-warning-600 text-right">
              Put one SMILES per line
            </label>
            <div className="border-1 border-foreground-300 p-4 bg-white rounded-xl">
              <Textarea
                type="text"
                className=""
                value={setting.smiles || ""}
                onChange={(e) =>
                  setSetting((prev) => ({ ...prev, smiles: e.target.value }))
                }
                required
                isClearable
                isMultiline
                minRows={5}
                maxRows={15}
                maxLength={5000}
              />
            </div>
          </div>
          <Divider />
          <div>
            <h3 className="text-md text-secondary font-semibold">
              Description (comment)
            </h3>
            <label className="text-sm block text-warning-600 text-right">
              Describe your calculation (only for your reference)
            </label>
            <div className="border-1 border-foreground-300 p-4 bg-white rounded-xl">
              <Textarea
                type="text"
                className=""
                value={setting.description || ""}
                onChange={(e) => {
                  setSetting((prev) => ({
                    ...prev,
                    description: e.target.value,
                  }));
                }}
                required
                isMultiline
                maxLength={512}
              />
            </div>
          </div>
          <div className="flex flex-row justify-end gap-4">
            <Button
              type="button"
              color={isValidated ? "success" : "warning"}
              startContent={!isValidating && <FaCheck />}
              size="lg"
              onPress={validate}
              isLoading={isValidating}
              isDisabled={isValidated}
            >
              Validate
            </Button>
            <Button
              type="submit"
              color="primary"
              startContent={!isSaving && <IoSend />}
              size="lg"
              isLoading={isSaving}
              isDisabled={!isValidated}
            >
              Submit
            </Button>
          </div>
        </div>
      </div>
      <Modal
        isOpen={isOpen}
        onOpenChange={onOpenChange}
        size="xl"
        hideCloseButton
      >
        <ModalContent>
          {(onClose) =>
            isValidating || validatorErrors.length === 0 ? (
              <>
                <div className="p-8 flex flex-col gap-4">
                  <h2 className="text-2xl font-bold">
                    {!isValidated ? "Validating" : "Validation finished"}
                  </h2>
                  <div>{validatingState}</div>
                  <div className="flex flex-row justify-end">
                    <Button
                      type="button"
                      color="primary"
                      onPress={() => {
                        onClose();
                      }}
                    >
                      Close
                    </Button>
                  </div>
                </div>
              </>
            ) : (
              <div className="p-8 flex flex-col gap-4">
                <h2 className="text-2xl font-bold">Validation errors</h2>
                <div className="flex flex-col gap-2">
                  {validatorErrors.map((error, index) => (
                    <Alert key={index} color="warning" title={error} />
                  ))}
                </div>
                <div className="flex flex-row justify-end">
                  <Button
                    type="button"
                    color="primary"
                    onPress={() => {
                      onClose();
                    }}
                  >
                    Close
                  </Button>
                </div>
              </div>
            )
          }
        </ModalContent>
      </Modal>
    </>
  );
}

function Divider() {
  return (
    <div className="h-1 w-full bg-gradient-to-r from-foreground-100/50 via-foreground-100 to-foreground-100/50" />
  );
}

interface GridSelectionItemProps {
  id: number | string;
  short_name: string;
  long_name?: string;
  description?: string;
  show_more_link?: string;
  onPress?: (id: number | string) => void;
}

function GridSelection(props: {
  items: GridSelectionItemProps[];
  multiple?: boolean;
  onSelectionChange?: (selectedItems: (number | string)[]) => void;
}) {
  const [selectedItems, setSelectedItems] = useState<(number | string)[]>([]);

  useEffect(() => {
    props.onSelectionChange?.(selectedItems);
  }, [selectedItems]);

  const Item = (props: GridSelectionItemProps) => (
    <Card
      isPressable
      className={`w-64 border-2 cursor-pointer select-none 
          ${selectedItems.includes(props.id) ? "border-primary" : "border-transparent hover:border-primary/50"}`}
      onPress={() => props.onPress?.(props.id)}
    >
      <CardHeader
        className={`flex flex-col gap-0 ${props.long_name || props.description ? "items-start" : "items-center"}`}
      >
        <h1>{props.short_name}</h1>
        {props.long_name && (
          <h2 className="text-sm text-foreground/50">{props.long_name}</h2>
        )}
      </CardHeader>
      {props.description && (
        <CardBody>
          <p className="text-clip line-clamp-3 text-sm">{props.description}</p>
        </CardBody>
      )}
      {props.show_more_link && (
        <CardFooter className="flex justify-end">
          <Link
            className="text-sm text-primary underline"
            href={props.show_more_link}
            target="_blank"
          >
            ...show more
          </Link>
        </CardFooter>
      )}
    </Card>
  );

  return (
    <div className="flex flex-row flex-wrap gap-4">
      {props.items.map((item, index) => (
        <Item
          key={index}
          {...item}
          onPress={(id) => {
            if (selectedItems.includes(id)) {
              setSelectedItems(selectedItems.filter((item) => item !== id));
            } else if (!props.multiple) {
              setSelectedItems([id]);
            } else {
              setSelectedItems([...selectedItems, id]);
            }
          }}
        />
      ))}
    </div>
  );
}
