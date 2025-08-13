"use client";
import { Button } from "@heroui/react";
import Image from "next/image";

export default function IntroductionSection() {
  return (
    <div className="relative w-full min-h-[650px] mx-auto lg:min-h-auto overflow-hidden p-8 sm:p-16 lg:p-24 xl:px-56 xl:py-32 2xl:px-64 2xl:py-32">
      <div
        className="absolute inset-0
            bg-gradient-to-br from-intro-blue via-intro-purple to-intro-pink z-10 block sm:hidden"
        style={{
          clipPath: "polygon(0 0, 100% 0, 100% 18%, 0 16%)",
        }}
      ></div>
      <div
        className="absolute inset-0
            bg-gradient-to-br from-intro-blue via-intro-purple to-intro-pink z-10 hidden sm:block lg:hidden"
        style={{
          clipPath: "polygon(0 0, 100% 0, 100% 22%, 0 17%)",
        }}
      ></div>
      <div
        className="absolute inset-0
            bg-gradient-to-br from-intro-blue via-intro-purple to-intro-pink z-10 hidden lg:block xl:hidden"
        style={{
          clipPath: "polygon(0 0, 100% 0, 100% 35%, 0 25%)",
        }}
      ></div>
      <div
        className="absolute inset-0
            bg-gradient-to-br from-intro-blue via-intro-purple to-intro-pink z-10 hidden xl:block"
        style={{
          clipPath: "polygon(0 0, 100% 0, 100% 40%, 0 27%)",
        }}
      ></div>
      <div className="relative z-10 flex flex-col gap-16 lg:grid lg:grid-rows-[300px_auto_auto] grid-cols-1 lg:grid-cols-2 gap-32 gap-y-32 h-full pt-8 lg:pt-20">
        {/* 1. řádek, 1. sloupec: Logo + text */}
        <div className="lg:row-start-1 lg:col-start-1 flex items-start justify-start text-white w-full">
          <div className="flex flex-col gap-4 w-full">
            <div className="h-full max-w-[250px] lg:max-w-[350px] xl:max-w-[450px]">
              <Image
                src="/assets/layout/logo/molmedb-white.svg"
                alt="MolMeDB logo"
                priority
                className="w-full h-auto"
                width={0}
                height={0}
                sizes="100vw"
              />
            </div>
            <h1 className="text-lg md:text-xl font-bold text-white lg:max-w-xl">
              MolMeDB is an open chemical database on the behaviour of (small)
              molecules on biological membranes.
            </h1>
          </div>
        </div>

        {/* 2. řádek, 1. sloupec: Nadpis + text */}
        <div className="row-start-2 col-start-1 flex flex-col justify-center text-foreground gap-6">
          <h1 className="text-2xl md:text-3xl font-bold leading-tight">
            We study behavior of <br /> molecules on membranes...
          </h1>
          <p className="text-lg lg:mt-4 lg:max-w-xl text-left text-semibold text-left sm:text-justify lg:text-left">
            Understanding how molecules interact with membranes is essential for
            evaluating their biological activity, bioavailability, and
            pharmacokinetics. These interactions can be expressed by
            partitioning, permeability, or positioning, and can be obtained from
            experimental measurements, molecular simulations, or computational
            prediction methods.
          </p>
          <div className="mt-4 flex flex-row justify-end lg:justify-start">
            <Button radius="full" variant="flat" color="primary" size="lg">
              Discover
            </Button>
          </div>
        </div>

        {/* 1. a 2. řádek, 2. sloupec: Obrázek přes oba řádky */}
        <div className="row-span-2 col-start-2 w-full h-full items-start justify-end hidden lg:flex">
          <Image
            src="/assets/layout/intro/Intro2.svg"
            alt="MolMeDB logo"
            priority
            className="w-auto h-full"
            width={0}
            height={0}
            sizes="100vw"
          />
        </div>

        <div className="row-start-3 col-1 w-full h-full hidden lg:flex items-center justify-start">
          <Image
            src="/assets/layout/intro/protein-mol.png"
            alt="Protein on membrane"
            priority
            className="w-full max-w-2xl h-auto"
            width={0}
            height={0}
            sizes="100vw"
          />
        </div>

        <div className="row-start-3 col-2 flex flex-row lg:justify-end">
          <div className="flex flex-col justify-center gap-6">
            <h1 className="text-3xl font-bold leading-tight">
              …and molecule interactions with transporter proteins
            </h1>
            <p className="text-lg lg:mt-4 w-full lg:max-w-xl text-left text-semibold text-left sm:text-justify lg:text-left">
              However, membranes themselves are only part of the story. Membrane
              proteins—including transporters, ion channels, receptors, and
              other proteins—actively regulate cellular uptake, efflux, and
              signalling. Understanding both passive and protein-mediated
              transport is essential for predicting drug efficacy, toxicity, and
              selectivity. The MolMeDB combines these aspects to facilitate a
              deeper understanding of molecular behaviour in organisms.
            </p>
            <div className="row-start-3 col-1 w-full lg:hidden h-full flex items-start justify-end">
              <Image
                src="/assets/layout/intro/protein-mol.png"
                alt="Protein on membrane"
                priority
                className="w-full  sm:w-3/4 max-w-2xl h-auto"
                width={0}
                height={0}
                sizes="100vw"
              />
            </div>
            <div className="flex flex-row justify-end lg:mt-4 w-full lg:max-w-xl">
              <Button radius="full" variant="flat" color="primary" size="lg">
                Discover
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
