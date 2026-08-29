"use client";
import { useState, useEffect } from "react";

export default function ScrollToTop() {
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const onScroll = () => setVisible(window.scrollY > 400);
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  if (!visible) return null;

  return (
    <button
      onClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}
      type="button"
      aria-label="Sayfa üstüne çık"
      className="fixed bottom-6 right-6 z-40 w-10 h-10 rounded-full bg-qyellow text-qblack shadow-lg flex items-center justify-center hover:bg-qblack hover:text-white transition-colors duration-200"
    >
      <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M8 2L2 8.5H5.5V14H10.5V8.5H14L8 2Z" fill="currentColor" />
      </svg>
    </button>
  );
}
