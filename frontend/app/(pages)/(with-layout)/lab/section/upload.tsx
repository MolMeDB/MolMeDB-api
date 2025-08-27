"use client";
import { Button } from "@heroui/react";
import { motion } from "framer-motion";
import Image from "next/image";
import { MdOutlineCloudUpload } from "react-icons/md";

export default function SectionUpload() {
  return (
    <>
      <div className="flex flex-col lg:flex-row gap-16 lg:gap-32 ">
        <div className="text-center flex flex-col justify-center items-center gap-8">
          <h1 className="text-3xl font-bold text-primary dark:text-primary-600">
            Do you already have computed data?
          </h1>
          <p className="text-lg">
            Feel free to share the data with the world! <br />
            <b>
              Please note that the data must be already publised with a valid
              DOI.
            </b>
          </p>
          <Button
            endContent={<MdOutlineCloudUpload size={25} />}
            size="lg"
            variant="solid"
            color="primary"
            className="text-white"
          >
            Upload
          </Button>
        </div>
        <div className="w-full lg:w-1/2 lg:h-[350px] flex justify-center">
          <Image
            src="/assets/layout/lab/lab-2.png"
            alt="Lab upload"
            width={0}
            height={0}
            sizes="100vw"
            className="h-full lg:h-auto w-auto lg:w-full max-h-[250px]"
          />
        </div>
      </div>
    </>
  );
}
