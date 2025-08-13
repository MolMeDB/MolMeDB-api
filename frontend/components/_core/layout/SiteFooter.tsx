import Image from "next/image";
import Link from "next/link";

export default function SiteFooter() {
  return (
    <footer className="relative text-footer-2 w-full min-h-footer xl:h-footer text-white">
      <div className="absolute inset-0 bg-[#2a2a2a] z-0 " />
      <div className="relative p-12 xl:py-20 flex flex-row justify-center z-20 h-full w-full">
        <div className="h-full flex flex-col xl:flex-row justify-center items-center w-full lg:w-maxPage gap-8 xl:gap-6 lg:gap-12">
          <div className="flex flex-col sm:flex-row xl:flex-col justify-center sm:items-center xl:items-start gap-2 md:gap-8 xl:gap-1 xl:w-1/3 xl:w-[330px] w-full xl:h-full">
            <Image
              src="/assets/layout/logo/up-horizontal-white.png"
              alt="Palacky University Olomouc"
              width={400}
              height={300}
              className="w-full sm:w-1/2 xl:w-full h-auto pb-1"
            />
            <div className="flex flex-col gap-0 text-sm text-white xl:pl-6 text-semibold">
              <p>Palacky University Olomouc</p>
              <p>Faculty of Science</p>
              <p>Department of Physical Chemistry</p>
              <p>tř. 17. listopadu 1192/12</p>
              <p>771 46 Olomouc</p>
              <p>Czech Republic</p>
            </div>
          </div>
          {/* DIVIDER */}
          <Divider />
          <div className="w-full xl:w-3/12 xl:w-[330px] flex flex-col gap-8">
            <div className="flex flex-col gap-2">
              <h3 className="text-lg font-bold">How to cite?</h3>
              <p className="text-sm">
                Juračka J., Šrejber M., Melíková M., Bazgier V., Berka K.:
                MolMeDB: Molecules on Membranes Database. Database, Volume 2019,
                2019, baz078,{" "}
                <a
                  target="_blank"
                  href="https://doi.org/10.1093/database/baz078"
                >
                  https://doi.org/10.1093/database/baz078
                </a>
              </p>
            </div>
            <div className="flex flex-col gap-2">
              <h3 className="text-lg font-bold">Follow us</h3>
              <div className="flex flex-row gap-3">
                <Link target="_blank" href="https://www.facebook.com/molmedb">
                  <Image
                    src="/assets/layout/logo/facebook.png"
                    alt="Facebook"
                    width={25}
                    height={25}
                    className="h-auto"
                  />
                </Link>
                <Link target="_blank" href="https://github.com/MolMeDB/MolMeDB">
                  <Image
                    src="/assets/layout/logo/github.png"
                    alt="Github"
                    width={25}
                    height={25}
                    className="h-auto"
                  />
                </Link>
              </div>
            </div>
          </div>
          <Divider />
          <div className="flex flex-col w-full xl:w-1/3 xl:w-[330px] gap-2">
            <h3 className="text-lg font-bold">Financial Support</h3>
            <ul className="list-disc pl-6 text-sm">
              <li>
                GAČR 17-2112S (Principal investigator: prof. RNDr. Karel Berka,
                Ph.D.)
              </li>
              <li>
                Palacky University Olomouc (projects IGA_PrF_2018_032 and
                IGA_2019_031)
              </li>
              <li>ELIXIR-CZ (projects LM2015047 and LM2018131)</li>
            </ul>
            <div className="flex flex-row justify-end gap-4 mt-4">
              <Image
                src="/assets/layout/logo/elixir.png"
                alt="ELIXIR"
                width={100}
                height={50}
                className="h-auto"
              />
              <Image
                src="/assets/layout/logo/gacr.svg"
                alt="GAČR"
                style={{
                  filter:
                    "invert(50%) saturate(2414%) hue-rotate(67deg) brightness(200%) contrast(119%)",
                }}
                width={100}
                height={100}
              />
              <Image
                src="/assets/layout/logo/up-e-white.png"
                alt="UP Olomouc"
                width={100}
                height={50}
                className="h-auto"
              />
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
}

function Divider() {
  return (
    <div className="h-[2px] xl:h-full w-full xl:w-[2px] bg-gray-losing-90 xl:bg-gray-losing z-20"></div>
  );
}
