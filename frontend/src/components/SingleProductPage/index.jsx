"use client";
import dynamic from "next/dynamic";
import Link from "next/link";
import { useContext, useEffect, useMemo, useRef, useState } from "react";
import auth from "@/utils/auth";
import BreadcrumbCom from "../BreadcrumbCom";
import DataIteration from "../Helpers/DataIteration";
import InputCom from "../Helpers/InputCom";
import LoaderStyleOne from "../Helpers/Loaders/LoaderStyleOne";
import ProductView from "./ProductView";
import { buildProductPath } from "@/utils/url";
import { displayTurkishLabel } from "@/utils/turkishDisplay";
import Reviews from "./Reviews";
import SallerInfo from "./SallerInfo";
import ServeLangItem from "../Helpers/ServeLangItem";
import Video from "yet-another-react-lightbox/plugins/video";
import "yet-another-react-lightbox/styles.css";
import ProductCard from "../Helpers/Cards/ProductCard";
import appConfig from "@/appConfig";
import { resolveProductImageUrl } from "@/utils/productImage";
import LoginContext from "../Contexts/LoginContext";
import { useProductReportApiMutation } from "@/redux/features/product/apiSlice";
import { toast } from "react-toastify";
import Multivendor from "../Shared/Multivendor";

const Lightbox = dynamic(() => import("yet-another-react-lightbox"), {
  ssr: false,
});

const buildVideoSource = (video) => {
  const rawSource =
    video?.video_url ||
    video?.url ||
    video?.src ||
    video?.source ||
    video?.path ||
    "";

  if (!rawSource) {
    return null;
  }

  return rawSource.startsWith("http")
    ? rawSource
    : `${appConfig.BASE_URL}${rawSource}`;
};

