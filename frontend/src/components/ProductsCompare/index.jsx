"use client";
import Image from "next/image";
import Link from "next/link";
import { useEffect, useState, useMemo, useContext } from "react";
import Star from "../Helpers/icons/Star";
import PageTitle from "../Helpers/PageTitle";
import CheckProductIsExistsInFlashSale from "../Shared/CheckProductIsExistsInFlashSale";
import ServeLangItem from "../Helpers/ServeLangItem";
import ComDltIco from "../Helpers/icons/ComDltIco";
import auth from "../../utils/auth";
import {
  useLazyCompareListApiQuery,
  useRemoveFromCompareApiMutation,
} from "@/redux/features/product/apiSlice";
import LoginContext from "@/components/Contexts/LoginContext";
import LoaderStyleTwo from "../Helpers/Loaders/LoaderStyleTwo";
import appConfig from "@/appConfig";

function ProductsCompare() {
  const loginPopupBoard = useContext(LoginContext);
  // product list
  const [productList, setProductList] = useState([]);
  const [viewLoading, setViewLoading] = useState(true);
  // Single state object to manage all compare data
  const [compareData, setCompareData] = useState({
    imagesRow: [],
    ratings: [],
    stocks: [],
  });

  // get compare products from api
  const [compareListApi, { isLoading: isCompareListLoading }] =
    useLazyCompareListApiQuery();

  useEffect(() => {
    if (auth()) {
      const userToken = auth().access_token;
      const data = {
        token: userToken,
        success: (data) => {
          setProductList(data?.products);
          setViewLoading(false);
        },
      };
      compareListApi(data);
    }
  }, []);

  // Update compare data when products change
  useEffect(() => {
    const products = productList;

    if (products && products.length > 0) {
      const maxProducts = Math.min(products.length, 4);
      const slicedProducts = products.slice(0, maxProducts);

      const newCompareData = {
        imagesRow: slicedProducts.map((item) => ({
          id: item.id,
          price: parseInt(item.price),
          offerPrice: item.offer_price ? parseInt(item.offer_price) : null,
          image: item.thumb_image,
          name: item.short_name,
          variants: item.active_variants,
        })),
        ratings: slicedProducts.map((item) => parseInt(item.averageRating)),
        stocks: slicedProducts.map((item) => parseInt(item.qty)),
      };

      setCompareData(newCompareData);
    } else {
      setCompareData({ imagesRow: [], ratings: [], stocks: [] });
    }
  }, [productList]);

  /**
   * product remove from compare functionality
   * @Initializaing useLazyRemoveFromCompareApiQuery @const removeFromCompareApi
   * @func removeItem @params id
   */
  const [removeFromCompareApi, { isLoading: removeFromCompareLoading }] =
    useRemoveFromCompareApiMutation();

  const removeItem = async (id) => {
    if (auth()) {
      const userToken = auth().access_token;
      const data = {
        token: userToken,
        id: id,
      };
      await removeFromCompareApi(data);
      setProductList(productList.filter((item) => item.id !== id));
    } else {
      loginPopupBoard.handlerPopup(true);
    }
  };

  // Calculate product price with variants
  const calculatePrice = (item) => {
    if (!item) return 0;

    const variantPrices =
      item.variants?.map(
        (variant) => variant.active_variant_items?.[0]?.price || 0
      ) || [];

    const variantSum = variantPrices.reduce(
      (sum, price) => sum + parseInt(price),
      0
    );
    const basePrice = item.offerPrice
      ? parseInt(item.offerPrice)
      : parseInt(item.price);

    return basePrice + variantSum;
  };

  // Calculate table column width
  const columnWidth = useMemo(() => {
    const productCount = productList?.length || 0;
    const maxProducts = Math.min(productCount, 4);
    return `calc(100% / ${maxProducts + 1})`;
  }, [productList]);

  // Breadcrumb data
  const breadcrumbData = useMemo(
    () => [
      { name: ServeLangItem()?.home, path: "/" },
      {
        name: ServeLangItem()?.Product_Compare || "Urun Karsilastirma",
        path: "/products-compare",
      },
    ],
    []
  );

  // Product image component
  const ProductImage = ({ item, index }) => (
    <td
      key={index}
      className="product bg-white p-6 border-b border-r border-qgray-border relative"
      style={{ width: columnWidth }}
    >
      <div className="product-img flex justify-center mb-3">
        <div className="w-[161px] h-[161px] relative">
          <Image
            fill
            style={{ objectFit: "scale-down" }}
            src={`${appConfig.BASE_URL}${item.image}`}
            alt=""
            className="w-full h-full object-contain"
          />
        </div>
      </div>
      <p className="text-center text-[15px] font-medium text-qblack leading-[24px] mb-2 notranslate">
        {item.name}
      </p>
      <p className="text-center text-[15px] font-medium text-qred leading-[24px]">
        <span>
          <CheckProductIsExistsInFlashSale
            id={item.id}
            price={calculatePrice(item)}
          />
        </span>
      </p>
      <div className="absolute right-2.5 top-2.5">
        <button type="button" onClick={() => removeItem(item.id)}>
          <span className="text-red-500">
            <ComDltIco />
          </span>
        </button>
      </div>
    </td>
  );

  // Star rating component
  const StarRating = ({ rating, index }) => (
    <td
      key={index}
      style={{ width: columnWidth }}
      className="product bg-white px-6 border-r border-qgray-border pb-[20px] align-top"
    >
      <div className="flex space-x-2 items-center">
        {Array.from(Array(rating), () => (
          <span key={`star-${rating}-${Math.random()}`}>
            <Star />
          </span>
        ))}
        {rating < 5 && (
          <>
            {Array.from(Array(5 - rating), () => (
              <span
                key={`empty-star-${rating}-${Math.random()}`}
                className="text-gray-500"
              >
                <Star defaultValue={false} />
              </span>
            ))}
          </>
        )}
      </div>
    </td>
  );

  // Availability component
  const AvailabilityStatus = ({ stock, index }) => (
    <td
      key={index}
      style={{ width: columnWidth }}
      className="product bg-white px-6 border-r border-qgray-border pb-[20px] align-top"
    >
      {stock !== 0 ? (
        <span className="text-[13px] font-semibold text-green-500">
          {ServeLangItem()?.In_Stock}
        </span>
      ) : (
        <span className="text-[13px] font-semibold text-red-500">
          {ServeLangItem()?.Out_of_Stock}
        </span>
      )}
    </td>
  );

  // Specification component
  const ProductSpecification = ({ product, index }) => (
    <td
      key={index}
      style={{ width: columnWidth }}
      className="product bg-white px-6 border-r border-qgray-border pb-[20px] align-top"
    >
      {product.specifications?.length > 0 ? (
        product.specifications.map((spec, specIndex) => (
          <ul key={specIndex}>
            <li className="mb-2">
              <p className="text-qblack text-sm font-semibold">
                {spec.key.key}
              </p>
              <span className="text-[13px] font-normal text-qgraytwo">
                {spec.specification}
              </span>
            </li>
          </ul>
        ))
      ) : (
        <span className="text-[13px] font-normal text-qgraytwo">Bilgi yok</span>
      )}
    </td>
  );

  // Table header cell component
  const TableHeaderCell = ({ children }) => (
    <td
      className="px-[26px] align-top bg-[#FAFAFA]"
      style={{ width: columnWidth }}
    >
      <div>{children}</div>
    </td>
  );

  // Empty state component
  const EmptyState = () => (
    <div className="w-full h-96 flex justify-center items-center border border-qgray-border">
      <div>
        <p className="text-xl text-qblack">
          {ServeLangItem()?.Your_Compare_List_Is_Empty}
        </p>
        <Link href="/">
          <div className="flex justify-center w-full mt-5 cursor-pointer">
            <div className="w-[180px] h-[50px]">
              <span type="button" className="yellow-btn">
                {ServeLangItem()?.Back_to_Shop}
              </span>
            </div>
          </div>
        </Link>
      </div>
    </div>
  );

  // main component render
  return (
    <div className="products-compare-wrapper w-full bg-white pb-[40px]">
      <div className="w-full mb-5">
        <PageTitle breadcrumb={breadcrumbData} title="Urun Karsilastirma" />
      </div>
      <div className="container-x mx-auto">
        {!viewLoading && productList ? (
          <>
            {productList.length > 0 ? (
              <div className="w-full border border-qgray-border overflow-x-auto">
                <table className="table-wrapper w-full">
                  <tbody>
                    {/*image*/}
                    <tr className="table-row-wrapper">
                      <TableHeaderCell>
                        <h2 className="text-[18px] font-medium text-qblack mb-4">
                          {ServeLangItem()?.Product_Comparison}
                        </h2>
                        <p className="text-[13px] text-qgraytwo">
                          {
                            ServeLangItem()
                              ?.Select_products_to_see_the_differences_and_similarities_between_them
                          }
                        </p>
                      </TableHeaderCell>
                      {compareData.imagesRow?.map((item, i) => (
                        <ProductImage key={i} item={item} index={i} />
                      ))}
                    </tr>

                    {/*rating*/}
                    <tr className="table-row-wrapper">
                      <TableHeaderCell>
                        <h2 className="text-[15px] font-medium text-qblack">
                          {ServeLangItem()?.Star_Rating}
                        </h2>
                      </TableHeaderCell>
                      {compareData.ratings.map((item, i) => (
                        <StarRating key={i} rating={item} index={i} />
                      ))}
                    </tr>

                    {/*Availability*/}
                    <tr className="table-row-wrapper">
                      <TableHeaderCell>
                        <h2 className="text-[15px] font-medium text-qblack">
                          {ServeLangItem()?.Availability}
                        </h2>
                      </TableHeaderCell>
                      {compareData.stocks?.map((item, i) => (
                        <AvailabilityStatus key={i} stock={item} index={i} />
                      ))}
                    </tr>

                    <tr className="table-row-wrapper">
                      <TableHeaderCell>
                        <h2 className="text-[15px] font-medium text-qblack">
                          {ServeLangItem()?.Specification}
                        </h2>
                      </TableHeaderCell>
                      {compareData.imagesRow?.map((item, i) => (
                        <ProductSpecification
                          key={i}
                          product={item}
                          index={i}
                        />
                      ))}
                    </tr>
                  </tbody>
                </table>
              </div>
            ) : (
              <EmptyState />
            )}
          </>
        ) : (
          <div className="w-full justify-center ">
            <LoaderStyleTwo />
          </div>
        )}
      </div>
    </div>
  );
}

export default ProductsCompare;
