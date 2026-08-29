import settings from "./../../utils/settings";

function Multivendor() {
  const settingsData = settings();
  if (!settingsData) return null;
  const { enable_multivendor } = settingsData;
  return enable_multivendor ? parseInt(enable_multivendor) : null;
}

export default Multivendor;
