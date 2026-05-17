"use client";

import * as am5 from "@amcharts/amcharts5";
import * as am5hierarchy from "@amcharts/amcharts5/hierarchy";
import am5themes_Animated from "@amcharts/amcharts5/themes/Animated";
import { Select, SelectItem, Spinner } from "@heroui/react";
import { useEffect, useLayoutEffect, useMemo, useRef, useState } from "react";
import ICategory from "@/lib/api/admin/interfaces/Category";

type PieChartItem = {
  name: string;
  value?: number;
  model_id: number;
  children?: PieChartItem[];
  mems?: PieChartItem[];
  isFinal?: boolean;
};

function addValueToCategory(category: any, isFinal?: boolean): PieChartItem {
  const t = {
    name: category.title ?? category.name,
    model_id: category.id,
    isFinal: !!isFinal,
    value:
      !category.methods?.length && !category.children?.length ? 1 : undefined,
    children:
      category.children?.length > 0
        ? category.children?.map((c: any) => addValueToCategory(c))
        : category.methods?.map((m: any) => addValueToCategory(m, true)),
    mems: category.methods?.map((m: any) => addValueToCategory(m, true)),
  };

  return t;
}

function findPathToMethod(
  items: PieChartItem[],
  methodId: string,
  path: string[] = []
): string[] | null {
  for (const item of items) {
    const nextPath = [...path, item.model_id.toString()];

    if (item.isFinal && item.model_id.toString() === methodId) {
      return nextPath;
    }

    const childPath = findPathToMethod(
      item.children ?? [],
      methodId,
      nextPath
    );

    if (childPath) {
      return childPath;
    }
  }

  return null;
}

