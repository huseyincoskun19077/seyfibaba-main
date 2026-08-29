import { Suspense } from "react";
import DefaultLayoutClient from "./DefaultLayoutClient";

function LayoutSkeleton() {
  return (
    <div className="w-full min-h-screen bg-white animate-pulse">
      <div className="h-[40px] bg-gray-100" />
      <div className="h-[80px] bg-gray-50 border-b" />
      <div className="h-[50px] bg-qyellow/30" />
      <div className="container-x mx-auto mt-6 space-y-4">
        <div className="h-[400px] bg-gray-100 rounded-xl" />
        <div className="grid grid-cols-4 gap-4">
          {[...Array(4)].map((_, i) => (
            <div key={i} className="h-[200px] bg-gray-100 rounded-lg" />
          ))}
        </div>
      </div>
    </div>
  );
}

export default function DefaultLayout({ children }) {
  return (
    <Suspense fallback={<LayoutSkeleton />}>
      <DefaultLayoutClient>{children}</DefaultLayoutClient>
    </Suspense>
  );
}
