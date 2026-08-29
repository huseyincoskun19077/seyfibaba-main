"use client";
import PageTitle from "../Helpers/PageTitle";
import ServeLangItem from "../Helpers/ServeLangItem";

export default function PrivacyPolicy({ datas }) {
  const { privacy_policy } = datas;
  return (
    <div className="terms-condition-page w-full bg-white pb-[30px] min-h-screen">
      <div className="w-full mb-[30px]">
        <PageTitle
          breadcrumb={[
            { name: ServeLangItem()?.home, path: "/" },
            { name: ServeLangItem()?.Privacy_Policy, path: "privacy-policy" },
          ]}
          title="Gizlilik Politikasi"
        />
      </div>
      <div className="w-full">
        <div className="container-x mx-auto">
          {privacy_policy && (
            <div
              dangerouslySetInnerHTML={{
                __html: privacy_policy,
              }}
            ></div>
          )}
        </div>
      </div>
    </div>
  );
}
