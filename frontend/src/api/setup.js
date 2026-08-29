import { sanitizeWebsiteSetup } from "@/utils/sanitizeWebsiteSetup";
import { serverApiGet } from "@/utils/serverApiFetch";

const DEFAULT_LANGUAGE_CODE = "tr";

export default async function getSetupData() {
  try {
    const res = await serverApiGet(
      `website-setup?lang_code=${DEFAULT_LANGUAGE_CODE}`
    );
    const data = await res.json();
    return sanitizeWebsiteSetup(data);
  } catch {
    return null;
  }
}
