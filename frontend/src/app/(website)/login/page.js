import Login from "../../../components/Auth/Login/index";

export const dynamic = "force-dynamic";

// generate seo metadata
export async function generateMetadata() {
  return {
    title: "Giriş Yap",
    description: "Seyfibaba hesabınıza giriş yapın. Siparişlerinizi takip edin, favorilerinizi yönetin.",
    alternates: {
      canonical: "/login",
    },
    robots: { index: false, follow: true },
  };
}

// main page
export default async function login() {
  return <Login />;
}
