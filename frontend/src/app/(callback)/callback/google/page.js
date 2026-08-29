import GoogleCallbackClient from "./GoogleCallbackClient";

export const metadata = {
  title: "Google Giris Yonlendirmesi | Seyfibaba",
  robots: { index: false, follow: false },
  alternates: {
    canonical: "/callback/google",
  },
};

export default function GooglePage() {
  return <GoogleCallbackClient />;
}
