"use client";
import React, { useEffect, useState } from "react";
import { useSelector } from "react-redux";
import { useRouter } from "next/navigation";
import PageTitle from "./Helpers/PageTitle";
import ServeLangItem from "./Helpers/ServeLangItem";

function CustomPageCom({ slug }) {
  const { websiteSetup } = useSelector((state) => state.websiteSetup);
  const [pageData, setPageData] = useState(null);
  const router = useRouter();
  useEffect(() => {
    if (websiteSetup) {
      const checkPageIsExist = websiteSetup.payload.customPages.find((item) => {
        return item.slug === slug;
      });
      if (checkPageIsExist) {
        setPageData(checkPageIsExist);
      } else {
        router.push("/404");
      }
    }
  }, [pageData, slug, websiteSetup]);
  return (
    <div className="terms-condition-page w-full bg-white pb-[30px] min-h-screen">
      <div className="w-full mb-[30px]">
        {pageData && (
          <PageTitle
            breadcrumb={[
              { name: ServeLangItem()?.home, path: "/" },
              {
                name: pageData.slug,
                path: `/pages?custom=${pageData.slug}`,
              },
            ]}
            title={pageData.page_name}
          />
        )}
      </div>
      <div className="w-full">
        <div className="container-x mx-auto">
          {pageData && (
            <div
              dangerouslySetInnerHTML={{
                __html: pageData.description,
              }}
            ></div>
          )}
        </div>
      </div>
    </div>
  );
}

export default CustomPageCom;
