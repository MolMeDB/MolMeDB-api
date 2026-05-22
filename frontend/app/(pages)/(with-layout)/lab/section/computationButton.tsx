"use client";
import { motion } from "framer-motion";
import Image from "next/image";
import Link from "next/link";

export default function SectionComputationButtons(props: {
  isLoggedIn: boolean;
  isDisabled?: boolean;
}) {
  return (
    <>
      <div className="flex flex-col md:flex-row gap-8 md:gap-16">
        <div className="w-full md:w-1/2 h-[250px] lg:h-[350px] flex justify-center">
          <Image
            src="/assets/layout/lab/lab-1.png"
            alt="Lab computer"
            width={0}
            height={0}
            sizes="100vw"
            className="h-full w-auto"
          />
        </div>
        <div className="text-center flex flex-col justify-center items-center gap-8 w-full md:w-1/2">
          <h1 className="text-2xl lg:text-3xl font-bold text-primary dark:text-primary-600">
            Help us expand MolMeDB with pharmacologically interesting molecules
          </h1>
          <p className="text-lg">
            Use our laboratory for a free calculations of interaction values for
            your proposed molecules.
          </p>
        </div>
      </div>

      <div className="relative w-full flex flex-col sm:flex-row justify-center items-center gap-8 lg:gap-16 lg:h-[250px]">
        {props.isDisabled ?
          <div className="absolute top-0 left-0 w-full h-full bg-white/70 dark:bg-background-dark/70 z-10 rounded-2xl lg:rounded-4xl flex flex-col justify-center items-center gap-4">
            <h2 className="text-xl lg:text-2xl font-bold">
              Feature currently under development
            </h2>
            <p className="text-center px-8">
              This functionality is currently being refined and is not available at the moment. 
              We’re working on improving and finalizing it to ensure the best possible experience. Please check back later.
            </p>
          </div>
        :
        !props.isLoggedIn && (
          <div className="absolute top-0 left-0 w-full h-full bg-white/70 dark:bg-background-dark/70 z-10 rounded-2xl lg:rounded-4xl flex flex-col justify-center items-center gap-4">
            <h2 className="text-xl lg:text-2xl font-bold">
              Please log in to access all laboratory features
            </h2>
            <p className="text-center px-8">
              You need to be logged in to use the computation features of our
              laboratory. Please log in or create an account to proceed.
            </p>
          </div>
        )}
        <motion.a
          href="/lab/new-predictions"
          whileHover={{ scale: 1.05 }}
          whileTap={{ scale: 0.95 }}
          className="h-[150px] lg:h-[250px] w-auto sm:w-1/3 relative cursor-pointer bg-zinc-500 rounded-2xl lg:rounded-4xl"
        >
          <Image
            src="/assets/layout/lab/lab-upload.png"
            alt="Compute new data"
            width={0}
            height={0}
            sizes="100vw"
            className="h-full w-auto"
          />
          <div className="absolute bottom-0 left-0 w-full h-1/2 bg-white/20 backdrop-blur-md rounded-b-2xl overflow-hidden flex flex-row justify-center items-center">
            <label className="text-md lg:text-xl font-bold uppercase text-zinc-100">
              New calculations
            </label>
          </div>
        </motion.a>
        <motion.a
          href="/lab/running-predictions"
          whileHover={{ scale: 1.05 }}
          whileTap={{ scale: 0.95 }}
          className="h-[150px] lg:h-[250px] w-auto sm:w-1/3 relative cursor-pointer bg-zinc-500 rounded-2xl lg:rounded-4xl"
        >
          <Image
            src="/assets/layout/lab/lab-download.png"
            alt="Download results"
            width={0}
            height={0}
            sizes="100vw"
            className="h-full w-auto"
          />
          <div className="absolute bottom-0 left-0 w-full h-1/2 bg-white/20 backdrop-blur-md rounded-b-2xl overflow-hidden flex flex-row justify-center items-center">
            <label className="text-md lg:text-xl font-bold uppercase text-zinc-100">
              Running calculations
            </label>
          </div>
        </motion.a>
      </div>
    </>
  );
}
