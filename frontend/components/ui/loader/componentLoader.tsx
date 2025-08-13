import React, { JSX } from "react";

/**
 * ComponentLoader function to render a loading spinner component.
 *
 * @return {JSX.Element} The loading spinner component
 */
export default function ComponentLoader(): JSX.Element {
  const el = (
    <div className="p-4 w-full space-y-4 md:space-y-4 animate-pulse">
      <div className="flex flex-row space-x-4">
        <div className="w-2/6 max-h-3/6 h-24 bg-gray-200 rounded"></div>
        <div className="flex flex-col justify-center space-y-2 w-4/6">
          <div className="h-4 bg-gray-200 rounded w-full"></div>
          <div className="h-4 bg-gray-200 rounded w-2/4"></div>
        </div>
      </div>
      <div className="space-y-2">
        <div className="h-4 bg-gray-200 rounded w-3/4"></div>
        <div className="h-4 bg-gray-200 rounded w-1/2"></div>
      </div>
    </div>
  );

  return (
    <div className="md:p-0 pt-16 w-full h-full flex flex-row wrap overflow-hidden">
      {el}
    </div>
  );
}
