"use client";
import { Alert, Button } from "@heroui/react";
import Image from "next/image";
import Link from "next/link";
import { MdOutlineCloudUpload } from "react-icons/md";

export default function SectionUpload(props: { isLoggedIn: boolean }) {
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
          <div className="flex flex-col justify-center gap-1">
            <Button
              as={Link}
              href="/lab/upload"
              endContent={<MdOutlineCloudUpload size={25} />}
              size="lg"
              isDisabled={!props.isLoggedIn}
              variant="solid"
              color="primary"
              className="text-white"
            >
              Upload
            </Button>
            {!props.isLoggedIn && (
              <Alert color="warning" className="mt-2">
                Please log in to upload new datasets.
              </Alert>
            )}
          </div>
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
