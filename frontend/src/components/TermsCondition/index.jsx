"use client";
import PageTitle from "../Helpers/PageTitle";
import ServeLangItem from "../Helpers/ServeLangItem";

export default function TermsCondition({ datas }) {
  return (
    <div className="terms-condition-page w-full bg-white pb-[30px] min-h-screen">
      <div className="w-full mb-[30px]">
        <PageTitle
          breadcrumb={[
            { name: ServeLangItem()?.home, path: "/" },
            {
              name: ServeLangItem()?.Term_and_Conditions,
              path: "/terms-conditions",
            },
          ]}
          title="Kullanim Kosullari"
        />
      </div>
      <div className="w-full">
        <div
          className="container-x mx-auto"
          dangerouslySetInnerHTML={{
            __html: datas,
          }}
        ></div>
      </div>
    </div>
  );
}
