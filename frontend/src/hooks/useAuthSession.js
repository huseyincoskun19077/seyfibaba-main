import { useEffect, useState } from "react";
import { usePathname } from "next/navigation";
import { AUTH_STORAGE_SYNC_EVENT } from "@/redux/api/apiSlice";
import auth from "@/utils/auth";

export default function useAuthSession() {
  const pathname = usePathname() ?? "";
  const [session, setSession] = useState(null);

  useEffect(() => {
    setSession(auth());
  }, [pathname]);

  useEffect(() => {
    const syncSession = () => setSession(auth());

    window.addEventListener(AUTH_STORAGE_SYNC_EVENT, syncSession);
    window.addEventListener("storage", syncSession);

    return () => {
      window.removeEventListener(AUTH_STORAGE_SYNC_EVENT, syncSession);
      window.removeEventListener("storage", syncSession);
    };
  }, []);

  return session;
}
