"use client";
import Maintain from "../Maintain";
import { useSelector } from "react-redux";
import { useEffect, useState } from "react";

function MaintenanceWrapper({ children }) {
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const [mode, setMode] = useState(null);
  useEffect(() => {
    if (websiteSetup) {
      if (websiteSetup.payload) {
        if (websiteSetup.payload.maintainance) {
          setMode(Number(websiteSetup.payload.maintainance.status));
        }
      }
    }
  }, [websiteSetup]);
  if (mode === 1) {
    return <Maintain />;
  }
  return children;
}

export default MaintenanceWrapper;
