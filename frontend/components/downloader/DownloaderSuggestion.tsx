"use client";

import {
  DownloaderCategory,
  useDownloader,
} from "@/components/_core/providers/downloader";
import { useEffect } from "react";

export default function DownloaderSuggestion(props: {
  category: DownloaderCategory;
  id: string;
  label: string;
}) {
  const { setSuggestedItem } = useDownloader();

  useEffect(() => {
    setSuggestedItem({
      category: props.category,
      id: props.id,
      label: props.label,
    });

    return () => setSuggestedItem(null);
  }, [props.category, props.id, props.label, setSuggestedItem]);

  return null;
}