export default function SingleProductPage({ details }) {
  const safeDetails = details || {};
  const safeProduct = safeDetails?.product || null;
  const productCategorySlug = safeProduct?.category?.slug;
  const productBrandSlug = safeProduct?.brand?.slug;
  const loginPopupBoard = useContext(LoginContext);
  const [open, setOpen] = useState(false);
  const [photoIndex, setIndex] = useState(0);
  const popupHandler = (value) => {
    setIndex(value);
    setOpen(!open);
  };
  const [tab, setTab] = useState("des");
  const reviewElement = useRef(null);
  const [report, setReport] = useState(false);
  const ReportHandler = () => {
    if (auth()) {
      setReport(!report);
    } else {
      loginPopupBoard.handlerPopup(true);
    }
  };

  //report state
  const [subject, setSubject] = useState("");
  const [description, setDescription] = useState("");
  const [reportErrors, setReportErrors] = useState(null);
  const [commnets, setComments] = useState(null);
  useEffect(() => {
    const maskWord = (word = "") => {
      const clean = String(word).trim();
      if (!clean) return "";
      const first = clean[0] || "";
      return first + "*".repeat(Math.max(0, clean.length - 1));
    };

    const maskFullName = (fullName = "") => {
      const parts = String(fullName).trim().split(/\s+/).filter(Boolean);
      if (parts.length === 0) return "";
      if (parts.length === 1) return maskWord(parts[0]);
      const first = maskWord(parts[0]);
      const last = maskWord(parts[parts.length - 1]);
      return `${first} ${last}`;
    };

    // Reset comments when product details change
    const reviews =
      safeDetails &&
      safeDetails.productReviews &&
      safeDetails.productReviews.length > 0 &&
      safeDetails.productReviews.map((review) => {
        const rawName = review?.user?.name || "";
        return {
          id: review.id,
          author: maskFullName(rawName),
          comments: review.review,
          review: parseInt(review.rating),
          replys: null,
          // Gizlilik: yorumlarda profil fotoğrafı göstermiyoruz
          image: null,
        };
      });
    setComments(reviews || []);

    // Reset tab to description for new products
    setTab("des");

    // Reset report-related states for new products
    setReport(false);
    setSubject("");
    setDescription("");
    setReportErrors(null);

    // Reset lightbox states for new products
    setIndex(0);
    setOpen(false);
  }, [safeDetails]);

  const sellerInfo =
    safeDetails.seller &&
    safeDetails.sellerTotalProducts !== undefined &&
    safeDetails.sellerTotalReview !== undefined
      ? {
          seller: {
            ...safeDetails.seller,
            sellerTotalProducts: parseInt(safeDetails.sellerTotalProducts, 10),
            sellerTotalReview: parseInt(safeDetails.sellerTotalReview, 10),
          },
        }
      : null;
  const safeVideoSlides = useMemo(() => {
    if (!Array.isArray(safeDetails?.videos)) {
      return [];
    }

    return safeDetails.videos
      .map((video) => buildVideoSource(video))
      .filter(Boolean)
      .map((videoUrl) => ({
        type: "video",
        width: 1280,
        height: 720,
        sources: [
          {
            src: videoUrl,
            type: "video/mp4",
          },
        ],
      }));
  }, [safeDetails?.videos]);
  const relatedProducts = safeDetails.relatedProducts
    ? safeDetails.relatedProducts.map((item) => {
        return {
          id: item.id,
          title: item.name,
          slug: item.slug,
          image: resolveProductImageUrl(item.thumb_image),
          price: item.price,
          offer_price: item.offer_price,
          campaingn_product: null,
          vendor_id: Number(item.vendor_id),
          review: parseInt(item.averageRating, 10),
          variants: item.active_variants,
          sale_unit_qty: item.sale_unit_qty,
        };
      })
    : [];

  /**
   * Product report functionality
   * @Initializing useProductReportApiMutation @const productReportApi
   * @func productReportSuccessHandler @params data , statusCode
   * @func productReportErrorHandler @params error
   * @func productReport @params id
   */
  const [productReportApi, { isLoading: reportLoading }] =
    useProductReportApiMutation();

  const productReportSuccessHandler = (data, statusCode) => {
    if (statusCode === 200 || statusCode === 201) {
      setReportErrors(null);
      setSubject("");
      setDescription("");
      setReport(!report);
      toast.success(data?.message);
    } else {
      toast.warning("Bir hata olustu");
    }
  };

  const productReportErrorHandler = (error) => {
    setReportErrors(error.data && error?.data?.errors);
  };
  const productReport = async (id) => {
    if (!id) {
      toast.error("Ürün bulunamadı.");
      return;
    }

    if (auth()) {
      const userToken = auth()?.access_token;
      await productReportApi({
        data: {
          subject: subject,
          description: description,
          product_id: id,
        },
        token: userToken,
        success: productReportSuccessHandler,
        error: productReportErrorHandler,
      });
    }
  };

  return (
    <>
      <div key={safeProduct?.id || "product"} className="single-product-wrapper w-full ">
        <div className="product-view-main-wrapper bg-white pt-[30px] w-full">
          <div className="breadcrumb-wrapper w-full ">
            <div className="container-x mx-auto">
              <BreadcrumbCom
                paths={[
                  { name: ServeLangItem()?.home, path: "/" },
                  ...(safeProduct?.category?.name && productCategorySlug
                    ? [
                        {
                          name: displayTurkishLabel(safeProduct.category.name),
                          path: `/products?category=${productCategorySlug}`,
                        },
                      ]
                    : []),
                  {
                    name: safeProduct?.name || safeProduct?.slug || "",
                    path: buildProductPath(safeProduct?.slug),
                  },
                ]}
              />
            </div>
          </div>
          <div className="w-full bg-white pb-[60px]">
            <div className="container-x mx-auto">
              {/*key name spelling not correct (gellery)*/}
              <ProductView
                key={safeProduct?.id}
                product={safeProduct}
                details={safeDetails}
                images={safeDetails?.gellery}
                reportHandler={ReportHandler}
                seller={safeDetails?.seller ? safeDetails.seller : false}
              />
            </div>
          </div>
        </div>

        <div
          className="product-des-wrapper w-full relative pb-[60px]"
          ref={reviewElement}
        >
          <div className="tab-buttons w-full mb-10 mt-5 sm:mt-0">
            <div className="container-x mx-auto">
              <ul className="flex gap-6 sm:gap-12 overflow-x-auto overscroll-x-contain -mx-2 px-2">
                {safeVideoSlides.length > 0 && (
                    <li>
                      <span
                        onClick={() => setTab("video")}
                        className={`py-[15px]  space-x-3.5 sm:text-[15px] text-sm sm:flex border-b font-medium cursor-pointer ${
                          tab === "video"
                            ? "border-qyellow text-qblack "
                            : "border-transparent text-qgray"
                        }`}
                      >
                        <span>
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            strokeWidth="1.5"
                            stroke="currentColor"
                            className="w-5 h-5"
                          >
                            <path
                              strokeLinecap="round"
                              d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"
                            />
                          </svg>
                        </span>{" "}
                        <span>Videolar</span>
                      </span>
                    </li>
                  )}

                <li>
                  <span
                    onClick={() => setTab("des")}
                    className={`py-[15px] sm:text-[15px] text-sm sm:block border-b font-medium cursor-pointer ${
                      tab === "des"
                        ? "border-qyellow text-qblack "
                        : "border-transparent text-qgray"
                    }`}
                  >
                    {ServeLangItem()?.Description}
                  </span>
                </li>
                <li>
                  <span
                    onClick={() => setTab("review")}
                    className={`py-[15px] sm:text-[15px] text-sm sm:block border-b font-medium cursor-pointer ${
                      tab === "review"
                        ? "border-qyellow text-qblack "
                        : "border-transparent text-qgray"
                    }`}
                  >
                    {ServeLangItem()?.Reviews}
                  </span>
                </li>
                {/* Satıcı bilgisi gizlendi (#70) */}
                <li>
                  <span
                    onClick={() => setTab("shipping")}
                    className={`py-[15px] sm:text-[15px] text-sm sm:block border-b font-medium cursor-pointer ${
                      tab === "shipping"
                        ? "border-qyellow text-qblack "
                        : "border-transparent text-qgray"
                    }`}
                  >
                    Teslimat Bilgisi
                  </span>
                </li>
                <li>
                  <span
                    onClick={() => setTab("return")}
                    className={`py-[15px] sm:text-[15px] text-sm sm:block border-b font-medium cursor-pointer ${
                      tab === "return"
                        ? "border-qyellow text-qblack "
                        : "border-transparent text-qgray"
                    }`}
                  >
                    İade Politikası
                  </span>
                </li>
              </ul>
            </div>
            <div className="w-full h-[1px] bg-[#E8E8E8] absolute left-0 sm:top-[50px] top-[36px] -z-10"></div>
          </div>
          <div className="tab-contents w-full ">
            <div className="container-x mx-auto">
              {tab === "video" && (
                <>
                  <div className="grid grid-cols-5 gap-10">
                    {safeVideoSlides.length > 0 &&
                      safeVideoSlides.map((item, i) => (
                        <div key={i} className="item h-40">
                          {/*<p  onClick={() => popupHandler(i)}>video {i}</p>*/}
                          <div
                            onClick={() => popupHandler(i)}
                            className="bg-red-500 text-white h-full flex justify-center items-center cursor-pointer"
                          >
                            <svg
                              xmlns="http://www.w3.org/2000/svg"
                              fill="none"
                              viewBox="0 0 24 24"
                              strokeWidth="1.5"
                              stroke="currentColor"
                              className="w-20 h-20"
                            >
                              <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                              />
                              <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                d="M15.91 11.672a.375.375 0 010 .656l-5.603 3.113a.375.375 0 01-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112z"
                              />
                            </svg>
                          </div>
                        </div>
                      ))}
                  </div>
                  <Lightbox
                    index={photoIndex}
                    open={open}
                    close={() => setOpen(false)}
                    plugins={[Video]}
                    slides={safeVideoSlides}
                  />
                </>
              )}
              {tab === "des" && (
                <>
                  <h6 className="text-[20px] font-bold text-qblack mb-5">
                    {ServeLangItem()?.Introduction}
                  </h6>
                  <div
                    className="product-detail-des mb-10"
                    dangerouslySetInnerHTML={{
                      __html: safeProduct?.long_description || "",
                    }}
                  ></div>
                  {safeDetails.specifications &&
                    Array.isArray(safeDetails.specifications) &&
                    safeDetails.specifications.length > 0 && (
                      <div className="product-specifications">
                        <h6 className="text-[20px] font-bold mb-4">
                          {ServeLangItem()?.Features} :
                        </h6>
                        <ul className="">
                          {safeDetails.specifications.map((item, i) => (
                            <li
                              key={i}
                              className=" leading-9 flex space-x-3 items-center"
                            >
                              <span className="text-qblack font-medium capitalize">
                                {" "}
                                {item?.key?.key}:
                              </span>
                              <span className="font-normal text-qgray">
                                {item.specification}
                              </span>
                            </li>
                          ))}
                        </ul>
                      </div>
                    )}
                </>
              )}
              {tab === "review" && (
                <div data-aos="fade-up" className="w-full tab-content-item">
                  <h6 className="text-[20px] font-bold text-qblack mb-2">
                    {ServeLangItem()?.Reviews}
                  </h6>
                  {/* review-comments */}
                  <div className="w-full">
                    <Reviews
                      comments={
                        commnets && commnets.length > 0
                          ? commnets.slice(0, 2)
                          : []
                      }
                    />
                  </div>
                </div>
              )}
              {/* Satıcı bilgisi tab içeriği gizlendi (#70) */}
              {tab === "shipping" && (
                <div data-aos="fade-up" className="w-full tab-content-item">
                  <div className="prose max-w-none text-qgray text-sm leading-7">
                    <h3 className="text-lg font-semibold text-qblack mb-4">Teslimat Bilgisi</h3>
                    <ul className="list-disc pl-5 space-y-2">
                      <li>Siparişiniz onaylandıktan sonra 1-3 iş günü içinde kargoya verilir.</li>
                      <li>Kargo süresi bulunduğunuz bölgeye göre 2-5 iş günü arasında değişir.</li>
                      <li>Kargo takip numarası sipariş detaylarınızda görüntülenecektir.</li>
                      <li>Teslimat sırasında alıcının kimliği kontrol edilebilir.</li>
                    </ul>
                  </div>
                </div>
              )}
              {tab === "return" && (
                <div data-aos="fade-up" className="w-full tab-content-item">
                  <div className="prose max-w-none text-qgray text-sm leading-7">
                    <h3 className="text-lg font-semibold text-qblack mb-4">İade Politikası</h3>
                    <ul className="list-disc pl-5 space-y-2">
                      <li>Ürünü teslim aldıktan sonra 14 gün içinde iade talebinde bulunabilirsiniz.</li>
                      <li>İade edilecek ürün kullanılmamış ve orijinal ambalajında olmalıdır.</li>
                      <li>İade talebi onaylandıktan sonra ürünü belirtilen adrese göndermeniz gerekmektedir.</li>
                      <li>İade tutarı, ürün tarafımıza ulaştıktan sonra 3-5 iş günü içinde ödeme yönteminize iade edilir.</li>
                    </ul>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
        <div className="w-full bg-[#fffaf0] py-[40px]">
          <div className="container-x mx-auto">
            <div className="rounded-[28px] border border-[#efe4c8] bg-white px-6 py-8 shadow-sm">
              <h2 className="text-[24px] font-semibold text-qblack mb-3">
                İlgili Kategoriler ve Sayfalar
              </h2>
              <p className="text-sm leading-7 text-qgray mb-6">
                Bu ürünle ilgili kategori, marka ve mağaza sayfalarına geçerek
                benzer berber ve kuaför ekipmanlarını daha hızlı keşfedebilirsiniz.
              </p>
              <div className="flex flex-wrap gap-3">
                {safeProduct?.category?.name && productCategorySlug && (
                  <Link
                    href={`/products?category=${productCategorySlug}`}
                    className="rounded-full border border-[#e5d7b8] bg-[#fff8e8] px-4 py-2 text-sm font-medium text-qblack transition hover:border-qyellow hover:text-qyellow"
                  >
                    {displayTurkishLabel(safeProduct.category.name)} kategorisindeki ürünler
                  </Link>
                )}
                {safeProduct?.brand?.name && productBrandSlug && (
                  <Link
                    href={`/products?brand=${productBrandSlug}`}
                    className="rounded-full border border-[#e5d7b8] bg-[#fff8e8] px-4 py-2 text-sm font-medium text-qblack transition hover:border-qyellow hover:text-qyellow"
                  >
                    {safeProduct.brand.name} markasındaki ürünler
                  </Link>
                )}
                <Link
                  href="/products"
                  className="rounded-full border border-[#e5d7b8] bg-[#fff8e8] px-4 py-2 text-sm font-medium text-qblack transition hover:border-qyellow hover:text-qyellow"
                >
                  Tüm profesyonel ürünleri incele
                </Link>
              </div>
            </div>
          </div>
        </div>
        {relatedProducts.length > 0 && (
          <div className="related-product w-full bg-white">
            <div className="container-x mx-auto">
              <div className="w-full py-[60px]">
                <h2 className="sm:text-3xl text-xl font-600 text-qblacktext leading-none mb-[30px]">
                  {ServeLangItem()?.Related_Product}
                </h2>
                <div
                  data-aos="fade-up"
                  className="grid grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 xl:gap-[30px] gap-2.5"
                >
                  <DataIteration
                    datas={relatedProducts}
                    startLength={0}
                    endLength={
                      relatedProducts.length > 4 ? 4 : relatedProducts.length
                    }
                  >
                    {({ datas }) => (
                      <div key={datas.id} className="item">
                        <ProductCard datas={datas} />
                      </div>
                    )}
                  </DataIteration>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
      {report && (
        <div className="w-full h-full flex fixed left-0 top-0 justify-center z-50 items-center">
          <div
            onClick={() => setReport(!report)}
            className="w-full h-full fixed left-0 right-0 bg-black  bg-opacity-25"
          ></div>
          <div
            data-aos="fade-up"
            className="sm:w-[548px] w-[calc(100%-1.5rem)] max-h-[100dvh] overflow-y-auto bg-white relative py-[40px] px-[24px] sm:px-[38px]"
            style={{ zIndex: "999" }}
          >
            <div className="title-bar flex items-center justify-between mb-3">
              <h6 className="text-2xl font-medium">
                {ServeLangItem()?.Report_Products}
              </h6>
              <span
                className="cursor-pointer"
                onClick={() => setReport(!report)}
              >
                <svg
                  width="54"
                  height="54"
                  viewBox="0 0 54 54"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    d="M26.9399 54.0001C12.0678 53.9832 -0.0210736 41.827 2.75822e-05 26.9125C0.0211287 12.0507 12.1965 -0.0315946 27.115 6.20658e-05C41.9703 0.0317188 54.0401 12.2153 54 27.1404C53.9599 41.9452 41.7972 54.0191 26.9399 54.0001ZM18.8476 16.4088C17.6765 16.4404 16.9844 16.871 16.6151 17.7194C16.1952 18.6881 16.3893 19.5745 17.1363 20.3258C19.0966 22.2906 21.0252 24.2913 23.0425 26.197C23.7599 26.8745 23.6397 27.2206 23.0045 27.8305C21.078 29.6793 19.2148 31.5956 17.3241 33.4802C16.9211 33.8812 16.5581 34.3012 16.4505 34.8857C16.269 35.884 16.6953 36.8337 17.5456 37.3106C18.4382 37.8129 19.5038 37.6631 20.3394 36.8421C22.3673 34.8435 24.3866 32.8365 26.3723 30.7999C26.8513 30.3082 27.1298 30.2871 27.6193 30.7915C29.529 32.7584 31.4851 34.6789 33.4201 36.6184C33.8463 37.0447 34.2831 37.4436 34.9098 37.5491C35.9184 37.7201 36.849 37.2895 37.3196 36.4264C37.7964 35.5548 37.6677 34.508 36.8912 33.7144C34.9731 31.756 33.0677 29.7806 31.0631 27.9149C30.238 27.1467 30.3688 26.7479 31.1031 26.0535C32.9896 24.266 34.8022 22.3982 36.6338 20.5516C37.7922 19.3845 37.8914 17.9832 36.9081 17.0293C35.9501 16.1007 34.5975 16.2146 33.4623 17.3416C31.5188 19.2748 29.5649 21.1995 27.6594 23.1664C27.1446 23.6983 26.8492 23.6962 26.3343 23.1664C24.4267 21.1974 22.4664 19.2811 20.5336 17.3374C19.9997 16.7971 19.4258 16.3666 18.8476 16.4088Z"
                    fill="#F34336"
                  />
                </svg>
              </span>
            </div>

            <div className="inputs w-full">
              <div className="w-full mb-5">
                <InputCom
                  label={ServeLangItem()?.Enter_Report_Ttile + "*"}
                  placeholder={ServeLangItem()?.Reports_Headline_here}
                  type="text"
                  name="name"
                  inputClasses="h-[50px]"
                  labelClasses="text-[13px] font-600 leading-[24px] text-qblack"
                  value={subject}
                  inputHandler={(e) => setSubject(e.target.value)}
                  error={
                    !!(reportErrors && Object.hasOwn(reportErrors, "subject"))
                  }
                />
                {reportErrors && Object.hasOwn(reportErrors, "subject") ? (
                  <span className="text-sm mt-1 text-qred">
                    {reportErrors.subject[0]}
                  </span>
                ) : (
                  ""
                )}
              </div>
              <div className="w-full mb-[40px]">
                <h6 className="input-label  capitalize text-[13px] font-600 leading-[24px] text-qblack block mb-2 ">
                  {ServeLangItem()?.Enter_Report_Note}*
                </h6>
                <textarea
                  name=""
                  id=""
                  cols="30"
                  rows="6"
                  className={`w-full focus:ring-0 focus:outline-none py-3 px-4 border  placeholder:text-sm text-sm ${
                    reportErrors ? "border-qred" : "border-qgray-border"
                  }`}
                  placeholder={ServeLangItem()?.Type_Here}
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                ></textarea>
                {reportErrors && Object.hasOwn(reportErrors, "description") ? (
                  <span className="text-sm mt-1 text-qred">
                    {reportErrors.description[0]}
                  </span>
                ) : (
                  ""
                )}
              </div>

              <button
                disabled={reportLoading}
                onClick={() => productReport(safeProduct?.id)}
                type="button"
                className="black-btn flex h-[50px] items-center justify-center w-full"
              >
                <span>{ServeLangItem()?.Submit_Report}</span>
                {reportLoading && (
                  <span className="w-5 " style={{ transform: "scale(0.3)" }}>
                    <LoaderStyleOne />
                  </span>
                )}
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
