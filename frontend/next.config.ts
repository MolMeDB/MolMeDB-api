import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  /* config options here */
  allowedDevOrigins: ["localhost", "192.168.0.*"],
  output: "standalone",
  // swcMinify: true,
  // reactStrictMode: true,
};

export default nextConfig;
