"use client";

import type { Ketcher } from "ketcher-core";
import { Editor } from "ketcher-react";
import { StandaloneStructServiceProvider } from "ketcher-standalone";
import { useMemo } from "react";

export default function KetcherEditor(props: {
  onError: (message: string) => void;
  onInit: (ketcher: Ketcher) => void;
}) {
  const structServiceProvider = useMemo(
    () => new StandaloneStructServiceProvider(),
    [],
  );

  return (
    <Editor
      disableMacromoleculesEditor
      staticResourcesUrl="/"
      structServiceProvider={structServiceProvider}
      buttons={{
        about: { hidden: true },
        arrows: { hidden: true },
        "create-monomer": { hidden: true },
        "enhanced-stereo": { hidden: true },
        help: { hidden: true },
        miew: { hidden: true },
        recognize: { hidden: true },
        "reaction-mapping-tools": { hidden: true },
        "reaction-plus": { hidden: true },
        rgroup: { hidden: true },
        shape: { hidden: true },
        text: { hidden: true },
      }}
      errorHandler={props.onError}
      onInit={props.onInit}
    />
  );
}
