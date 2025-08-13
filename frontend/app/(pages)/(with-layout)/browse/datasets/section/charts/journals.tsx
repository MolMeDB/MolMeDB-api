"use client";

import { IBarChartSetting } from "@/lib/api/admin/interfaces/Stats";
import * as am5 from "@amcharts/amcharts5";
import * as am5xy from "@amcharts/amcharts5/xy";
import am5themes_Animated from "@amcharts/amcharts5/themes/Animated";
import { useEffect } from "react";

export default function SectionChartsJournals(props: {
  data: IBarChartSetting;
}) {
  useEffect(() => {
    let root = am5.Root.new("chartdiv");

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

    let yRenderer = am5xy.AxisRendererY.new(root, {
      minGridDistance: 1,
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
          minGridDistance: 100,
        }),
      })
    );

    // xAxis.data.setAll(data);

    let series = chart.series.push(
      am5xy.ColumnSeries.new(root, {
        name: "Journal",
        xAxis: xAxis,
        yAxis: yAxis,
        valueXField: "value1",
        categoryYField: "name",
        tooltip: am5.Tooltip.new(root, {
          pointerOrientation: "left",
          labelText: "{valueX} interactions",
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
  }, []);

  return (
    <div className="w-full h-auto hidden sm:flex flex-col justify-center gap-8 items-center pt-16">
      <h1 className="text-3xl font-bold text-secondary text-center">
        Number of interactions by sources
      </h1>
      <div className="xl:pr-16">
        <div
          id="chartdiv"
          className="w-[550px] md:w-[650px] lg:w-[950px] h-[650px]"
        ></div>
      </div>
    </div>
  );
}
