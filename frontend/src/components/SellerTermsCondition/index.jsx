"use client";
import PageTitle from "../Helpers/PageTitle"; 
import ServeLangItem from "../Helpers/ServeLangItem";

export default function TermsCondition({ datas }) {
  const { seller_condition } = datas;
  return (
    <div className="terms-condition-page w-full bg-white pb-[30px] min-h-screen">
      <div className="w-full mb-[30px]">
        <PageTitle
          breadcrumb={[
            { name: ServeLangItem()?.home, path: "/" },
            {
              name: ServeLangItem()?.Seller_terms_and_conditions,
              path: "/seller-terms-condition",
            },
          ]}
          title={ServeLangItem()?.Seller_terms_and_conditions}
        />
      </div>
      <div className="w-full">
        <div
          className="container-x mx-auto"
          dangerouslySetInnerHTML={{
            __html: seller_condition,
          }}
        ></div>
      </div>
    </div>
  );
}
