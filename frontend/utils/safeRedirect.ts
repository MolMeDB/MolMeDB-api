/**
 * Guards against open-redirect attacks: only allows same-origin, relative
 * paths (e.g. "/lab"). Rejects absolute URLs, protocol-relative URLs
 * ("//evil.com"), and anything containing a scheme ("javascript:", "https:").
 */
export function safeRedirectPath(path?: string | null): string | undefined {
  if (!path) {
    return undefined;
  }

  if (!path.startsWith("/") || path.startsWith("//") || path.startsWith("/\\")) {
    return undefined;
  }

  if (/^\/[a-zA-Z][a-zA-Z0-9+.-]*:/.test(path)) {
    return undefined;
  }

  return path;
}
