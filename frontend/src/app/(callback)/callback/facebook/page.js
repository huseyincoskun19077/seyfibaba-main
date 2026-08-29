import FacebookCallbackClient from "./FacebookCallbackClient";

export const metadata = {
  title: "Facebook Giris Yonlendirmesi | Seyfibaba",
  robots: { index: false, follow: false },
  alternates: {
    canonical: "/callback/facebook",
  },
};

export default function FacebookPage() {
  return <FacebookCallbackClient />;
}
