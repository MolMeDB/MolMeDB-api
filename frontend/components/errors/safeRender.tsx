import React, { JSX, Suspense } from "react";
import UIErrorCatcher from "./errorBoundary";
import ComponentLoader from "../ui/loader/componentLoader";

/**
 * SafeRenderer function renders the children inside a UIErrorCatcher component with fallback support.
 *
 * @param {JSX.Element} children - The JSX element to be rendered.
 * @param {JSX.Element} [fallback] - Optional JSX element to be rendered if the children are not yet available.
 * @return {React.JSX.Element} The rendered JSX element.
 */
export default function SafeRenderer({
  children,
  fallback = undefined,
}: {
  children: JSX.Element;
  fallback?: JSX.Element;
}): React.JSX.Element {
  return (
    <UIErrorCatcher>
      <Suspense fallback={fallback ?? <ComponentLoader />}>{children}</Suspense>
    </UIErrorCatcher>
  );
}
