"use client";

import CountUp from "react-countup";
import InteractionLine from "./charts/interactionLine";
import DatabaseBar from "./charts/databasesBar";
import TransportersBar from "./charts/transportersBar";
import MembraneBar from "./charts/membraneBar";
import IStatsGlobal from "@/lib/api/admin/interfaces/Stats";

export default function StatsContent(props: { stats: IStatsGlobal }) {
  const totalInteractions =
    props.stats.total.interactions.active +
    props.stats.total.interactions.passive;

  const totalWithExternal =
    Math.floor(
      (props.stats.plots.databasesBar?.items.reduce(
        (acc, item) => acc + item.value1,
        0
      ) || 0) / 1000
    ) * 1000;

  return (
    <>
      <div className="w-full flex flex-col items-center gap-8 pt-16 px-8 sm:px-16 font-sans">
        <div className="text-2xl lg:text-3xl text-center xl:w-3xl">
          Passive interactions are typicaly defined by the combination of one of{" "}
          <span className="text-primary font-bold">
            <CountUp start={1} end={props.stats.total.membranes} duration={3} />
          </span>{" "}
          membranes and one of{" "}
          <span className="text-primary font-bold">
            <CountUp start={1} end={props.stats.total.methods} duration={3} />
          </span>{" "}
          methods.
        </div>
        <div className="h-full flex flex-col xl:flex-row items-center justify-between gap-8 xl:gap-16 py-8 xl:py-12 w-full xl:w-7xl xl:mx-auto">
          <div className="xl:w-2/6 w-full h-full xl:p-16 text-2xl flex flex-col justify-center font-sans">
            <div className="flex flex-col justify-center gap-2 xl:gap-4 w-full">
              So far, our team gathered
              <div className="flex flex-row no-wrap gap-4 items-end">
                <span className="text-pink-600 font-bold text-4xl sm:text-5xl">
                  <CountUp
                    start={totalInteractions / 10}
                    end={totalInteractions}
                    duration={3}
                  />
                </span>{" "}
                <span className="text-pink-600 font-bold">interactions</span>
              </div>
              for
              <div className="flex flex-row no-wrap gap-4 items-end">
                <span className="text-sky-500 font-bold text-4xl sm:text-5xl">
                  <CountUp
                    start={props.stats.total.structures / 10}
                    end={props.stats.total.structures}
                    duration={3}
                  />
                </span>
                <span className="text-sky-500 font-bold">substances</span>
              </div>
            </div>
          </div>
          <div className="flex justify-end pt-8 h-full">
            {props.stats.plots.interactionsLine && (
              <InteractionLine data={props.stats.plots.interactionsLine} />
            )}
          </div>
        </div>
      </div>
      <div className="h-12 w-full bg-big-delimiter dark:bg-big-delimiter-dark"></div>
      <div className="flex flex-col-reverse xl:flex-row items-center justify-between gap-8 xl:gap-16 w-full xl:w-7xl xl:mx-auto">
        <div className="xl:p-8 flex flex-col gap-8">
          {props.stats.plots.databasesBar && (
            <DatabaseBar data={props.stats.plots.databasesBar} />
          )}
        </div>
        <div className="p-8 flex flex-col gap-8">
          <div className="text-xl lg:text-2xl text-center xl:text-right font-sans">
            And more than
            <span className="text-primary font-bold text-4xl lg:text-6xl">
              <CountUp start={100} end={totalWithExternal} duration={3} />
            </span>{" "}
            compounds has found counterparts in other chemical databases.
          </div>
        </div>
      </div>
      <div className="w-full bg-big-delimiter dark:bg-big-delimiter-dark text-white font-sans">
        <div className="flex flex-col xl:flex-row gap-16 py-16 px-8 lg:px-16 mx-auto xl:w-7xl">
          <div className="w-full xl:w-1/2 flex flex-col justify-center gap-4 text-xl">
            While passive interactions describe the spontaneous permeation of a
            molecule through the membrane, active interactions describe the
            action of the molecule on cellular
            <div className="flex flex-col gap-2 font-bold uppercase py-4">
              <div className="border-l-4 border-white pl-4">transporters</div>
              <div className="border-l-4 border-white pl-4">ion channels</div>
              <div className="border-l-4 border-white pl-4">receptors</div>
              <div className="border-l-4 border-white pl-4">enzymes</div>
            </div>
            which subsequently ensure the permeation itself.
          </div>
          <div className="w-full xl:w-1/2">
            {props.stats.plots.proteinsBar && (
              <TransportersBar data={props.stats.plots.proteinsBar} />
            )}
          </div>
        </div>
      </div>
    </>
  );
}
