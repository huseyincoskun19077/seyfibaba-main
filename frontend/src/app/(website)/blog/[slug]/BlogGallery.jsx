"use client";
import { useState } from "react";
import Image from "next/image";
import appConfig from "@/appConfig";

export default function BlogGallery({ gallery }) {
  const [selectedIndex, setSelectedIndex] = useState(null);

  if (!gallery || gallery.length === 0) return null;

  return (
    <>
      {/* Gallery Grid */}
      <div className="mt-10 mb-6">
        <h3 className="text-xl font-semibold text-qblack mb-4">Görseller</h3>
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
          {gallery.map((item, index) => (
            <button
              key={item.id}
              type="button"
              onClick={() => setSelectedIndex(index)}
              className="relative aspect-square rounded-xl overflow-hidden border border-gray-100 hover:border-qyellow hover:shadow-md transition-all group"
            >
              <Image
                src={`${appConfig.BASE_URL}${item.image}`}
                alt={`Görsel ${index + 1}`}
                fill
                className="object-cover group-hover:scale-105 transition-transform duration-300"
                sizes="(max-width: 768px) 50vw, 25vw"
              />
            </button>
          ))}
        </div>
      </div>

      {/* Lightbox */}
      {selectedIndex !== null && (
        <div
          className="fixed inset-0 z-[9999] bg-black/90 flex items-center justify-center"
          onClick={() => setSelectedIndex(null)}
        >
          {/* Close */}
          <button
            type="button"
            onClick={() => setSelectedIndex(null)}
            className="absolute top-4 right-4 text-white text-3xl font-bold z-10 w-10 h-10 flex items-center justify-center rounded-full bg-black/50 hover:bg-black/80"
          >
            &times;
          </button>

          {/* Prev */}
          {selectedIndex > 0 && (
            <button
              type="button"
              onClick={(e) => {
                e.stopPropagation();
                setSelectedIndex(selectedIndex - 1);
              }}
              className="absolute left-4 top-1/2 -translate-y-1/2 text-white text-4xl z-10 w-12 h-12 flex items-center justify-center rounded-full bg-black/50 hover:bg-black/80"
            >
              &#8249;
            </button>
          )}

          {/* Next */}
          {selectedIndex < gallery.length - 1 && (
            <button
              type="button"
              onClick={(e) => {
                e.stopPropagation();
                setSelectedIndex(selectedIndex + 1);
              }}
              className="absolute right-4 top-1/2 -translate-y-1/2 text-white text-4xl z-10 w-12 h-12 flex items-center justify-center rounded-full bg-black/50 hover:bg-black/80"
            >
              &#8250;
            </button>
          )}

          {/* Image */}
          <div
            className="relative w-[90vw] h-[80vh] max-w-5xl"
            onClick={(e) => e.stopPropagation()}
          >
            <Image
              src={`${appConfig.BASE_URL}${gallery[selectedIndex].image}`}
              alt={`Görsel ${selectedIndex + 1}`}
              fill
              className="object-contain"
              sizes="90vw"
              priority
            />
          </div>

          {/* Counter */}
          <div className="absolute bottom-4 left-1/2 -translate-x-1/2 text-white/70 text-sm">
            {selectedIndex + 1} / {gallery.length}
          </div>
        </div>
      )}
    </>
  );
}
