"use client";
import { motion } from "framer-motion";
import Image from "next/image";

export default function SectionComputationButtons() {
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

      <div className="w-full flex flex-col sm:flex-row justify-center items-center gap-8 lg:gap-16 lg:h-[250px]">
        <motion.div
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
        </motion.div>
        <motion.div
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
              Download results
            </label>
          </div>
        </motion.div>
      </div>
    </>
  );
}
