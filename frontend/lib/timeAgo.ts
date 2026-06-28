/**
 * Parse a timestamp string as UTC.
 *
 * Laravel returns timestamps as "2026-06-28 13:42:06" — no timezone suffix,
 * space instead of "T". Browsers that parse this as local time produce wrong
 * relative timestamps for non-UTC users. We force UTC interpretation.
 */
function parseUtc(value: string): Date {
  // Already has timezone info (Z, +HH:MM, -HH:MM) — use as-is
  if (/[Zz]$/.test(value) || /[+-]\d{2}:\d{2}$/.test(value)) {
    return new Date(value);
  }
  // Normalize: "2026/06/28 13:42:06" or "2026-06-28 13:42:06" → "2026-06-28T13:42:06Z"
  const normalized = value.replace(/\//g, "-").replace(" ", "T") + "Z";
  return new Date(normalized);
}

/**
 * Convert a UTC timestamp string to a human-friendly relative time string.
 * Returns "-" for null/undefined/invalid values.
 */
export function timeAgo(value: string | null | undefined): string {
  if (!value) return "-";

  const date = parseUtc(value);
  if (isNaN(date.getTime())) return value;

  const diffMs = Date.now() - date.getTime();
  const diffSeconds = Math.floor(diffMs / 1000);

  if (diffSeconds < 60) return "< 1 minute ago";

  const diffMinutes = Math.floor(diffSeconds / 60);
  if (diffMinutes < 60) {
    return diffMinutes === 1 ? "1 minute ago" : `${diffMinutes} minutes ago`;
  }

  const diffHours = Math.floor(diffMinutes / 60);
  if (diffHours < 24) {
    return diffHours === 1 ? "1 hour ago" : `${diffHours} hours ago`;
  }

  const diffDays = Math.floor(diffHours / 24);
  if (diffDays < 30) {
    return diffDays === 1 ? "1 day ago" : `${diffDays} days ago`;
  }

  const diffMonths = Math.floor(diffDays / 30);
  if (diffMonths < 12) {
    return diffMonths === 1 ? "1 month ago" : `${diffMonths} months ago`;
  }

  const diffYears = Math.floor(diffMonths / 12);
  return diffYears === 1 ? "1 year ago" : `${diffYears} years ago`;
}
