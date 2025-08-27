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
};

function addValueToCategory(category: any): PieChartItem {
  const t = {
    name: category.title ?? category.name,
    model_id: category.id,
    value:
      !category.methods?.length && !category.children?.length ? 1 : undefined,
    children:
      category.children?.length > 0
        ? category.children?.map(addValueToCategory)
        : category.methods?.map(addValueToCategory),
    mems: category.methods?.map(addValueToCategory),
  };

  return t;
}

export default function SectionPieChart(props: {
  categories: ICategory[];
  setSelectedMethodId: (id: string) => void;
}) {
  const viewerRef = useRef(null);
  const [isLoaded, setIsLoaded] = useState(false);
  const [isDarkMode, setIsDarkMode] = useState<boolean | null>(null);
  const [level1, setLevel1] = useState<string>("");
  const [level2, setLevel2] = useState<string>("");
  const [level3, setLevel3] = useState<string>("");

  const categories: PieChartItem[] = useMemo(
    () => [
      {
        name: "Methods",
        model_id: 0,
        children: props.categories.map(addValueToCategory),
      },
    ],
    [props.categories]
  );

  useEffect(() => {
    props.setSelectedMethodId(level3);
  }, [level3]);

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
        const item = ev.target.dataItem as any;
        const inputData = item?.dataContext as PieChartItem;
        const model_id = inputData.model_id;

        if (!model_id) return;
        else {
          // Check, if has parent
          const parentItem = item.get("parent") as any;
          const parent = parentItem?.dataContext as PieChartItem;
          const parent_id = parent?.model_id;

          if (parent_id) {
            const gparentItem = parentItem?.get("parent") as any;
            const gparent = gparentItem?.dataContext as PieChartItem;
            const gparent_id = gparent?.model_id;

            if (gparent_id) {
              setLevel1(gparent_id.toString());
              setLevel2(parent_id.toString());
              setLevel3(model_id.toString());
            } else {
              setLevel1(parent_id.toString());
              setLevel2(model_id.toString());
              setLevel3("");
            }
          } else {
            setLevel1(model_id.toString());
            setLevel2("");
            setLevel3("");
          }

          return false;
        }
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
      root.dispose();
    };
  }, [isDarkMode]);

  const options = categories[0].children as PieChartItem[];

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
        <Select
          color="primary"
          variant="bordered"
          className="max-w-xs"
          aria-label="Method category selector"
          placeholder="Select category"
          disallowEmptySelection
          selectedKeys={[level1]}
          onSelectionChange={(e) => {
            setLevel3("");
            setLevel2("");
            setLevel1(Array.from(e)[0].toString());
          }}
        >
          {options.map((option) => {
            return (
              <SelectItem textValue={option.name} key={option.model_id}>
                {option.name}
              </SelectItem>
            );
          })}
        </Select>
        <label className="text-zinc-400 text-xl rotate-90 md:rotate-0">
          {">"}
        </label>
        <Select
          color="primary"
          isDisabled={level1 === ""}
          variant="bordered"
          className="max-w-xs"
          placeholder={level1 === "" ? "" : "Select subcategory"}
          aria-label="Method category selector"
          selectedKeys={[level2]}
          disallowEmptySelection
          onSelectionChange={(e) => {
            setLevel3("");
            setLevel2(Array.from(e)[0].toString());
          }}
        >
          {level1 !== ""
            ? options
                .find((option) => option.model_id?.toString() == level1)
                ?.children?.map((option) => {
                  return (
                    <SelectItem textValue={option.name} key={option.model_id}>
                      {option.name}
                    </SelectItem>
                  );
                }) ?? null
            : null}
        </Select>
        <label className="text-zinc-400 text-xl rotate-90 md:rotate-0">
          {">"}
        </label>
        <Select
          color="primary"
          isDisabled={level2 === ""}
          variant="bordered"
          disallowEmptySelection
          className="max-w-xs"
          placeholder={level2 === "" ? "" : "Select method"}
          aria-label="Method category selector"
          selectedKeys={[level3]}
          onSelectionChange={(e) => {
            setLevel3(Array.from(e)[0].toString());
          }}
        >
          {level2 !== ""
            ? options
                .find((option) => option.model_id?.toString() == level1)
                ?.children?.find(
                  (option) => option.model_id?.toString() == level2
                )
                ?.children?.map((option) => {
                  return (
                    <SelectItem textValue={option.name} key={option.model_id}>
                      {option.name}
                    </SelectItem>
                  );
                }) ?? null
            : null}
        </Select>
      </div>
    </>
  );
}
