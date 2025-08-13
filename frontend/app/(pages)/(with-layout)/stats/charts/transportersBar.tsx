"use client";

import React, { useEffect } from "react";

import * as am5 from "@amcharts/amcharts5";
import * as am5xy from "@amcharts/amcharts5/xy";
import am5themes_Animated from "@amcharts/amcharts5/themes/Animated";
import am5themes_Dark from "@amcharts/amcharts5/themes/Dark";
import { IBarChartSetting } from "@/lib/api/admin/interfaces/Stats";
import am5themes_Responsive from "@amcharts/amcharts5/themes/Responsive";

export default function TransportersBar(props: { data: IBarChartSetting }) {
  useEffect(() => {
    let root = am5.Root.new("transporterBarRoot");

    root.setThemes([
      am5themes_Animated.new(root),
      am5themes_Dark.new(root),
      am5themes_Responsive.new(root),
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

    let myColorSet = am5.ColorSet.new(root, {
      colors: [
        am5.color(0xff9671),
        am5.color(0xc62e65),
        am5.color(0xffc75f),
        am5.color(0xf9f871),
        am5.color(0xd63af9),
      ],
      reuse: true,
    });

    chart.set("colors", myColorSet);

    // We don't want zoom-out button to appear while animating, so we hide it
    chart.zoomOutButton.set("forceHidden", true);

    // Define data

    let yRenderer = am5xy.AxisRendererY.new(root, {
      minGridDistance: 30,
      minorGridEnabled: true,
    });

    yRenderer.grid.template.setAll({
      location: 1,
      stroke: am5.color(0xffffff),
      strokeWidth: 2,
    });

    // Create Y-axis
    let yAxis = chart.yAxes.push(
      am5xy.CategoryAxis.new(root, {
        maxDeviation: 0,
        categoryField: "name",
        renderer: yRenderer,
        tooltip: am5.Tooltip.new(root, { themeTags: ["axis"] }),
      })
    );

    yRenderer.labels.template.setAll({
      fill: am5.color(0xffffff),
      fontSize: "1.2em",
      fontWeight: "bold",
      paddingRight: 20,
    });

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

    let xRend = xAxis.get("renderer");
    xRend.labels.template.setAll({
      fill: am5.color(0xffffff),
      fontSize: "1.2em",
    });

    xRend.grid.template.setAll({
      stroke: am5.color(0xffffff),
      strokeWidth: 2,
    });

    // xAxis.data.setAll(data);

    let series = chart.series.push(
      am5xy.ColumnSeries.new(root, {
        name: "name",
        xAxis: xAxis,
        yAxis: yAxis,
        valueXField: "value1",
        categoryYField: "name",
        tooltip: am5.Tooltip.new(root, {
          pointerOrientation: "left",
          labelText: "{valueX} {categoryY}",
        }),
      })
    );

    let series2 = chart.series.push(
      am5xy.ColumnSeries.new(root, {
        name: "name",
        xAxis: xAxis,
        yAxis: yAxis,
        valueXField: "value2",
        categoryYField: "name",
        tooltip: am5.Tooltip.new(root, {
          pointerOrientation: "left",
          labelText: "{valueX} interactions",
        }),
      })
    );

    series.columns.template.setAll({
      cornerRadiusTR: 5,
      cornerRadiusBR: 5,
      strokeOpacity: 0,
    });

    series2.columns.template.setAll({
      cornerRadiusTR: 5,
      cornerRadiusBR: 5,
      strokeOpacity: 0,
    });

    series.columns.template.adapters.add("fill", (fill, target) => {
      return chart.get("colors")!.getIndex(series.columns.indexOf(target) + 1);
    });

    series.columns.template.adapters.add("stroke", function (stroke, target) {
      return chart.get("colors")!.getIndex(series.columns.indexOf(target) + 1);
    });

    series2.columns.template.adapters.add("fill", (fill, target) => {
      return chart.get("colors")!.getIndex(0);
    });

    series2.columns.template.adapters.add("stroke", function (stroke, target) {
      return chart.get("colors")!.getIndex(0);
    });

    yAxis.data.setAll(props.data.items);
    series.data.setAll(props.data.items);
    series2.data.setAll(props.data.items);

    // Add cursor
    chart.set(
      "cursor",
      am5xy.XYCursor.new(root, {
        behavior: "none",
        xAxis: xAxis,
        yAxis: yAxis,
      })
    );

    let cursor = chart.get("cursor")!;

    cursor.lineY.setAll({
      stroke: am5.color(0xffffff),
      strokeWidth: 2,
      strokeDasharray: [],
    });

    cursor.lineX.setAll({
      visible: false,
    });

    series.appear(1000);
    series2.appear(1000);
    chart.appear(1000, 100);

    return () => {
      root.dispose();
    };
  }, []);

  return (
    <div>
      <div
        id="transporterBarRoot"
        style={{ width: "100%", height: "400px" }}
      ></div>
    </div>
  );
}
