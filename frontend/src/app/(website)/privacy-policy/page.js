import { redirect } from "next/navigation";

export default function PrivacyPolicyRedirectPage() {
  redirect("/legal/privacy-policy");
}
