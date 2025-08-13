"use client";

import { Spinner } from "@heroui/react";
import Head from "next/head";
import Script from "next/script";
import { useEffect, useRef } from "react";

export default function MolStar(props: { sdfPath: string }) {
  const viewerRef = useRef(null);

  useEffect(() => {
    const checkAndInit = () => {
      if (
        typeof window !== "undefined" &&
        (window as any).PDBeMolstarPlugin &&
        viewerRef.current
      ) {
        const instance = new (window as any).PDBeMolstarPlugin();
        const options = {
          customData: {
            url: props.sdfPath,
            format: "sdf",
            binary: false,
          },
          hideControls: true,
          hideCanvasControls: [
            "expand",
            "controlToggle",
            "controlInfo",
            "selection",
            "animation",
            "trajectory",
          ],
          leftPanel: false,
          rightPanel: false,
          bgColor: "white",
          reactive: true,
        };
        instance.render(viewerRef.current, options);
        return true;
      }
      return false;
    };

    const interval = setInterval(() => {
      if (checkAndInit()) clearInterval(interval);
    }, 100);

    return () => clearInterval(interval);
  }, []);

  return (
    <div className="w-full h-full">
      <Head>
        <script src="/js/pdbe-molstar-plugin.js" defer />
        <link rel="stylesheet" type="text/css" href="/css/pdbe-molstar.css" />
      </Head>
      <Script src="/js/pdbe-molstar-plugin.js" />
      <div
        ref={viewerRef}
        style={{ height: "100%", width: "100%" }}
        className="flex items-center justify-center"
      >
        <Spinner variant="wave" label="Loading..." />
      </div>
    </div>
  );
}
