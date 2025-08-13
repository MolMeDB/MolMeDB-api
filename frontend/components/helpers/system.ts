/**
 * Checks, if code is running server-side
 *
 * @returns {boolean}
 */
export function isServerSide(): boolean {
  return typeof window === "undefined";
}
