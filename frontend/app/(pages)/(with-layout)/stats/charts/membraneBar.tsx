"use client";

import React, { useEffect, useLayoutEffect, useState } from "react";
import { isServerSide } from "@/components/helpers/system";
// import ReactApexChart from "react-apexcharts";
import dynamic from "next/dynamic";

import * as am5 from "@amcharts/amcharts5";
import * as am5xy from "@amcharts/amcharts5/xy";
import am5themes_Animated from "@amcharts/amcharts5/themes/Animated";
import { Varela_Round } from "next/font/google";

export default function MembraneBar({ idKey }: { idKey: string }) {
  useEffect(() => {
    let root = am5.Root.new(idKey);

    root.setThemes([am5themes_Animated.new(root)]);

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
    let data = [
      {
        name: "ALOGPS",
        substances: 441257,
        interactions: 441257,
      },
      {
        name: "MDCG",
        substances: 434257,
        interactions: 434257,
      },
      {
        name: "XLOGP",
        substances: 45951,
        interactions: 45951,
      },
      {
        name: "CCM18",
        substances: 11017,
        interactions: 23923,
      },
      {
        name: "EMAP",
        substances: 6181,
        interactions: 11751,
      },
      {
        name: "EMAP",
        substances: 6181,
        interactions: 11751,
      },
      {
        name: "EMAP",
        substances: 6181,
        interactions: 11751,
      },
      {
        name: "EMAP",
        substances: 6181,
        interactions: 11751,
      },
      {
        name: "EMAP",
        substances: 6181,
        interactions: 11751,
      },
      {
        name: "EMAP",
        substances: 6181,
        interactions: 11751,
      },
      {
        name: "EMAP",
        substances: 6181,
        interactions: 11751,
      },
      {
        name: "EMAP",
        substances: 6181,
        interactions: 11751,
      },
      {
        name: "EMAP",
        substances: 6181,
        interactions: 11751,
      },
    ];

    let yRenderer = am5xy.AxisRendererY.new(root, {
      minGridDistance: 15,
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

    // yRenderer.labels.template.setAll({
    //   fill: am5.color(0xffffff),
    //   fontSize: "1em",
    // });

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

    // let xRend = xAxis.get("renderer");
    // xRend.labels.template.setAll({
    //   fill: am5.color(0xffffff),
    //   fontSize: "1em",
    // });

    // xAxis.data.setAll(data);

    let series2 = chart.series.push(
      am5xy.ColumnSeries.new(root, {
        name: "Interactions",
        xAxis: xAxis,
        yAxis: yAxis,
        valueXField: "interactions",
        categoryYField: "name",
        tooltip: am5.Tooltip.new(root, {
          pointerOrientation: "left",
          labelText: "{valueX} interactions",
        }),
      })
    );

    let series = chart.series.push(
      am5xy.LineSeries.new(root, {
        name: "Substances",
        xAxis: xAxis,
        yAxis: yAxis,
        valueXField: "substances",
        categoryYField: "name",
        tooltip: am5.Tooltip.new(root, {
          pointerOrientation: "left",
          labelText: "{valueX} substances",
        }),
      })
    );

    series.strokes.template.setAll({
      strokeWidth: 3,
      templateField: "strokeSettings",
    });

    series.bullets.push(function () {
      return am5.Bullet.new(root, {
        sprite: am5.Circle.new(root, {
          strokeWidth: 3,
          stroke: series.get("stroke"),
          radius: 5,
          fill: root.interfaceColors.get("background"),
        }),
      });
    });

    series2.columns.template.setAll({
      cornerRadiusTR: 5,
      cornerRadiusBR: 5,
      strokeOpacity: 0,
    });

    // series.columns.template.adapters.add("fill", (fill, target) => {
    //   return chart.get("colors")!.getIndex(series.columns.indexOf(target));
    // });

    // series.columns.template.adapters.add("stroke", function (stroke, target) {
    //   return chart.get("colors")!.getIndex(series.columns.indexOf(target));
    // });

    series2.columns.template.adapters.add("fill", (fill, target) => {
      return chart.get("colors")!.getIndex(series2.columns.indexOf(target) + 6);
    });

    series2.columns.template.adapters.add("stroke", function (stroke, target) {
      return chart.get("colors")!.getIndex(series2.columns.indexOf(target) + 6);
    });

    yAxis.data.setAll(data);
    series2.data.setAll(data);
    series.data.setAll(data);

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
    series2.appear(1000);
    chart.appear(1000, 100);

    return () => {
      root.dispose();
    };
  }, [idKey]);

  return (
    <div>
      <div id={idKey} style={{ width: "100%", height: "600px" }}></div>
    </div>
  );
}
