"use client";
import { Accordion, AccordionItem } from "@heroui/react";
// import { isRedirectError } from "next/dist/client/components/redirect";
import React, { ErrorInfo, JSX } from "react";
import { MdOutlineError } from "react-icons/md";
import ApiError from "./types/apiError";

/**
 * Default UI component wrapper
 * - Shows loader while component loading or error if any occurs
 */
class UIErrorCatcher extends React.Component<
  { children: JSX.Element },
  { hasError: boolean; message: string; exception: Error | ApiError | null }
> {
  /**
   * Constructor for UIErrorCatcher component.
   * @param {Object} props - The props object
   */
  constructor(props: { children: JSX.Element }) {
    // Calling super to invoke the constructor of the base class
    super(props);

    /**
     * The state object for the UIErrorCatcher component. It has a single property:
     * hasError: A boolean indicating whether an error has occurred or not.
     */
    this.state = {
      hasError: false,
      message: "",
      exception: null,
    }; // Initializes the state with 'hasError' as false
  }

  /**
   * A static method that returns a new state object with the hasError property set to true,
   * which will cause the next render to show the fallback UI.
   *
   * @param {Error} error - the error object that triggered the method
   * @return {object} the new state object with hasError set to true
   */
  static getDerivedStateFromError(error: Error | ApiError) {
    // Update state so the next render will show the fallback UI.
    return { hasError: true, message: error.message, exception: error };
  }

  /**
   * A lifecycle method that catches errors during rendering, in lifecycle methods, and in the constructor of the whole tree below them.
   *
   * @param {Error} error - The error that was thrown.
   * @param {ErrorInfo} info - A plain JavaScript object with a componentStack key containing information about which component threw the error.
   */
  componentDidCatch(error: Error | ApiError, info: ErrorInfo) {
    // if (isRedirectError(error)) {
    console.log(info);
    throw error;
    // }
  }

  render() {
    if (this.state.hasError) {
      return UIError({
        message: this.state.message,
        exception: this.state.exception,
      });
    }

    return this.props.children;
  }
}

/**
 * A function that renders an error UI component.
 *
 * @return {JSX.Element} The error UI component
 */
function UIError({
  message = "",
  exception = null,
}: {
  message: string;
  exception: Error | ApiError | null;
}): JSX.Element {
  const isApiError = message.includes("|:|");

  if (isApiError) {
    const t = message.split("|:|");
    message = t[1];
    exception = new ApiError(t[1], t[2], parseInt(t[3]), JSON.parse(t[4]));
  }

  return (
    <div role="alert" className="p-4">
      <div className="bg-red-600 text-white font-bold rounded-t px-4 py-2">
        {isApiError ? "Backend server error" : "Error"}
      </div>
      <div className="border border-t-0 border-red-600 rounded-b bg-red-100 px-4 py-3 text-red-600">
        <p>Something went wrong during rendering UI.</p>
        <Accordion>
          <AccordionItem
            key="1"
            startContent={<MdOutlineError size={30} />}
            aria-label="Accordion 1"
            subtitle="Click to show trace"
            title={`Backend error: ${message}`}
          >
            {isApiError && exception instanceof ApiError ? (
              <div>
                <p>
                  <strong>
                    <u>Line:</u>
                  </strong>{" "}
                  {exception!.line}
                </p>
                <p>
                  <strong>
                    <u>Filename:</u>
                  </strong>{" "}
                  {exception!.file}
                </p>
                <br />
                <p>
                  <strong>
                    <u>Trace:</u>
                  </strong>
                </p>
                {exception!.trace.map((tr: any, index: any) => {
                  return tr.line ? (
                    <p>
                      [Line <strong>{tr.line}</strong>]: {tr.file} in method{" "}
                      {exception!.trace.length > index + 1 ? (
                        <>
                          <strong>
                            {/* {exception!.trace[index + 1].class}
                            {exception!.trace[index + 1].type}
                            {exception!.trace[index + 1].function} */}
                          </strong>
                          {"("}
                          {/* {exception!.trace[index + 1].args.join(", ")} */}
                          {")."}
                        </>
                      ) : (
                        ""
                      )}
                    </p>
                  ) : (
                    <></>
                  );
                })}
              </div>
            ) : (
              <div>
                <p
                  dangerouslySetInnerHTML={{
                    __html: exception?.stack?.replaceAll("\n", "<br/>") ?? "",
                  }}
                ></p>
              </div>
            )}
          </AccordionItem>
        </Accordion>
      </div>
    </div>
  );
}

export default UIErrorCatcher;