export default function SectionPieChart(props: {
  categories: ICategory[];
  selectedMethodId: string;
  setSelectedMethodId: (id: string) => void;
}) {
  const viewerRef = useRef(null);
  const [isLoaded, setIsLoaded] = useState(false);
  const [isDarkMode, setIsDarkMode] = useState<boolean | null>(null);
  const [levels, setLevels] = useState<string[]>([]);
  const [methodId, setMethodId] = useState("");

  const categories: PieChartItem[] = useMemo(
    () => [
      {
        name: "Methods",
        model_id: 0,
        children: props.categories.map((c) => addValueToCategory(c)),
      },
    ],
    [props.categories]
  );

  useEffect(() => {
    props.setSelectedMethodId(methodId);
  }, [methodId]);

  useEffect(() => {
    if (!props.selectedMethodId) {
      return;
    }

    const path = findPathToMethod(
      categories[0].children as PieChartItem[],
      props.selectedMethodId
    );

    if (!path) {
      return;
    }

    setLevels(path);
    setMethodId(props.selectedMethodId);
  }, [categories, props.selectedMethodId]);

  useEffect(() => {
    const darkModeMedia = window.matchMedia("(prefers-color-scheme: dark)");
    setIsDarkMode(darkModeMedia.matches);

    // poslouchání změny
    const handler = (e: any) => {
      setIsDarkMode(e.matches);
    };
    darkModeMedia.addEventListener("change", handler);

    return () => {
      darkModeMedia.removeEventListener("change", handler);
    };
  }, []);

  useLayoutEffect(() => {
    if (isDarkMode === null) return;

    if (typeof window !== "undefined" && viewerRef.current && categories) {
      var root = am5.Root.new(viewerRef.current);

      root.setThemes([am5themes_Animated.new(root)]);

      const container = root.container.children.push(
        am5.Container.new(root, {
          width: am5.percent(100),
          height: am5.percent(100),
          layout: root.verticalLayout,
        })
      );

      const series = container.children.push(
        am5hierarchy.Sunburst.new(root, {
          singleBranchOnly: true,
          downDepth: 10,
          initialDepth: 10,
          topDepth: 1,
          radius: am5.percent(98),
          innerRadius: 0,
          startAngle: -200,
          endAngle: 20,
          valueField: "value",
          categoryField: "name",
          childDataField: "children",
          legendLabelText: "{category}",
          legendValueText: "",
          tooltip: am5.Tooltip.new(root, {
            labelText: "{category}",
          }),
        })
      );

      series.labels.template.setAll({
        fontSize: 11,
        oversizedBehavior: "truncate",
        fill: am5.color(0x000000),
      });

      series.slices.template.setAll({
        tooltipText: "{category}",
      });

      series.slices.template.events.on("click", (ev) => {
        const levels: string[] = [];
        let selectedMethodId = "";
        let currentItem = ev.target.dataItem as any;

        while (currentItem) {
          const ctx = currentItem.dataContext as PieChartItem;
          if (ctx?.model_id != null && ctx.model_id !== 0) {
            levels.unshift(ctx.model_id.toString());
            if (ctx?.isFinal) {
              selectedMethodId = ctx.model_id.toString();
            }
          }
          currentItem = currentItem.get("parent") as any;
        }

        setLevels(levels);
        setMethodId(selectedMethodId);
        return false;
      });

      // Breadcrumbs
      container.children.unshift(
        am5hierarchy.BreadcrumbBar.new(root, {
          series: series,
        })
      );

      // Set data
      series.data.setAll(categories);

      // Legend
      let legend = container.children.push(
        am5.Legend.new(root, {
          centerX: am5.percent(50),
          x: am5.percent(50),
          layout: root.horizontalLayout,
        })
      );

      let textColor = isDarkMode ? 0xffffff : 0x000000;

      legend.labels.template.setAll({
        fill: am5.color(textColor),
      });

      legend.valueLabels.template.setAll({
        fill: am5.color(textColor),
      });

      legend.data.setAll(series.dataItems[0].get("children"));

      series.appear(1000, 100);

      // series.seriesTemplates.setAll({
      //   labels: {
      //     forceHidden: false,
      //     oversizedBehavior: "truncate",
      //   },
      // });

      // series.seriesContainer.children.each((serie) => {
      //   serie.labels?.template.setAll({
      //     oversizedBehavior: "truncate",
      //     fill: am5.color(0x000000),
      //   });
      // });

      // series.seriesContainer.children.each((serie) => {
      //   serie.slices?.template.events.on("click", (ev) => {
      //     const rawData = ev.target.dataItem?.dataContext;
      //     if (rawData?.last && rawData?.id_element) {
      //       const target = document.querySelector(`#target_${rawData.id_element}`);
      //       if (target) {
      //         (target as HTMLElement).click();
      //       } else {
      //         console.log("Target not found.");
      //       }
      //     }
      //   });
      // });
    }

    setIsLoaded(true);

    return () => {
      if (root) root.dispose();
    };
  }, [isDarkMode]);

  const renderSelects = () => {
    const selects = [];
    let currentChildren = categories[0].children as PieChartItem[];

    for (let i = 0; ; i++) {
      const childrenForLevel = currentChildren;

      if (childrenForLevel.length === 0) {
        break;
      }

      const selected = childrenForLevel.find(
        (c) => c?.model_id.toString() === levels[i]
      );

      selects.push(
        <Select
          key={`level-${i}`}
          color="primary"
          variant="bordered"
          className="max-w-xs"
          aria-label={`Method category selector level ${i}`}
          placeholder={i === 0 ? "Select category" : "Select method"}
          disallowEmptySelection
          selectedKeys={selected ? [levels[i]] : []}
          onSelectionChange={(e) => {
            const value = Array.from(e)[0]?.toString();

            if (!value) {
              return;
            }

            const selectedOption = childrenForLevel.find(
              (c) => c?.model_id.toString() === value
            );

            if (selectedOption?.isFinal) {
              setMethodId(value);
            } else {
              setMethodId("");
            }

            setLevels([...levels.slice(0, i), value]);
          }}
        >
          {childrenForLevel.map((option) => (
            <SelectItem textValue={option.name} key={option.model_id}>
              {option.name}
            </SelectItem>
          ))}
        </Select>
      );

      if (!selected || !selected.children || selected.children.length === 0) {
        break;
      }

      currentChildren = selected.children;
    }

    return selects;
  };

  return (
    <>
      <div className="h-[550px] w-full hidden md:block dark:bg-gradient-to-b dark:from-[#4a4b64] dark:to-[#373749] p-4 rounded-3xl">
        <div
          ref={viewerRef}
          style={{ height: "100%", width: "100%" }}
          className="flex items-center justify-center"
        >
          {!isLoaded && <Spinner variant="wave" label="Loading..." />}
        </div>
      </div>
      <div className="flex flex-col md:flex-row justify-start items-center gap-1 md:gap-4">
        {renderSelects()}
      </div>
    </>
  );
}
