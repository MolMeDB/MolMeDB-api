"use client";
import { useEffect, useRef, useState } from "react";
import { motion } from "framer-motion";

const partners = [
  { src: "/assets/partners/pubchem.png", angle: 340 },
  { src: "/assets/partners/pdb.png", angle: 20 },
  { src: "/assets/partners/elixir.png", angle: 70 },
  { src: "/assets/partners/drugbank2.png", angle: 110 },
  { src: "/assets/partners/chembl.png", angle: 160 },
  { src: "/assets/partners/uniprot.png", angle: 200 },
];

export default function InteroperabilitySection() {
  return (
    <>
      <div className="lg:hidden">
        <InteroperabilityContent size="small" />
      </div>
      <div className="hidden lg:block">
        <InteroperabilityContent size="large" />
      </div>
    </>
  );
}

function InteroperabilityContent(props: { size: "small" | "large" }) {
  const containerRef = useRef(null);
  const [height, setHeight] = useState(750);
  const [width, setWidth] = useState(400);

  useEffect(() => {
    const observer = new ResizeObserver((entries) => {
      for (let entry of entries) {
        const width = entry.contentRect.width;
        const height = entry.contentRect.height;
        setWidth(width);
        setHeight(height);
      }
    });

    if (containerRef.current) {
      observer.observe(containerRef.current);
    }

    return () => observer.disconnect();
  }, []);

  const centerH = height / 2;
  const centerW = width / 2;
  const radius = width * 0.5; // jako 35 % šířky kontejneru
  const logoSize = props.size === "small" ? height * 0.13 : height * 0.18; // např. 12 %

  return (
    <div className="relative w-full h-screen lg:h-[950px] px-8 py-28 bg-gradient-to-br from-intro-blue via-intro-purple to-intro-pink">
      <div className="flex flex-col items-center gap-4 -mb-24">
        <h1 className="text-4xl font-bold text-white text-center">
          Focused on interoperability
        </h1>
        <h2 className="text-2xl font-bold text-white text-center">
          We are connected to
        </h2>
      </div>
      <div className="flex justify-center items-start h-full lg:h-auto">
        <div
          ref={containerRef}
          className="relative sm:aspect-square w-full max-w-[800px] h-full lg:h-auto"
        >
          {/* SVG čáry */}
          <svg
            className="absolute inset-0 w-full h-full pointer-events-none"
            viewBox={`0 0 ${width} ${height}`}
            preserveAspectRatio="xMidYMid meet"
          >
            {partners.map(({ angle }, i) => {
              angle = props.size === "small" ? i * 25 - 30 : angle;
              const rad = (angle * Math.PI) / 180;
              const x =
                (props.size === "small" ? centerW / 2 : centerW) +
                radius * Math.cos(rad);
              const y =
                props.size === "large" && angle > 60 && angle < 120
                  ? centerH + (radius / 1.5) * Math.sin(rad)
                  : centerH + radius * Math.sin(rad);
              return (
                <motion.line
                  key={i}
                  x1={props.size === "small" ? centerW / 2 : centerW}
                  y1={centerH}
                  x2={x}
                  y2={y}
                  stroke="darkgray"
                  strokeWidth="3"
                  // strokeDasharray="10 4"
                  initial={{ pathLength: 0 }}
                  whileInView={{ pathLength: 1 }}
                  transition={{ duration: 2, ease: "easeInOut" }}
                  viewport={{ once: true }}
                />
              );
            })}
          </svg>

          {/* Centrální logo */}
          <div
            className="absolute z-10"
            style={{
              left: `${
                (props.size === "small" ? centerW / 2 : centerW) - logoSize / 2
              }px`,
              top: `${centerH - logoSize / 2}px`,
              width: `${logoSize}px`,
              height: `${logoSize}px`,
            }}
          >
            <img
              src="/assets/partners/molmedb.png"
              alt="MolMeDB"
              className="w-full h-full object-contain"
            />
          </div>

          {/* Loga partnerů */}
          {partners.map(({ src, angle }, i) => {
            angle = props.size === "small" ? i * 25 - 30 : angle;
            const rad = (angle * Math.PI) / 180;
            const x =
              (props.size === "small" ? centerW / 2 : centerW) +
              radius * Math.cos(rad) -
              logoSize / 2;
            const y =
              props.size === "large" && angle > 60 && angle < 120
                ? centerH + (radius / 1.5) * Math.sin(rad) - logoSize / 2
                : centerH + radius * Math.sin(rad) - logoSize / 2;

            return (
              <motion.img
                key={i}
                src={src}
                alt={`Partner ${i + 1}`}
                className="absolute object-contain"
                style={{
                  width: `${logoSize}px`,
                  height: `${logoSize}px`,
                  left: `${x}px`,
                  top: `${y}px`,
                }}
                initial={{ scale: 0, opacity: 0 }}
                whileInView={{ scale: 1, opacity: 1 }}
                transition={{ duration: 1, delay: 1, ease: "easeOut" }}
                viewport={{ once: true }}
              />
            );
          })}
        </div>
      </div>
    </div>
  );
}
