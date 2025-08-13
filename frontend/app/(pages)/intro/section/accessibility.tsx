"use client";

import Image from "next/image";
import { motion } from "framer-motion";
import { JSX } from "react";

export default function AccessibilitySection() {
  return (
    <div className="relative w-full min-h-[650px] lg:min-h-auto overflow-hidden p-8 sm:p-16 lg:p-16 lg:py-24 xl:p-28 bg-zinc-200">
      <div className="flex flex-col gap-8 sm:gap-16 lg:gap-24">
        <h1 className="text-3xl lg:text-4xl font-bold text-foreground text-center">
          Open and easily accessible
        </h1>
        <div className="flex flex-col lg:grid lg:grid-cols-3 gap-8 xl:gap-16">
          <Card
            image="/assets/layout/intro/accessibility/web.png"
            title="Web interface"
            description={
              <p className="text-center text-lg">
                Browse and download the data through a modern, user-friendly
                interface — <strong>no login required</strong>.
              </p>
            }
          />
          <Card
            image="/assets/layout/intro/accessibility/api.png"
            title="Modern API"
            description={
              <p className="text-center text-lg">
                Easily integrate the data into your applications using a clean
                and well-documented <strong>REST API</strong>.
              </p>
            }
          />
          <Card
            image="/assets/layout/intro/accessibility/sparql.png"
            title="Sparql queries"
            description={
              <p className="text-center text-lg">
                Query the data directly with <strong>SPARQL</strong> — a
                powerful option for advanced and flexible access.
              </p>
            }
          />
        </div>
      </div>
    </div>
  );
}

function Card(props: {
  image: string;
  title: string;
  description: JSX.Element;
}) {
  return (
    <motion.div
      initial={{ y: 100, opacity: 0 }}
      whileInView={{ y: 0, opacity: 1 }}
      transition={{ duration: 0.6, ease: "easeOut" }}
      viewport={{ once: true }}
      className="bg-white rounded-xl p-8 flex flex-col gap-6 justift-center pb-16"
    >
      <div className="lg:max-h-[140px] flex justify-center mb-2">
        <Image
          src={props.image}
          alt={props.title}
          width={0}
          height={0}
          sizes="100vh"
          className="w-1/3 sm:w-1/4 h-auto lg:w-auto lg:h-full"
        />
      </div>
      <h2 className="text-2xl font-bold text-foreground text-center">
        {props.title}
      </h2>
      {props.description}
    </motion.div>
  );
}
