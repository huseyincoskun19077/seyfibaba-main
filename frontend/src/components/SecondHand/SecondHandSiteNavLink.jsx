"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import {
  isLocalHost,
  isMarketplaceHost,
  isSecondHandHost,
} from "@/utils/secondHandSite";

export default function SecondHandSiteNavLink({
  className,
  children,
  ariaLabel,
}) {
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const host = window.location.hostname;
    setVisible(isSecondHandHost(host) || isLocalHost(host));
  }, []);

  if (!visible) {
    return null;
  }

  return (
    <Link href="/ikinci-el" className={className} aria-label={ariaLabel}>
      {children}
    </Link>
  );
}

export function useHideMarketplaceSecondHandNav() {
  const [hide, setHide] = useState(true);

  useEffect(() => {
    const host = window.location.hostname;
    setHide(isMarketplaceHost(host));
  }, []);

  return hide;
}
