"use client";
import * as am5 from "@amcharts/amcharts5";
import * as am5xy from "@amcharts/amcharts5/xy";
import am5themes_Animated from "@amcharts/amcharts5/themes/Animated";
import am5themes_Responsive from "@amcharts/amcharts5/themes/Responsive";
import { useEffect } from "react";
import { ILineChartSetting } from "@/lib/api/admin/interfaces/Stats";

export default function SectionChartsHistory(props: {
  data: ILineChartSetting;
  totalPublications: number;
  minPublishedYear: number;
}) {
  useEffect(() => {
    if (props.data.data.length > 0) {
      var root = am5.Root.new("interactionLineRoot");

      root.setThemes([am5themes_Animated.new(root)]);

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
        am5xy.CategoryAxis.new(root, {
          categoryField: "date",
          renderer: am5xy.AxisRendererX.new(root, {
            minGridDistance: 1,
          }),
        })
      );

      xAxis.get("renderer").labels.template.setAll({
        rotation: -90,
        centerY: am5.p50,
        centerX: am5.p100,
        paddingTop: 15,
      });

      xAxis.data.setAll(props.data.data);

      //   xAxis.setAll({
      //     min: props.data.data[0].date,
      //     max: new Date().getFullYear(),
      //     strictMinMax: true,
      //   });

      let yAxis = chart.yAxes.push(
        am5xy.ValueAxis.new(root, {
          renderer: am5xy.AxisRendererY.new(root, {}),
        })
      );

      //   let seriesSubstances = chart.series.push(
      //     am5xy.ColumnSeries.new(root, {
      //       name: "Substances per month",
      //       xAxis: xAxis,
      //       yAxis: yAxis,
      //       valueYField: "value1",
      //       valueXField: "date",
      //       clustered: false,
      //       tooltip: am5.Tooltip.new(root, {
      //         labelText: "{valueY} substances",
      //       }),
      //     })
      //   );

      const color = chart.get("colors")!.getIndex(7);

      let seriesInteractions = chart.series.push(
        am5xy.ColumnSeries.new(root, {
          name: "Interactions per year",
          xAxis: xAxis,
          yAxis: yAxis,
          valueYField: "value1",
          categoryXField: "date",
          fill: color,
          stroke: color,
          // clustered: false,
          tooltip: am5.Tooltip.new(root, {
            labelText: "{valueY} interactions",
          }),
        })
      );

      //   seriesInteractions.bullets?.push(() =>
      //     am5.Bullet.new(root, {
      //       sprite: am5.Circle.new(root, {
      //         radius: 5,
      //         fill: color,
      //         stroke: color,
      //       }),
      //     })
      //   );

      //   seriesSubstances.columns.template.setAll({
      //     strokeOpacity: 0,
      //     width: am5.percent(50),
      //     tooltipY: 0,
      //   });

      //   // yAxis.data.setAll(data);
      //   seriesSubstances.data.setAll(props.data.data);
      seriesInteractions.data.setAll(props.data.data);

      //   seriesSubstances.appear(1000);
      seriesInteractions.appear(1000);
      chart.appear(1000, 100);
    }

    return () => {
      root.dispose();
    };
  }, []);

  return (
    <div className="flex flex-col lg:flex-row justify-between gap-16 lg:gap-32 lg:pt-8">
      {/* TEXT */}
      <div className="flex flex-col items-center justify-center gap-8 w-full lg:w-1/2 lg:pb-24">
        <h1 className="text-center text-2xl font-bold">
          The behavior of molecules has been studied in more than{" "}
          <span className="text-primary">
            {1000 * Math.floor(props.totalPublications / 1000)}
          </span>{" "}
          scientific sources published since{" "}
          <span className="text-danger">{props.minPublishedYear}</span>.
        </h1>
      </div>
      {/* CHART */}
      <div className="flex flex-col justify-center items-center w-full sm:px-8 lg:w-1/2">
        <div
          id="interactionLineRoot"
          className="w-full lg:w-[450px] xl:w-[600px] h-[350px] md:h-[400px]"
          // style={{ width: "600px", height: "400px" }}
        ></div>
      </div>
    </div>
  );
}
