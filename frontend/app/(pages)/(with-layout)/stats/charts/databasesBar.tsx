"use client";

import React, { useEffect, useLayoutEffect, useState } from "react";

import * as am5 from "@amcharts/amcharts5";
import * as am5xy from "@amcharts/amcharts5/xy";
import am5themes_Animated from "@amcharts/amcharts5/themes/Animated";
import am5themes_Dark from "@amcharts/amcharts5/themes/Dark";
import { IBarChartSetting } from "@/lib/api/admin/interfaces/Stats";

export default function DatabaseBar(props: { data: IBarChartSetting }) {
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

    let root = am5.Root.new("chartdiv");

    root.setThemes([
      am5themes_Animated.new(root),
      ...(isDarkMode ? [am5themes_Dark.new(root)] : []),
    ]);

    var chart = root.container.children.push(
      am5xy.XYChart.new(root, {
        panX: false,
        panY: false,
        wheelX: "none",
        wheelY: "none",
        paddingLeft: 20,
      })
    );

    // We don't want zoom-out button to appear while animating, so we hide it
    chart.zoomOutButton.set("forceHidden", true);

    // Define data

    let yRenderer = am5xy.AxisRendererY.new(root, {
      minGridDistance: 30,
      minorGridEnabled: true,
    });

    yRenderer.grid.template.set("location", 1);

    // Create Y-axis
    let yAxis = chart.yAxes.push(
      am5xy.CategoryAxis.new(root, {
        maxDeviation: 0,
        categoryField: "name",
        renderer: yRenderer,
        tooltip: am5.Tooltip.new(root, { themeTags: ["axis"] }),
      })
    );

    let xAxis = chart.xAxes.push(
      am5xy.ValueAxis.new(root, {
        maxDeviation: 0,
        min: 0,
        numberFormatter: am5.NumberFormatter.new(root, {
          numberFormat: "#,###a",
        }),
        extraMax: 0.1,
        renderer: am5xy.AxisRendererX.new(root, {
          strokeOpacity: 0.1,
          minGridDistance: 80,
        }),
      })
    );

    // xAxis.data.setAll(data);

    let series = chart.series.push(
      am5xy.ColumnSeries.new(root, {
        name: "Database",
        xAxis: xAxis,
        yAxis: yAxis,
        valueXField: "value1",
        categoryYField: "name",
        tooltip: am5.Tooltip.new(root, {
          pointerOrientation: "left",
          labelText: "{valueX} substances",
        }),
      })
    );

    // series.data.setAll(data);

    series.columns.template.setAll({
      cornerRadiusTR: 5,
      cornerRadiusBR: 5,
      strokeOpacity: 0,
    });

    series.columns.template.adapters.add("fill", (fill, target) => {
      return chart.get("colors")!.getIndex(series.columns.indexOf(target));
    });

    series.columns.template.adapters.add("stroke", function (stroke, target) {
      return chart.get("colors")!.getIndex(series.columns.indexOf(target));
    });

    yAxis.data.setAll(props.data.items);
    series.data.setAll(props.data.items);

    // Add cursor
    chart.set(
      "cursor",
      am5xy.XYCursor.new(root, {
        behavior: "none",
        xAxis: xAxis,
        yAxis: yAxis,
      })
    );

    series.appear(1000);
    chart.appear(1000, 100);

    return () => {
      root.dispose();
    };
  }, [isDarkMode]);

  return (
    <div>
      <div
        id="chartdiv"
        className="w-[350px] sm:w-[550px] md:w-[600px] h-[300px] sm:h-[400px]"
      />
    </div>
  );
}
