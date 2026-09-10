import nextCoreWebVitals from "eslint-config-next/core-web-vitals";
import nextTypescript from "eslint-config-next/typescript";

const eslintConfig = [
  // Vendored/generated static assets, not source to lint.
  { ignores: ["public/**"] },
  ...nextCoreWebVitals,
  ...nextTypescript,
  {
    rules: {
      // Pre-existing, widespread in this codebase (in ~40-50 spots each)
      // and not something to blanket-fix as a side effect of introducing
      // lint — downgraded to warn so they're visible without blocking CI.
      // Revisit tightening these once addressed deliberately.
      "react-hooks/set-state-in-effect": "warn",
      "react-hooks/exhaustive-deps": "warn",
    },
  },
];

export default eslintConfig;
