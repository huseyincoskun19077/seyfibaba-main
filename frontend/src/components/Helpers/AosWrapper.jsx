"use client";
import { useEffect } from "react";

function AosWrapper({ children }) {
  useEffect(() => {
    const isMobile = window.matchMedia("(max-width: 768px)").matches;
    if (isMobile) return;

    import("aos/dist/aos.css");
    import("aos").then((AOS) => {
      AOS.init({ once: true });
    });
  }, []);
  return children;
}

export default AosWrapper;
