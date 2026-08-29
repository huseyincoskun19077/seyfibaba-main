import { STORAGE_KEYS } from "@/utils/layoutConstants";

export default function settings() {
  if (typeof window !== "undefined") {
    if (localStorage.getItem(STORAGE_KEYS.SETTINGS)) {
      return JSON.parse(localStorage.getItem(STORAGE_KEYS.SETTINGS));
    }
    return false;
  }
  return false;
}
