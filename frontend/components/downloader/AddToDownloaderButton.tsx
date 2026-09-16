"use client";

import {
  DownloaderCategory,
  useDownloader,
} from "@/components/_core/providers/downloader";
import { Button } from "@heroui/react";
import { FiCheck, FiPlus } from "react-icons/fi";

export default function AddToDownloaderButton(props: {
  category: DownloaderCategory;
  id: string;
  label: string;
}) {
  const { isAdded, addItem, removeItem } = useDownloader();
  const added = isAdded(props.category, props.id);

  return (
    <Button
      color={added ? "success" : "primary"}
      size="md"
      variant={added ? "flat" : "bordered"}
      startContent={added ? <FiCheck /> : <FiPlus />}
      onPress={() =>
        added
          ? removeItem(props.category, props.id)
          : addItem({
              category: props.category,
              id: props.id,
              label: props.label,
            })
      }
    >
      {added ? "Added to downloader" : "Add to downloader"}
    </Button>
  );
}
