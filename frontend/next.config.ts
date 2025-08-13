import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  /* config options here */
  allowedDevOrigins: ["http://localhost*", "192.168.0.*"],
};

export default nextConfig;
