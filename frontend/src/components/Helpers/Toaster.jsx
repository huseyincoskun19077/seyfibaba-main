"use client";
import { useEffect, useState } from "react";

export default function Toaster() {
  const [Container, setContainer] = useState(null);

  useEffect(() => {
    import("react-toastify/dist/ReactToastify.css");
    import("react-toastify").then((mod) => {
      setContainer(() => mod.ToastContainer);
    });
  }, []);

  if (!Container) return null;
  return <Container />;
}
