import React from "react";
import Profile from "@/components/Auth/Profile";

export const metadata = {
  title: "Profilim",
  robots: { index: false, follow: false },
  alternates: {
    canonical: "/profile",
  },
};

function ProfilePage() {
  return <Profile />;
}

export default ProfilePage;
