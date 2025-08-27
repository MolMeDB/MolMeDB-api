import type { Config } from "tailwindcss";
import { ColorScale, heroui } from "@heroui/react";

// const primary: ColorScale = {
//   50: "#FFEDCC",
//   100: "#FFEDCC",
//   200: "#FFD699",
//   300: "#FFBA66",
//   400: "#FF9E3F",
//   500: "#FF7000",
//   600: "#DB5400",
//   700: "#B73C00",
//   800: "#932800",
//   900: "#932800",
// };

const config: Config = {
  content: [
    "./pages/**/*.{js,ts,jsx,tsx,mdx}",
    "./components/**/*.{js,ts,jsx,tsx,mdx}",
    "./app/**/*.{js,ts,jsx,tsx,mdx}",
    "./node_modules/@heroui/theme/dist/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        // primary: "#4b52e8",
        secondary: "#7c41f2",
        ternary: "#fea500",
        "intro-blue": "#004aad",
        "intro-blue-dark": "#151A24",
        "intro-purple": "#7d4be5",
        "intro-purple-dark": "#46526c",
        "intro-pink": "#cd85ec",
        "intro-pink-dark": "#4d5973",
        dark: "#222",
        "primary-foreground": "#fff",
        background: "#f9f9f9",
        "background-dark": "#252b3b",
        "background-dark-2": "#46526c",
        "background-menu-dark": "#161921",
        "big-delimiter": "#6a50d9",
        "big-delimiter-dark": "#4d5973",
      },
      width: {
        maxPage: "1200px",
      },
      maxWidth: {
        maxPage: "1400px",
      },
      minHeight: {
        800: "800px",
      },
      height: {
        footer: "500px",
      },
      padding: {
        xs: "12px",
        sm: "16px",
        md: "20px",
        lg: "24px",
        xl: "32px",
      },
    },
  },
  darkMode: "media",
  plugins: [
    heroui({
      themes: {
        light: {
          colors: {
            primary: {
              // ...primary,
              foreground: "#000",
              // DEFAULT: primary[500],
            },
          },
        },
        dark: {
          colors: {
            primary: {
              // ...primary,
              // foreground: "#FFFFFF",
              // DEFAULT: primary[500],
            },
          },
        },
      },
    }),
  ],
};
export default config;
