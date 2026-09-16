"use client";

import { useEffect, useState } from "react";

export default function Error404() {
  const [active, setActive] = useState(false);

  useEffect(() => {
    setActive(true);
  }, []);

  return (
    <div
      className={`
        cont_principal relative w-full h-screen overflow-hidden bg-[#D4D9ED]
        ${active ? "cont_error_active" : ""}
      `}
    >
      <div className="cont_error absolute w-full h-[300px] top-1/2 -translate-y-2/3 text-center">
        <h1 className="text-[150px] font-light text-white relative left-[-100%] transition-all duration-500">
          Oops!
        </h1>
        <p className="text-[24px] font-thin text-[#6d6f81] tracking-widest relative left-[100%] transition-all duration-500 delay-500">
          The Page you're looking for isn't here. Please, check the URL address.
        </p>
      </div>

      <div className="cont_aura_1 absolute w-[300px] h-[120%] top-[25px] right-[-340px] bg-[#8A65DF] shadow-[0_0_60px_20px_rgba(137,100,222,0.5)] transition-all duration-500"></div>

      <div className="cont_aura_2 absolute w-full h-[300px] bottom-[-301px] right-[-10%] bg-[#8B65E4] shadow-[0_0_60px_10px_rgba(131,95,214,0.5),0_0_20px_0_rgba(0,0,0,0.1)] z-10 transition-all duration-500"></div>
    </div>
  );
}
