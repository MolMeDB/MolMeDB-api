"use client";

import * as am5 from "@amcharts/amcharts5";
import * as am5hierarchy from "@amcharts/amcharts5/hierarchy";
import am5themes_Animated from "@amcharts/amcharts5/themes/Animated";
import am5themes_Responsive from "@amcharts/amcharts5/themes/Responsive";
import { Select, SelectItem, Spinner } from "@heroui/react";
import { useEffect, useMemo, useRef, useState } from "react";
import ICategory from "@/lib/api/admin/interfaces/Category";

type PieChartItem = {
  name: string;
  value?: number;
  model_id: number;
  children?: PieChartItem[];
  prots?: PieChartItem[];
  isFinal?: boolean;
};

function addValueToCategory(
  category: any,
  isFinal?: boolean
): PieChartItem | null {
  const children =
    [
      ...(category.children?.map((c: any) => addValueToCategory(c)) ?? []),
      ...(category.proteins?.map((p: any) => addValueToCategory(p, true)) ??
        []),
    ].filter((c: PieChartItem | null): c is PieChartItem => c !== null) ?? [];

  const proteins =
    category.proteins
      ?.map((p: any) => addValueToCategory(p, true))
      .filter((c: PieChartItem | null): c is PieChartItem => c !== null) ?? [];

  if (children.length === 0 && proteins.length === 0 && !isFinal) return null;

  return {
    name: category.title ?? category.name,
    model_id: category.id,
    isFinal: !!isFinal,
    value:
      !category.proteins?.length && !category.children?.length ? 1 : undefined,
    children: children,
    prots: proteins,
  };
}

export default function SectionPieChart(props: {
  categories: ICategory[];
  setSelectedProteinId: (id: string) => void;
}) {
  const viewerRef = useRef(null);
  const [isLoaded, setIsLoaded] = useState(false);
  const [levels, setLevels] = useState<string[]>(["0"]);
  const [isDarkMode, setIsDarkMode] = useState<boolean | null>(null);
  const [proteinId, setProteinId] = useState("");

  const categories = useMemo(
    () => [
      {
        name: "Proteins",
        model_id: 0,
        // value: 0,
        children: props.categories.map((c) => addValueToCategory(c)),
      },
    ],
    [props.categories]
  );

  useEffect(() => {
    props.setSelectedProteinId(proteinId);
  }, [proteinId]);

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

  useEffect(() => {
    if (isDarkMode === null) return;

    if (typeof window !== "undefined" && viewerRef.current && categories) {
      const root = am5.Root.new(viewerRef.current);
      root.setThemes([
        am5themes_Animated.new(root),
        am5themes_Responsive.new(root),
      ]);

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
          // initialDepth: 1,
          // topDepth: 1,
          // downDepth: 1,
          radius: am5.percent(98),
          // innerRadius: 0,
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
        fill: am5.color(0x000000),
      });

      series.slices.template.setAll({
        tooltipText: "{category}",
      });

      series.slices.template.events.on("click", (ev) => {
        const levels: string[] = [];
        let currentItem = ev.target.dataItem as any;

        while (currentItem) {
          const ctx = currentItem.dataContext as PieChartItem;
          if (ctx?.model_id != null && ctx.model_id !== 0) {
            levels.unshift(ctx.model_id.toString());
            if (ctx?.isFinal) {
              setProteinId(ctx.model_id.toString());
            }
          }
          currentItem = currentItem.get("parent") as any;
        }

        setLevels(levels);
        return false;
      });

      container.children.unshift(
        am5hierarchy.BreadcrumbBar.new(root, { series })
      );

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

      // const legend = container.children.push(
      //   am5.Legend.new(root, {
      //     centerX: am5.percent(50),
      //     x: am5.percent(50),
      //     layout: root.horizontalLayout,
      //   })
      // );
      // legend.data.setAll(series.dataItems[0].get("children"));

      series.appear(1000, 100);
      setIsLoaded(true);

      return () => {
        root.dispose();
      };
    }
  }, [isDarkMode]);

  const renderSelects = () => {
    const selects = [];
    let currentChildren = categories[0].children as PieChartItem[];

    for (let i = 0; ; i++) {
      const childrenForLevel = currentChildren;
      const selected = childrenForLevel.find(
        (c) => c.model_id.toString() === levels[i]
      );

      selects.push(
        <Select
          key={`level-${i}`}
          color="primary"
          variant="bordered"
          className="max-w-xs min-w-8 my-1"
          aria-label={`Select level ${i}`}
          placeholder="Select category"
          disallowEmptySelection
          selectedKeys={[levels[i]]}
          onSelectionChange={(e) => {
            const value = Array.from(e)[0].toString();
            const selectedOption = childrenForLevel.find(
              (c) => c.model_id.toString() === value
            );

            if (selectedOption?.isFinal) {
              setProteinId(value);
            } else {
              setProteinId("");
            }

            const newLevels = [...levels.slice(0, i), value];
            setLevels(newLevels);
          }}
        >
          {childrenForLevel.map((option) => (
            <SelectItem key={option.model_id} textValue={option.name}>
              {option.name}
            </SelectItem>
          ))}
        </Select>
      );

      if (!selected || !selected.children || selected.children.length === 0)
        break;

      currentChildren = selected.children;
    }

    return selects;
  };

  return (
    <>
      <div className="h-[650px] w-full hidden md:block dark:bg-gradient-to-b dark:from-[#4a4b64] dark:to-[#373749] p-4 rounded-3xl">
        <div
          ref={viewerRef}
          style={{ height: "100%", width: "100%" }}
          className="flex items-center justify-center"
        >
          {!isLoaded && <Spinner variant="wave" label="Loading..." />}
        </div>
      </div>
      <div className="flex flex-col md:flex-row justify-start flex-wrap items-center gap-1 md:gap-4">
        {renderSelects()}
      </div>
    </>
  );
}
