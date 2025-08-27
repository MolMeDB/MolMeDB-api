"use client";
import Image from "next/image";
import { useEffect, useRef, useState } from "react";
import { motion } from "framer-motion";

const items = [
  {
    count: "970,000+",
    label: "behaviour",
    image: "/assets/layout/intro/stats/behaviour2.png",
  },
  {
    count: "56",
    label: "membranes",
    image: "/assets/layout/intro/stats/membrane.png",
  },
  {
    count: "32",
    label: "methods",
    image: "/assets/layout/intro/stats/method.png",
  },
  {
    count: "360",
    label: "proteins",
    image: "/assets/layout/intro/stats/protein.png",
  },
  {
    count: "3,000+",
    label: "interactions",
    image: "/assets/layout/intro/stats/interaction.png",
  },
];

export default function StatsSection() {
  return (
    <>
      <div className="lg:hidden">
        <StatsContent size="small" />
      </div>
      <div className="hidden lg:block">
        <StatsContent size="large" />
      </div>
    </>
  );
}

function StatsContent(props: { size: "small" | "large" }) {
  const containerRef = useRef(null);
  const [height, setHeight] = useState(900); // default height
  const [width, setWidth] = useState(800); // default width

  useEffect(() => {
    const observer = new ResizeObserver((entries) => {
      for (let entry of entries) {
        const width = entry.contentRect.width;
        const height = entry.contentRect.height;
        setHeight(height);
        setWidth(width);
        // setSize(Math.min(width, height)); // čtvercová plocha
      }
    });

    if (containerRef.current) {
      observer.observe(containerRef.current);
    }

    return () => observer.disconnect();
  }, []);

  const centerH = height / 2;
  const centerW = width / 2;
  const shift = props.size === "small" ? 30 : 150;
  const topElementWidth = props.size === "small" ? 250 : 350;
  const topElementHeight = props.size === "small" ? 100 : 120;
  const elementWidth = props.size === "small" ? 150 : 0;
  const ElementHeight = props.size === "small" ? 500 : 260;

  return (
    <div
      className="relative w-full lg:h-[900px] h-[1100px] px-8 py-28 bg-gradient-to-br 
          from-intro-blue dark:from-intro-purple-dark via-intro-purple dark:via-intro-purple-dark to-intro-pink dark:to-intro-pink-dark"
    >
      <div className="flex flex-col items-center gap-4 -mb-24">
        <motion.div
          initial={{ y: 50, opacity: 0 }}
          whileInView={{ y: 0, opacity: 1 }}
          transition={{ duration: 0.6, ease: "easeOut" }}
          viewport={{ once: true }}
        >
          <h1 className="text-4xl font-bold text-white text-center">
            We already gathered
          </h1>
        </motion.div>
      </div>
      <div className="flex justify-center items-start h-full lg:h-auto">
        <div
          ref={containerRef}
          className={`relative sm:aspect-square w-full lg:max-h-[800px] lg:max-w-[900px] xl:max-w-[1100px] h-full lg:h-auto`}
        >
          {/* SVG lines */}
          <svg
            className="absolute inset-0 w-full h-full pointer-events-none hidden lg:block"
            viewBox={`0 0 ${width} ${height}`}
            preserveAspectRatio="xMidYMid meet"
          >
            {/* TOP LINE  */}
            <motion.line
              x1={centerW}
              y1={centerH - shift + 130 / 2}
              x2={centerW}
              y2={centerH - shift / 2 + 130 / 3}
              stroke="white"
              strokeWidth="2"
              initial={{ pathLength: 0 }}
              whileInView={{ pathLength: 1 }}
              transition={{ duration: 1, ease: "easeInOut" }}
              viewport={{ once: true }}
            />
            <motion.path
              d={`
                  M ${
                    width -
                    (width - (items.length - 1) * 20) / items.length / 2 -
                    10
                  } ${centerH - shift / 2 + 130 / 3}
                  A 10 10 0 0 1 ${
                    width - (width - (items.length - 1) * 20) / items.length / 2
                  } ${centerH - shift / 2 + 130 / 3 + 10}
                  L ${
                    width - (width - (items.length - 1) * 20) / items.length / 2
                  } ${centerH - 260 / 2 + shift}

                  M ${
                    0 +
                    (width - (items.length - 1) * 20) / items.length / 2 +
                    10
                  } ${centerH - shift / 2 + 130 / 3}
                  A 10 10 0 0 0 ${
                    0 + (width - (items.length - 1) * 20) / items.length / 2
                  } ${centerH - shift / 2 + 130 / 3 + 10}
                  L ${
                    0 + (width - (items.length - 1) * 20) / items.length / 2
                  } ${centerH - 260 / 2 + shift}

                  M ${
                    0 +
                    (width - (items.length - 1) * 20) / items.length / 2 +
                    10
                  } ${centerH - shift / 2 + 130 / 3}
                  L ${
                    width -
                    (width - (items.length - 1) * 20) / items.length / 2 -
                    10
                  } ${centerH - shift / 2 + 130 / 3}
                `}
              fill="none"
              stroke="white"
              strokeWidth="2"
              initial={{ pathLength: 0 }}
              whileInView={{ pathLength: 1 }}
              transition={{ duration: 2 }}
              viewport={{ once: true }}
            />
            {/* BEHAVIOUR -> MEMBRANES */}
            <motion.path
              d={`
                M ${0 + (width - (items.length - 1) * 20) / items.length / 2} ${
                centerH + 260 / 2 + shift
              } 
                L ${0 + (width - (items.length - 1) * 20) / items.length / 2} ${
                centerH + 260 / 2 + shift + 130 / 3 - 10
              }
                A 10 10 0 0 0 ${
                  0 + (width - (items.length - 1) * 20) / items.length / 2 + 10
                } ${centerH + 260 / 2 + shift + 130 / 3}
                L ${
                  0 +
                  (3 * (width - (items.length - 1) * 20)) / items.length / 2 -
                  10
                } ${centerH + 260 / 2 + shift + 130 / 3}
                A 10 10 0 0 0 ${
                  0 + (3 * (width - (items.length - 1) * 20)) / items.length / 2
                } ${centerH + 260 / 2 + shift + 130 / 3 - 10}
                L ${
                  0 + (3 * (width - (items.length - 1) * 20)) / items.length / 2
                } ${centerH + 260 / 2 + shift}
              `}
              fill="none"
              stroke="white"
              strokeWidth="2"
              initial={{ pathLength: 0 }}
              whileInView={{ pathLength: 1 }}
              transition={{ duration: 2 }}
              viewport={{ once: true }}
            />
            {/* MEMBRANES -> METHODS */}
            <motion.path
              d={`
                M ${
                  0 +
                  40 +
                  (3 * (width - (items.length - 1) * 20)) / items.length / 2
                } ${centerH + 260 / 2 + shift} 
                L ${
                  0 +
                  40 +
                  (3 * (width - (items.length - 1) * 20)) / items.length / 2
                } ${centerH + 260 / 2 + shift + 130 / 3 - 10}
                A 10 10 0 0 0 ${
                  0 +
                  40 +
                  (3 * (width - (items.length - 1) * 20)) / items.length / 2 +
                  10
                } ${centerH + 260 / 2 + shift + 130 / 3}
                L ${
                  0 +
                  40 +
                  (5 * (width - (items.length - 1) * 20)) / items.length / 2 -
                  10
                } ${centerH + 260 / 2 + shift + 130 / 3}
                A 10 10 0 0 0 ${
                  0 +
                  40 +
                  (5 * (width - (items.length - 1) * 20)) / items.length / 2
                } ${centerH + 260 / 2 + shift + 130 / 3 - 10}
                L ${
                  0 +
                  40 +
                  (5 * (width - (items.length - 1) * 20)) / items.length / 2
                } ${centerH + 260 / 2 + shift}
              `}
              fill="none"
              stroke="white"
              strokeWidth="2"
              initial={{ pathLength: 0 }}
              whileInView={{ pathLength: 1 }}
              transition={{ duration: 2 }}
              viewport={{ once: true }}
            />
            {/* INTERACTIONS -> PROTEINS */}
            <motion.path
              d={`
                M ${
                  width - (width - (items.length - 1) * 20) / items.length / 2
                } ${centerH + 260 / 2 + shift} 
                L ${
                  width - (width - (items.length - 1) * 20) / items.length / 2
                } ${centerH + 260 / 2 + shift + 130 / 3 - 10}
                A 10 10 0 0 1 ${
                  width -
                  (width - (items.length - 1) * 20) / items.length / 2 -
                  10
                } ${centerH + 260 / 2 + shift + 130 / 3}
                L ${
                  width -
                  20 -
                  3 * ((width - (items.length - 1) * 20) / items.length / 2) +
                  10
                } ${centerH + 260 / 2 + shift + 130 / 3}
                A 10 10 0 0 1 ${
                  width -
                  20 -
                  3 * ((width - (items.length - 1) * 20) / items.length / 2)
                } ${centerH + 260 / 2 + shift + 130 / 3 - 10}
                L ${
                  width -
                  20 -
                  3 * ((width - (items.length - 1) * 20) / items.length / 2)
                } ${centerH + 260 / 2 + shift}
              `}
              fill="none"
              stroke="white"
              strokeWidth="2"
              initial={{ pathLength: 0 }}
              whileInView={{ pathLength: 1 }}
              transition={{ duration: 2 }}
              viewport={{ once: true }}
            />
          </svg>
          {/* SMALL SCREENS */}
          <svg
            className="absolute inset-0 w-full h-full pointer-events-none lg:hidden"
            viewBox={`0 0 ${width} ${height}`}
            preserveAspectRatio="xMidYMid meet"
          >
            {/* TOP LINE  */}
            <motion.line
              x1={centerW}
              y1={220 + shift}
              x2={centerW}
              y2={245 + shift}
              stroke="white"
              strokeWidth="2"
              initial={{ pathLength: 0 }}
              whileInView={{ pathLength: 1 }}
              transition={{ duration: 1, ease: "easeInOut" }}
              viewport={{ once: true }}
            />
            <motion.path
              d={`
                  M ${width / 4} ${240 + 2 * shift}
                  L ${width / 4} ${240 + 2 * shift - 20}
                  A 10 10 0 0 1 ${width / 4 + 10} ${240 - 25 + 2 * shift}
                  L ${centerW} ${240 - 25 + 2 * shift}
                `}
              fill="none"
              stroke="white"
              strokeWidth="2"
              initial={{ pathLength: 0 }}
              whileInView={{ pathLength: 1 }}
              transition={{ duration: 2 }}
              viewport={{ once: true }}
            />
            <motion.path
              d={`
                  M ${(3 * width) / 4} ${240 + 2 * shift}
                  L ${(3 * width) / 4} ${240 + 2 * shift - 20}
                  A 10 10 0 0 0 ${(3 * width) / 4 - 10} ${240 - 25 + 2 * shift}
                  L ${centerW} ${240 - 25 + 2 * shift}
                `}
              fill="none"
              stroke="white"
              strokeWidth="2"
              initial={{ pathLength: 0 }}
              whileInView={{ pathLength: 1 }}
              transition={{ duration: 2 }}
              viewport={{ once: true }}
            />
            {/* BEHAVIOUR -> MEMBRANES */}
            <motion.line
              x1={width / 4}
              y1={240 + 2 * shift + 180}
              x2={width / 4}
              y2={240 + 2 * shift + 180 + shift}
              stroke="white"
              strokeWidth="2"
              initial={{ pathLength: 0 }}
              whileInView={{ pathLength: 1 }}
              transition={{
                duration: 1,
                delay: 1,
                ease: "easeInOut",
              }}
              viewport={{ once: true }}
            />
            {/* MEMBRANES -> METHODS */}
            <motion.line
              x1={width / 4}
              y1={240 + 3 * shift + 2 * 180}
              x2={width / 4}
              y2={240 + 3 * shift + 2 * 180 + shift}
              stroke="white"
              strokeWidth="2"
              initial={{ pathLength: 0 }}
              whileInView={{ pathLength: 1 }}
              transition={{
                duration: 1,
                delay: 2,
                ease: "easeInOut",
              }}
              viewport={{ once: true }}
            />
            {/* INTERACTIONS -> PROTEINS */}
            <motion.line
              x1={(3 * width) / 4}
              y1={240 + 2 * shift + 180}
              x2={(3 * width) / 4}
              y2={240 + 2 * shift + 180 + shift}
              stroke="white"
              strokeWidth="2"
              initial={{ pathLength: 0 }}
              whileInView={{ pathLength: 1 }}
              transition={{
                duration: 1,
                delay: 1,
                ease: "easeInOut",
              }}
              viewport={{ once: true }}
            />
          </svg>

          {/* TOP ELEMENT */}
          <motion.div
            className="absolute z-10
              bg-transparent flex flex-row items-center justify-around gap-4 border-2 border-white rounded-xl p-4"
            style={{
              left: `${centerW - topElementWidth / 2}px`,
              top: `${
                props.size === "small"
                  ? 150
                  : centerH - topElementHeight / 2 - shift
              }px`,
              width: `${topElementWidth}px`,
              height: `${topElementHeight}px`,
            }}
            initial={{ y: 50, opacity: 0 }}
            whileInView={{ y: 0, opacity: 1 }}
            transition={{ duration: 0.6, ease: "easeOut" }}
            viewport={{ once: true }}
          >
            <Image
              src="/assets/layout/intro/stats/molecules.png"
              alt="Molecules"
              width={120}
              height={120}
              className="w-auto h-full"
            />
            <div className="flex flex-col justify-center text-white">
              <h2 className="text-xl lg:text-3xl font-bold">490,000+</h2>
              <p className="text-lg lg:text-xl uppercase font-semibold">
                molecules
              </p>
            </div>
          </motion.div>

          {/* Second row */}
          {[
            ...(props.size === "small"
              ? [...items.slice(0, 3), items[4], items[3]]
              : items),
          ].map(({ count, label, image }, i) => {
            const space = 20;
            var w = (width - (items.length - 1) * space) / items.length;
            var h = ElementHeight;
            var x = i * w + space * i;
            var y = centerH - h / 2 + shift;

            if (props.size === "small") {
              w = elementWidth;
              h = 180;
              x = i < 3 ? width / 4 - w / 2 : (3 * width) / 4 - w / 2;
              y =
                i < 3
                  ? 200 + topElementHeight + (i ?? 2) * shift + i * h
                  : 200 + topElementHeight + (i - 3) * shift + (i - 3) * h;
            }

            return (
              <motion.div
                key={label}
                className="absolute z-10
              bg-transparent flex flex-col items-center justify-between lg:py-12 gap-4 border-2 border-white rounded-xl p-4"
                style={{
                  left: `${x}px`,
                  top: `${y}px`,
                  width: `${w}px`,
                  height: `${h}px`,
                }}
                initial={{ y: 50, opacity: 0 }}
                whileInView={{ y: 0, opacity: 1 }}
                transition={{ duration: 0.6, ease: "easeOut" }}
                viewport={{ once: true }}
              >
                <div className="flex flex-col justify-center items-center gap-2 text-white">
                  <h2 className="text-xl lg:text-2xl xl:text-3xl font-bold">
                    {count}
                  </h2>
                  <p className="text-md lg:text-xl uppercase font-semibold">
                    {label}
                  </p>
                </div>
                <Image
                  src={image}
                  alt={label}
                  className={
                    image.toLowerCase().includes("method")
                      ? "h-1/3 w-auto"
                      : "w-1/2 h-auto"
                  }
                  width={150}
                  height={150}
                  // sizes="100vw"
                />
              </motion.div>
            );
          })}
        </div>
      </div>
    </div>
  );
}
