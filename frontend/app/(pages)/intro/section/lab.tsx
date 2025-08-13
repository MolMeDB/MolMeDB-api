"use client";

import Image from "next/image";
import { motion } from "framer-motion";
import { Button } from "@heroui/react";
import { JSX } from "react";

export default function LabSection() {
  return (
    <div className="relative w-full min-h-[650px] lg:min-h-auto overflow-hidden p-8 sm:p-16 lg:p-24 xl:px-48 xl:py-24 bg-zinc-200">
      <div className="flex flex-col gap-12">
        <h1 className="text-3xl lg:text-4xl font-bold text-foreground text-center">
          Custom laboratory
        </h1>
        <p className="text-center text-lg md:text-xl">
          Each registered user will gain access to a personal lab, where they
          can calculate permeability values for their own sets of molecules. The
          data can be kept private for a limited period (up to one year).
          <br />
          <br />
          <strong>How does it work?</strong>
        </p>
        <div className="flex flex-row items-center justify-around gap-16">
          <motion.div
            initial={{ x: -100, opacity: 0 }}
            whileInView={{ x: 0, opacity: 1 }}
            transition={{ duration: 0.6, ease: "easeOut" }}
            viewport={{ once: true }}
            className="hidden lg:flex w-1/3 justify-center"
          >
            <Image
              src="/assets/layout/intro/lab/cluster.png"
              alt="Cluster"
              width={0}
              height={0}
              sizes="100vw"
              className="w-3/4 h-auto"
            />
          </motion.div>
          <div className="flex flex-col w-full lg:w-2/3 gap-6">
            <Card
              order={1}
              title="Find molecule of interest"
              description={
                <p>
                  Collect <strong>SMILES</strong> of one or more molecules.
                </p>
              }
            />
            <Card
              order={2}
              title="Upload to your lab"
              description={
                <p>
                  Select one of prepared membrane and in-house calcaulation
                  method.
                </p>
              }
            />
            <Card
              order={3}
              title="Start calculations"
              description={
                <p>
                  Molecule permeability will be computed using{" "}
                  <strong>our sources</strong>.
                </p>
              }
            />
            <Card
              order={4}
              title="Download results"
              description={
                <p>
                  Your lab will store the results, available for{" "}
                  <strong>download anytime</strong>.
                </p>
              }
            />
            <Card
              order={5}
              title="Be fair!"
              description={
                <p>
                  After a selected period of time, your results will become
                  publicly accessible in MolMeDB.
                </p>
              }
            />
          </div>
        </div>
        <div className="flex justify-end">
          <Button radius="full" variant="flat" color="primary" size="lg">
            Start now!
          </Button>
        </div>
      </div>
    </div>
  );
}

function Card(props: {
  order: number;
  title: string;
  description: JSX.Element;
}) {
  return (
    <motion.div
      initial={{ x: 100, opacity: 0 }}
      whileInView={{ x: 0, opacity: 1 }}
      transition={{ duration: 0.6, ease: "easeOut", delay: 0.4 * props.order }}
      viewport={{ once: true }}
      className="bg-white rounded-full py-4 px-6 lg:py-6 lg:px-8 flex flex-row gap-6 justify-start items-center"
    >
      <div>
        <div className="font-bold text-2xl lg:text-4xl text-white bg-secondary px-6 py-2 rounded-full">
          {props.order}
        </div>
      </div>
      <div className="flex flex-col gap-1 items-start justify-center">
        <h2 className="text-lg lg:text-xl font-bold text-foreground text-start">
          {props.title}
        </h2>
        {props.description}
      </div>
    </motion.div>
  );
}
