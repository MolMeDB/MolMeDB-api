"use client";
import Image from "next/image";
import { motion } from "framer-motion";

export default function HowInteracts() {
  return (
    <div className="relative w-full min-h-[650px] lg:min-h-auto overflow-hidden p-8 sm:p-16 lg:p-24 xl:p-32 ">
      <motion.div
        initial={{ y: 200, opacity: 0 }}
        whileInView={{ y: 0, opacity: 1 }}
        transition={{ duration: 1, ease: "easeOut" }}
        viewport={{ once: true }}
      >
        <div className="flex flex-col-reverse lg:flex-row justify-center items-center gap-8 sm:gap-16 lg:gap-24">
          <div className="h-full max-w-xl">
            <Image
              src="/assets/layout/intro/interactions.svg"
              alt="Behavior of molecule on membrane"
              priority
              className="w-full h-auto "
              width={0}
              height={0}
              sizes="100vw"
            />
          </div>
          <div className="flex flex-col justify-center text-foreground gap-12 ">
            <h1 className="text-2xl md:text-3xl font-bold leading-tight text-center lg:text-left">
              How do compounds interact with membranes?
            </h1>
            <ul className="list-disc pl-6 text-xl text-semibold space-y-3">
              <li>they are attracted to membranes</li>
              <li>they partition to membranes</li>
              <li>they reside in specific positions on membranes</li>
              <li>they penetrate through membranes</li>
              <li>they change the structure of membranes</li>
              <li>…and more</li>
            </ul>
          </div>
        </div>
      </motion.div>
    </div>
  );
}
