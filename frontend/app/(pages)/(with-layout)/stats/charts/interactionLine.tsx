"use client";

import React, { useEffect, useState } from "react";

import * as am5 from "@amcharts/amcharts5";
import * as am5xy from "@amcharts/amcharts5/xy";
import am5themes_Animated from "@amcharts/amcharts5/themes/Animated";
import am5themes_Responsive from "@amcharts/amcharts5/themes/Responsive";
import am5themes_Dark from "@amcharts/amcharts5/themes/Dark";
import { ILineChartSetting } from "@/lib/api/admin/interfaces/Stats";

export default function InteractionLine(props: { data: ILineChartSetting }) {
  const [isDarkMode, setIsDarkMode] = useState<boolean | null>(null);

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

    if (props.data.data.length > 0) {
      var root = am5.Root.new("interactionLineRoot");

      const myTheme = am5.Theme.new(root);

      myTheme.rule("AxisLabel", ["minor"]).setAll({
        dy: 1,
      });

      // Set themes
      // https://www.amcharts.com/docs/v5/concepts/themes/
      root.setThemes([
        am5themes_Animated.new(root),
        myTheme,
        am5themes_Responsive.new(root),
        ...(isDarkMode ? [am5themes_Dark.new(root)] : []),
      ]);

      var chart = root.container.children.push(
        am5xy.XYChart.new(root, {
          panX: false,
          panY: false,
          wheelX: "none",
          wheelY: "none",
          paddingLeft: 0,
        })
      );

      let cursor = chart.set(
        "cursor",
        am5xy.XYCursor.new(root, {
          behavior: "zoomX",
        })
      );
      cursor.lineY.set("visible", false);

      let xAxis = chart.xAxes.push(
        am5xy.GaplessDateAxis.new(root, {
          maxDeviation: 0,
          groupData: false,
          baseInterval: {
            timeUnit: "month",
            count: 1,
          },
          renderer: am5xy.AxisRendererX.new(root, {
            minorGridEnabled: true,
            minorLabelsEnabled: true,
          }),
          tooltip: am5.Tooltip.new(root, {}),
        })
      );

      xAxis.set("minorDateFormats", {
        month: "MM",
      });

      xAxis.setAll({
        groupData: false,
        markUnitChange: false,
      });

      let yAxis = chart.yAxes.push(
        am5xy.ValueAxis.new(root, {
          renderer: am5xy.AxisRendererY.new(root, {}),
        })
      );

      let seriesSubstances = chart.series.push(
        am5xy.ColumnSeries.new(root, {
          name: "Substances per month",
          xAxis: xAxis,
          yAxis: yAxis,
          valueYField: "value1",
          valueXField: "date",
          clustered: false,
          tooltip: am5.Tooltip.new(root, {
            labelText: "{valueY} substances",
          }),
        })
      );

      const color = chart.get("colors")!.getIndex(7);

      let seriesInteractions = chart.series.push(
        am5xy.LineSeries.new(root, {
          name: "Interactions per month",
          xAxis: xAxis,
          yAxis: yAxis,
          valueYField: "value2",
          valueXField: "date",
          fill: color,
          stroke: color,
          // clustered: false,
          tooltip: am5.Tooltip.new(root, {
            labelText: "{valueY} interactions",
          }),
        })
      );

      seriesInteractions.bullets?.push(() =>
        am5.Bullet.new(root, {
          sprite: am5.Circle.new(root, {
            radius: 5,
            fill: color,
            stroke: color,
          }),
        })
      );

      seriesSubstances.columns.template.setAll({
        strokeOpacity: 0,
        width: am5.percent(50),
        tooltipY: 0,
      });

      // yAxis.data.setAll(data);
      seriesSubstances.data.setAll(props.data.data);
      seriesInteractions.data.setAll(props.data.data);

      seriesSubstances.appear(1000);
      seriesInteractions.appear(1000);
      chart.appear(1000, 100);
    }

    return () => {
      root.dispose();
    };
  }, [isDarkMode]);

  return (
    <div>
      <div
        id="interactionLineRoot"
        className="w-[350px] sm:w-[550px] md:w-[600px] lg:w-[800px] xl:w-[700px] h-[300px] sm:h-[400px]"
      ></div>
    </div>
  );
}
