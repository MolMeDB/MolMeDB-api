const enabled = process.env.NEXT_PUBLIC_API_LOGGING === "true";

type LogLevel = "info" | "warn" | "error";

function log(level: LogLevel, ...args: unknown[]) {
  if (!enabled) return;

  const prefix = `[API][${level.toUpperCase()}]`;
  if (level === "error") {
    console.error(prefix, ...args);
  } else if (level === "warn") {
    console.warn(prefix, ...args);
  } else {
    console.log(prefix, ...args);
  }
}

const logger = {
  info: (...args: unknown[]) => log("info", ...args),
  warn: (...args: unknown[]) => log("warn", ...args),
  error: (...args: unknown[]) => log("error", ...args),
};

export default logger;
