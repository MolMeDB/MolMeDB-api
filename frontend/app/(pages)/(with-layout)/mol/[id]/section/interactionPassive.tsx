"use client";
import DetailSection from "../components/section";
import FilterSelect from "../components/interactions/filterSelect";
import PassiveInteractionTable from "../components/tables/passiveInteractions";
import IStructure from "@/lib/api/admin/interfaces/Structure";
import { useCallback, useEffect, useState } from "react";
import { ISelectSetting } from "@/lib/api/admin/interfaces/SelectData";
import { getJson } from "@/lib/api/admin";
import { addToast, Button, cn, Spinner } from "@heroui/react";
import { IInteractionPassive } from "@/lib/api/admin/interfaces/Interaction";
import { MdClose, MdSearch } from "react-icons/md";

export default function CompoundPassiveInteractions(props: {
  compound: IStructure;
}) {
  const [membraneSelects, setMembraneSelects] = useState<
    ISelectSetting[] | null
  >(null);

  const [methodSelects, setMethodSelects] = useState<ISelectSetting[] | null>(
    null
  );

  const [selectedMembraneIds, setSelectedMembraneIds] = useState<Set<string>>(
    new Set()
  );
  const [selectedMethodIds, setSelectedMethodIds] = useState<Set<string>>(
    new Set()
  );

  const [membraneIdsToTable, setMembraneIdsToTable] = useState<string[]>([]);
  const [methodIdsToTable, setMethodIdsToTable] = useState<string[]>([]);

  const [loadingMethod, setLoadingMethod] = useState<boolean>(false);

  useEffect(() => {
    getJson(
      "/api/structure/" + props.compound.identifier + "/form/select/membranes"
    ).then((res) => {
      if (res?.code === 200 && res.data) {
        setMembraneSelects(res.data);
        return;
      }

      addToast({
        title: "Error",
        description: "Failed to load membranes data. Please, try again.",
        color: "danger",
        shouldShowTimeoutProgress: true,
        timeout: 4500,
      });
    });
  }, [props.compound.id]);

  useEffect(() => {
    setLoadingMethod(true);

    getJson(
      "/api/structure/" + props.compound.identifier + "/form/select/methods",
      {
        "membraneIds[]": Array.from(selectedMembraneIds),
      }
    ).then((res) => {
      if (res?.code === 200 && res.data) {
        setMethodSelects(res.data);
        setLoadingMethod(false);
        return;
      }

      addToast({
        title: "Error",
        description: "Failed to load methods data. Please, try again.",
        color: "danger",
        shouldShowTimeoutProgress: true,
        timeout: 4500,
      });
    });
  }, [selectedMembraneIds]);

  const findInteractions = () => {
    setMethodIdsToTable(Array.from(selectedMethodIds));
    setMembraneIdsToTable(Array.from(selectedMembraneIds));
  };

  return (
    <DetailSection
      title="Behavior on membranes (passive interactions)"
      order={5}
    >
      <>
        <div className="flex flex-col gap-3">
          <h4 className="text-lg font-bold">Filters</h4>
          <div className="flex flex-row sm:flex-col gap-3">
            <div className="flex flex-col gap-1 w-1/2 sm:w-full">
              <div className="flex flex-row justify-between items-end gap-4">
                <h5 className="font-semibold text-sm text-foreground/80">
                  Membranes
                </h5>
                <h6 className="hidden sm:block text-xs text-foreground/60">
                  Only membranes with available interactions are shown
                </h6>
              </div>
              {membraneSelects !== null ? (
                <div className="flex flex-row flex-wrap gap-2 items-center sm:pl-2">
                  {membraneSelects?.map((item, i) => (
                    <FilterSelect
                      key={`mem-${item.placeholder}`}
                      title={item.placeholder}
                      items={item.items}
                      selectedItems={selectedMembraneIds}
                      onChange={(allIds, selectedIds) => {
                        let t = selectedMembraneIds;
                        t = t.difference(allIds);
                        setSelectedMembraneIds(t.union(selectedIds));
                        if (t.size === 0) {
                          setSelectedMethodIds(new Set());
                          setMethodSelects(null);
                        }
                      }}
                    />
                  ))}
                </div>
              ) : (
                <Spinner variant="wave" />
              )}
            </div>
            <div className="flex flex-col gap-1 w-1/2 sm:w-full">
              <h5 className="font-semibold text-sm text-foreground/80">
                Methods
              </h5>
              {loadingMethod ? (
                <Spinner
                  variant="wave"
                  className={cn({ hidden: !loadingMethod })}
                />
              ) : null}
              <div
                className={cn(
                  "flex flex-row flex-wrap gap-2 items-center pl-2",
                  {
                    hidden: loadingMethod,
                  }
                )}
              >
                {methodSelects?.map((item, i) => (
                  <FilterSelect
                    key={`met-${item.placeholder}`}
                    title={item.placeholder}
                    items={item.items}
                    selectedItems={selectedMethodIds}
                    onChange={(allIds, selectedIds) => {
                      let t = selectedMethodIds;
                      t = t.difference(allIds);
                      setSelectedMethodIds(t.union(selectedIds));
                    }}
                  />
                ))}
              </div>
            </div>
          </div>

          <div className="mt-2 ml-2 flex flex-row gap-2">
            <Button
              color="primary"
              variant="flat"
              onPress={findInteractions}
              endContent={<MdSearch className="animate-pulse" size={20} />}
            >
              Submit
            </Button>
            {selectedMembraneIds.size > 0 || selectedMethodIds.size > 0 ? (
              <Button
                color="danger"
                variant="flat"
                onPress={() => {
                  setSelectedMembraneIds(new Set());
                  setSelectedMethodIds(new Set());
                  setMethodIdsToTable([]);
                  setMembraneIdsToTable([]);
                }}
                endContent={<MdClose size={20} />}
              >
                Clear
              </Button>
            ) : null}
          </div>
        </div>
        <div className="h-0.5 w-full bg-gradient-to-r from-zinc-400 to-transparent" />
        <div className="mt-4">
          <PassiveInteractionTable
            structure={props.compound}
            membraneIds={Array.from(membraneIdsToTable)}
            methodIds={Array.from(methodIdsToTable)}
          />
        </div>
      </>
    </DetailSection>
  );
}
