import Signup from "@/components/Auth/Signup/index";

// generate seo metadata
export async function generateMetadata() {
  return {
    title: "Kayıt Ol",
    description: "Seyfibaba'ya ücretsiz kayıt olun. Binlerce ürüne erişin, alışverişe hemen başlayın.",
    alternates: {
      canonical: "/signup",
    },
    robots: { index: false, follow: true },
  };
}
// main page
export default function signupPage() {
  return <Signup />;
}
