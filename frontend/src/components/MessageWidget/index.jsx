"use client";

import React, {
  useContext,
  useEffect,
  useRef,
  useState,
  Suspense,
} from "react";
import EmojiPicker from "emoji-picker-react";
import Image from "next/image";
import { useSearchParams } from "next/navigation";
import Pusher from "pusher-js";
import Echo from "laravel-echo";
import Star from "../Helpers/icons/Star";
import CheckProductIsExistsInFlashSale from "../Shared/CheckProductIsExistsInFlashSale";
import settings from "@/utils/settings";
import auth from "@/utils/auth";
import appConfig from "@/appConfig";
import { resolveProductImageUrl } from "@/utils/productImage";
import MessageContext from "../Contexts/MessageContext";
import LoginContext from "../Contexts/LoginContext";
import ServeLangItem from "../Helpers/ServeLangItem";
import ChatMessageIcon from "../Helpers/icons/ChatMessageIcon";
import CloseCircleIcon from "../Helpers/icons/CloseCircleIcon";
import DoubleCheckIcon from "../Helpers/icons/DoubleCheckIcon";
import EmojiIcon from "../Helpers/icons/EmojiIcon";
import SendIcon from "../Helpers/icons/SendIcon";
import { useLazyGetProductBySlugApiQuery } from "@/redux/features/product/apiSlice";
import {
  useLazyLoadActiveSellerMessageQuery,
  useLazyMessageWithSellerQuery,
  useSendMessageToSellerMutation,
} from "@/redux/features/message/apiSlice";
import Moment from "react-moment";

function MessageWidgetContent({ pusher }) {
  const toggleMessage = useContext(MessageContext);
  const loginPopupBoard = useContext(LoginContext);

  const searchParams = useSearchParams();
  const getSlug = searchParams?.get("slug");

  const { currency_icon } = settings();

  const messageRef = useRef(null);

  const [state, setState] = useState({
    // Message related states
    message: "", // Current message input
    messages: [], // List of all seller conversations
    sellerMessages: [], // Messages with selected seller

    // Seller related states
    selectedSellerId: null, // Currently selected seller ID
    sellerVendorId: null, // Vendor ID of selected seller

    // UI states
    toggleEmoji: false, // Emoji picker visibility

    // Product related states
    isSeller: null, // Whether current product has seller
    product: null, // Current product data
    varients: null, // Product variants
    getFirstVarients: null, // First variant selection
    price: null, // Calculated product price
    offerPrice: null, // Calculated offer price

    // Real-time communication states
    listenerAdded: false, // Pusher listener status
  });

  // message Api Request
  const [messageWithSeller] = useLazyMessageWithSellerQuery();

  const fetchChat = async () => {
    if (!auth()) return;
    const userToken = auth()?.access_token;
    const response = await messageWithSeller({ token: userToken });
    if (response?.status === "fulfilled") {
      updateState({ messages: response.data.seller_list });
    }
  };

  useEffect(() => {
    if (auth() && !state.messages?.length) {
      fetchChat();
    }
  }, [state.messages]);

  const echoOptions = {
    broadcaster: "pusher",
    key: pusher?.app_key,
    cluster: pusher?.app_cluster,
    forceTLS: true,
    encrypted: false,
    // Authentication endpoint for private channels
    authEndpoint: appConfig.BASE_URL + "api/broadcasting/auth",
    // JWT token authentication headers
    auth: {
      headers: {
        Authorization: `Bearer ${auth()?.access_token || ""}`,
        Accept: "application/json",
      },
    },
  };

  /**
   * Scrolls message container to bottom
   * Used when new messages arrive or container is opened
   */
  const scrollToBottom = () => {
    if (messageRef.current) {
      messageRef.current.scrollTop = messageRef.current.scrollHeight;
    }
  };

  /**
   * Updates state with spread operator pattern
   * @param {Object} updates - State updates to apply
   */
  const updateState = (updates) => {
    setState((prev) => ({ ...prev, ...updates }));
  };

  /**
   * Handles incoming real-time messages from sellers
   * @param {Object} notification - Message notification from pusher
   * @param {string} activeSellerId - Currently active seller ID
   */
  const handleNewMessage = async (notification, activeSellerId) => {
    if (!auth()) return;

    const { message } = notification;

    if (message.seller_id === activeSellerId) {
      // Add message to current conversation if it's from active seller
      setState((prev) => ({
        ...prev,
        sellerMessages: (() => {
          const messageExists = prev.sellerMessages.some(
            (item) => item.id === message.id
          );
          return messageExists
            ? prev.sellerMessages
            : [...prev.sellerMessages, message];
        })(),
      }));
    } else {
      fetchChat();
    }
  };

  /**
   * Handles message input changes
   * @param {Event} event - Input change event
   */
  const handleMessageInput = (event) => {
    updateState({ message: event.target.value });
  };

  /**
   * Handles emoji selection and adds to message
   * @param {Object} emojiData - Selected emoji data
   */
  const handleEmojiSelect = (emojiData) => {
    updateState({ message: state.message + emojiData.emoji });
  };

  /**
   * send message to seller feature
   * @Initializing useSendMessageToSellerMutation @const sendMessageToSeller
   * @func sendMessage @params {number} productId
   */
  const [sendMessageToSeller] = useSendMessageToSellerMutation();

  const sendMessage = async (productId = null) => {
    if (!auth()) {
      loginPopupBoard.handlerPopup(true);
      return;
    }

    if (!state.selectedSellerId) {
      return false;
    }

    const userToken = auth()?.access_token;

    // success handler
    const successHandler = (data, statusCode) => {
      if (statusCode === 200 || statusCode === 201) {
        updateState({
          sellerMessages: [...state.sellerMessages, data.message],
          message: "",
          product: null,
        });
        scrollToBottom();
      }
    };

    await sendMessageToSeller({
      token: userToken,
      data: {
        seller_id: parseInt(state.selectedSellerId),
        message: state.message,
        product_id: productId ? productId : undefined,
      },
      success: successHandler,
    });
  };

  /**
   * load active seller message feature
   * @Initializing useLazyLoadActiveSellerMessageQuery @const loadActiveSellerMessage
   * @func handleSellerSelection @params {number} sellerId @params {number} vendorId
   */
  const [loadActiveSellerMessage] = useLazyLoadActiveSellerMessageQuery();

  const handleSellerSelection = async (sellerId, vendorId) => {
    // Store active seller in localStorage for persistence
    if (typeof window !== "undefined") {
      localStorage.setItem("active-chat-seller", `${sellerId}`);
    }

    updateState({
      selectedSellerId: Number(sellerId),
      sellerVendorId: Number(vendorId),
    });

    if (auth()) {
      const userToken = auth()?.access_token;

      const response = await loadActiveSellerMessage({
        token: userToken,
        sellerId: sellerId,
      });

      if (response?.status === "fulfilled") {
        updateState({
          sellerMessages: response.data.messages,
          messages:
            state.messages?.length > 0
              ? state.messages.map((item) =>
                  item.shop_owner_id === sellerId
                    ? { ...item, unread_message: 0 }
                    : item
                )
              : state.messages,
        });
      }
    }
  };

  /**
   * Toggles message widget visibility
   */
  const toggleMessageWidget = () => {
    if (!auth()) {
      loginPopupBoard.handlerPopup(true);
      return;
    }

    updateState({ isSeller: null, product: null });
    toggleMessage.toggleHandler();

    if (!state.messages?.length) {
      fetchChat();
    }
  };

  /**
   * Calculates total price including variants
   * @param {Array} variants - Product variants
   * @param {Object} product - Product data
   * @returns {Object} - Calculated prices
   */
  const calculateProductPrices = (variants, product) => {
    if (!variants?.length || !product) return { price: null, offerPrice: null };

    const variantPrices = variants.map((v) => v.price || 0);
    const variantTotal = variantPrices.reduce(
      (sum, price) => sum + parseInt(price),
      0
    );

    const basePrice = parseInt(product.price);
    const totalPrice = variantTotal + basePrice;

    const prices = { price: totalPrice, offerPrice: null };

    if (product.offer_price) {
      const baseOfferPrice = parseInt(product.offer_price);
      prices.offerPrice = variantTotal + baseOfferPrice;
    }

    return prices;
  };

  /**
   * Effect: Calculate first variants when product variants change
   */
  useEffect(() => {
    if (!state.varients) return;

    const firstVariants = state.varients.map((variant) =>
      variant.active_variant_items?.length > 0
        ? variant.active_variant_items[0]
        : {}
    );

    updateState({ getFirstVarients: firstVariants });
  }, [state.varients]);

  /**
   * Effect: Calculate product prices when variants or product changes
   */
  useEffect(() => {
    if (!state.getFirstVarients || !state.product) return;

    const calculatedPrices = calculateProductPrices(
      state.getFirstVarients,
      state.product
    );
    updateState(calculatedPrices);
  }, [state.getFirstVarients, state.product]);

  // get product by slug
  const [getProductBySlug] = useLazyGetProductBySlugApiQuery();

  /**
   * Effect: Load product data based on slug
   */
  useEffect(() => {
    if (!getSlug) return;
    const loadProduct = async () => {
      const response = await getProductBySlug(getSlug);
      if (response?.status === "fulfilled") {
        const { is_seller_product, product, seller } = response?.data;
        if (is_seller_product || is_seller_product === "true") {
          updateState({
            product: product,
            isSeller: is_seller_product,
            varients: product.active_variants,
          });
        } else {
          updateState({ product: false });
        }
        handleSellerSelection(
          Number(seller?.user_id),
          Number(product?.vendor_id)
        );
      }
    };
    loadProduct();
  }, [getSlug]);

  /**
   * Effect: Auto-scroll when messages change
   */
  useEffect(() => {
    if (state.messages?.length > 0) {
      scrollToBottom();
    }
  }, [state.messages]);

  /**
   * Effect: Handle message container layout
   */
  useEffect(() => {
    const messageBody = document.getElementById("message-body");

    if (!messageRef.current) return;

    if (state.sellerMessages?.length > 0) {
      if (messageBody?.offsetHeight <= 390) {
        messageRef.current.style.display = "flex";
        messageRef.current.style.alignItems = "end";
      } else {
        messageRef.current.style.display = "";
        messageRef.current.style.alignItems = "";
        scrollToBottom();
      }
    } else {
      messageRef.current.style.display = "";
      messageRef.current.style.alignItems = "";
      scrollToBottom();
    }
  }, [state.sellerMessages, state.selectedSellerId]);

  /**
   * Effect: Add new sellers to conversation list
   */
  useEffect(() => {
    if (!toggleMessage.newSeller) return;

    const newSellerData = {
      shop_owner_id: toggleMessage.newSeller.user_id,
      shop_owner: toggleMessage.newSeller.user.name,
      shop_name: toggleMessage.newSeller.shop_name,
      shop_or_vendor_id: toggleMessage.newSeller.id,
      shop_slug: toggleMessage.newSeller.slug,
      shop_logo: toggleMessage.newSeller.logo,
      unread_message: 0,
      messages: [],
    };

    const sellerExists = state.messages?.some(
      (seller) => seller.shop_or_vendor_id === toggleMessage.newSeller.id
    );

    if (!sellerExists) {
      updateState({
        messages: [newSellerData, ...state.messages],
      });
    }
  }, [toggleMessage.newSeller]);

  /**
   * Effect: Setup real-time messaging with Pusher/Echo
   */
  useEffect(() => {
    if (state.listenerAdded || !auth()) return;

    const echo = new Echo(echoOptions);

    echo
      .private(`seller-to-user-message.${auth().user?.id}`)
      .listen("SellerToUser", (event) => {
        const activeSeller =
          typeof window !== "undefined"
            ? localStorage.getItem("active-chat-seller")
            : null;
        handleNewMessage(event, activeSeller);
      });

    updateState({ listenerAdded: true });

    // Cleanup function
    return () => {
      echo.leave("seller-to-user-message");
    };
  }, [state.listenerAdded]);

  /**
   * Renders star rating component
   * @param {number} rating - Rating value
   * @param {string} keyPrefix - Unique key prefix
   * @returns {JSX.Element} - Star rating elements
   */
  const renderStarRating = (rating, keyPrefix = "") => {
    const ratingNum = parseInt(rating);
    const filledStars = Array.from({ length: ratingNum }, (_, i) => (
      <span key={`${keyPrefix}-filled-${i}`}>
        <Star />
      </span>
    ));

    const emptyStars =
      ratingNum < 5
        ? Array.from({ length: 5 - ratingNum }, (_, i) => (
            <span key={`${keyPrefix}-empty-${i}`} className="text-gray-500">
              <Star defaultValue={false} />
            </span>
          ))
        : [];

    return [...filledStars, ...emptyStars];
  };

  /**
   * Renders product card component
   * @param {Object} product - Product data
   * @param {boolean} isFloating - Whether it's a floating product card
   * @returns {JSX.Element} - Product card element
   */
  const renderProductCard = (product, isFloating = false) => (
    <div className={`flex space-x-2.5 items-center ${isFloating ? "p-3" : ""}`}>
      <div className="w-[58px] h-[63px] rounded relative bg-white">
        <Image
          layout="fill"
          objectFit="contain"
          src={resolveProductImageUrl(product.thumb_image)}
          alt={product.name}
        />
      </div>
      <div className="flex-1 flex flex-col">
        <div className="reviews flex space-x-[1px]">
          {renderStarRating(product.averageRating, `product-${product.id}`)}
        </div>
        <p
          className={`${
            isFloating ? "text-white" : "text-qblack"
          } text-xs font-medium leading-[24px] line-clamp-1`}
        >
          {product.name}
        </p>
        {isFloating && (
          <div className="flex space-x-2 items-baseline">
            <span
              suppressHydrationWarning
              className={`main-price font-600 ${
                state.offerPrice
                  ? "line-through text-qgray text-xs"
                  : "text-qred text-xs"
              }`}
            >
              {state.offerPrice ? (
                <span>{currency_icon + state.price}</span>
              ) : (
                <CheckProductIsExistsInFlashSale
                  id={product.id}
                  price={state.price}
                />
              )}
            </span>
            {state.offerPrice && (
              <span
                suppressHydrationWarning
                className="offer-price text-qred font-600 text-xs ml-2"
              >
                <CheckProductIsExistsInFlashSale
                  id={product.id}
                  price={state.offerPrice}
                />
              </span>
            )}
          </div>
        )}
      </div>
      {isFloating && (
        <button
          onClick={() => sendMessage(product.id)}
          style={{ left: "calc(50% - 46px)", bottom: "-16px" }}
          type="button"
          className="w-[120px] h-[32px] bg-white text-sm text-qyellow font-semibold absolute shadow-lg"
        >
          {ServeLangItem().Send_Product || "Ürünü Gönder"}
        </button>
      )}
    </div>
  );

  return (
    <>
      {/* FLOATING TOGGLE BUTTON */}
      {!toggleMessage.toggle && (
        <button
          onClick={toggleMessageWidget}
          type="button"
          className="w-[150px] text-qblack print:hidden fixed xl:right-[180px] right-[-56px] transform rotate-90 xl:rotate-0 z-30 xl:bottom-0 bottom-[150px] h-[38px] bg-qyellow justify-center flex space-x-2.5 rtl:space-x-reverse items-center cursor-pointer"
        >
          <span>
            <ChatMessageIcon />
          </span>
          <span className="text-base font-medium text-qblack">
            {"AI Asistan"}
          </span>
        </button>
      )}

      {/* MAIN MESSAGE WIDGET */}
      <div
        className="fixed print:hidden xl:right-[180px] right-0 bottom-0 w-full sm:w-auto"
        style={{ zIndex: "9999999999999" }}
      >
        <div
          className={`bg-white transform transition duration-700 ease-in-out ${
            toggleMessage.toggle
              ? "translate-y-0 md:w-[576px] w-full md:h-[474px] h-full"
              : "translate-y-[480px] w-0 h-0"
          }`}
          style={{ boxShadow: "0px 4px 109px rgba(0, 0, 0, 0.12)" }}
        >
          {/* WIDGET HEADER */}
          <div className="w-full h-[38px] bg-qyellow text-qblack">
            <div className="w-full h-full flex justify-between items-center px-[26px]">
              <div className="flex space-x-2.5 items-center">
                <span>
                  <ChatMessageIcon />
                </span>
                <span className="text-base font-medium text-qblack">
                  {"Yapay Zeka Asistanı"}
                </span>
              </div>
              <button onClick={toggleMessageWidget} type="button" aria-label="Mesaj widget'ını kapat">
                <CloseCircleIcon />
              </button>
            </div>
          </div>

          {/* WIDGET BODY */}
          <div
            className="w-full md:flex"
            style={{ height: `calc(100% - 38px)` }}
          >
            {/* SELLER SIDEBAR */}
            <div className="md:w-[240px] bg-[#FFF2DA] md:bg-white w-full md:h-full overflow-x-scroll md:overflow-x-hidden border-r border-[#E9E9E9] md:overflow-y-scroll overflow-style-none">
              <div className="seller-list w-full">
                <ul className="flex flex-row md:flex-col">
                  {state.messages?.map((seller, index) => (
                    <li
                      key={seller.shop_or_vendor_id || index}
                      onClick={() =>
                        handleSellerSelection(
                          seller.shop_owner_id,
                          seller.shop_or_vendor_id
                        )
                      }
                      className={`flex w-[150px] md:w-auto space-x-3 items-center px-2.5 py-3 hover:bg-[#FFF2DA] cursor-pointer ${
                        Number(seller.shop_owner_id) === state.selectedSellerId
                          ? "bg-[#FFF2DA]"
                          : ""
                      }`}
                    >
                      {/* Seller Avatar */}
                      <div className="w-[44px] h-[44px] relative">
                        <div className="w-full h-full rounded-full overflow-hidden bg-[#FAF9FA] relative shadow-lg">
                          <Image
                            layout="fill"
                            objectFit="contain"
                            src={appConfig.BASE_URL + seller.shop_logo}
                            alt={seller.shop_name}
                          />
                        </div>
                        {/* Unread Message Badge */}
                        {seller.unread_message > 0 && (
                          <div className="w-[15px] h-[15px] flex justify-center items-center rounded-full bg-qyellow text-qblack absolute right-0 top-0 text-xs">
                            <span>
                              {seller.unread_message > 99
                                ? "99"
                                : seller.unread_message}
                            </span>
                          </div>
                        )}
                      </div>

                      {/* Seller Info */}
                      <div className="flex-1">
                        <div className="flex flex-col space-y-1">
                          <p className="font-medium text-base text-qblack line-clamp-1 notranslate">
                            {seller.shop_name}
                          </p>
                          {/* Last Message Preview */}
                          {seller.messages?.length > 0 && (
                            <div className="md:block hidden">
                              <p className="text-xs text-qgray line-clamp-1">
                                {
                                  seller.messages[seller.messages.length - 1]
                                    ?.message
                                }
                              </p>
                            </div>
                          )}
                        </div>
                      </div>
                    </li>
                  ))}
                </ul>
              </div>
            </div>

            {/* CHAT AREA */}
            <div className="md:w-[336px] w-full relative">
              {/* EMOJI PICKER OVERLAY */}
              {state.toggleEmoji && (
                <div className="absolute -left-4 -top-16 z-20">
                  <div
                    onClick={() => updateState({ toggleEmoji: false })}
                    className="w-full h-full fixed left-0 top-0"
                  />
                  <EmojiPicker onEmojiClick={handleEmojiSelect} />
                </div>
              )}

              <div>
                {/* MESSAGES CONTAINER */}
                <div
                  ref={messageRef}
                  className="w-full h-[392px] px-2.5 pt-2.5 overflow-y-scroll overflow-style-none relative"
                >
                  {/* FLOATING PRODUCT CARD */}
                  {state.product &&
                    parseInt(state.product.vendor_id) ===
                      state.sellerVendorId && (
                      <div className="fixed w-[318px] h-[100px] bg-qblack p-3 top-[50px] z-10">
                        {renderProductCard(state.product, true)}
                      </div>
                    )}

                  {/* MESSAGES LIST */}
                  {state.sellerMessages?.length > 0 &&
                  state.selectedSellerId ? (
                    <div id="message-body" className="w-full">
                      {state.sellerMessages.map((message, index) => (
                        <div key={message.id || index}>
                          {message.send_by === "seller" ? (
                            // Received Message
                            <div className="mb-2 mr-10">
                              <div className="w-full">
                                <div
                                  className="bg-[#E8EEF2] py-[8px] px-[17px] flex justify-center rounded-[40px] text-qblack text-sm"
                                  style={{
                                    maxWidth: "fit-content",
                                    minWidth: "82px",
                                  }}
                                >
                                  <p className="text-justify">
                                    {message.message}
                                  </p>
                                </div>
                              </div>
                              <div className="flex space-x-1.5 items-center">
                                <p className="text-xs text-[#666666] leading-[24px] flex space-x-1.5 items-center">
                                  <Moment fromNow>{message.created_at}</Moment>
                                </p>
                                <span>
                                  <DoubleCheckIcon />
                                </span>
                              </div>
                            </div>
                          ) : (
                            // Sent Message
                            <div className="mb-2 ml-10">
                              <div>
                                {/* Product Card in Message */}
                                {message.product && (
                                  <div className="w-full bg-[#FFF2DA] p-3 mb-2">
                                    {renderProductCard(message.product)}
                                  </div>
                                )}

                                {/* Message Text */}
                                {message.message && (
                                  <div className="flex justify-end">
                                    <div className="flex flex-col items-end">
                                      <div
                                        className="bg-qyellow py-[8px] px-[17px] flex justify-center rounded-[40px] text-qblack text-sm"
                                        style={{
                                          maxWidth: "fit-content",
                                          minWidth: "82px",
                                        }}
                                      >
                                        <p className="text-justify">
                                          {message.message}
                                        </p>
                                      </div>
                                      <div className="flex justify-end">
                                        <div className="flex space-x-1.5 items-center">
                                          <p className="text-xs text-[#666666] leading-[24px] text-end">
                                            <Moment fromNow>
                                              {message.created_at}
                                            </Moment>
                                          </p>
                                          <span>
                                            <DoubleCheckIcon />
                                          </span>
                                        </div>
                                      </div>
                                    </div>
                                  </div>
                                )}
                              </div>
                            </div>
                          )}
                        </div>
                      ))}
                    </div>
                  ) : (
                    // Empty State
                    <div className="w-full h-full flex items-center justify-center">
                      <div className="text-center">
                        <div className="mb-5 flex justify-center w-[158px] h-[158px] relative mx-auto">
                          <Image
                            src="/assets/images/not-message-found.png"
                            fill
                            sizes="100%"
                            style={{ objectFit: "scale-down" }}
                            alt="no message"
                            className="w-full h-full object-scale-down"
                          />
                        </div>
                        <div>
                          <h2 className="text-[#1D1D1D] font-bold text-[20px] text-center">
                            {"Henüz mesaj yok"}
                          </h2>
                          <p className="text-[#666666] text-sm text-center">
                            {
                              "Ürünlerle ilgili soru sorabilir, özellik, fiyat ve stok bilgisi için bu alandan yardım alabilirsiniz."
                            }
                          </p>
                        </div>
                      </div>
                    </div>
                  )}
                </div>

                <div className="w-full h-[44px] flex justify-between bg-[#E2E8EB] pl-3">
                  {/* Message Input Field */}
                  <div className="md:w-[240px] w-full h-full">
                    <input
                      onChange={handleMessageInput}
                      onKeyDown={(e) => e.key === "Enter" && sendMessage()}
                      value={state.message}
                      placeholder="Ürün hakkında sorunuzu yazın"
                      className="w-full h-full placeholder:text-[#85959E] text-[#85959E] focus:outline-none bg-[#E2E8EB]"
                    />
                  </div>

                  {/* Action Buttons */}
                  <div className="flex items-center space-x-5">
                    {/* Emoji Button */}
                    <button
                      onClick={() =>
                        updateState({ toggleEmoji: !state.toggleEmoji })
                      }
                      type="button"
                      aria-label="Emoji ekle"
                    >
                      <span>
                        <EmojiIcon />
                      </span>
                    </button>

                    {/* Send Button */}
                    <button
                      onClick={() => sendMessage()}
                      type="button"
                      className="w-[50px] h-full bg-qyellow flex justify-center items-center text-qblack"
                      aria-label="Mesaj gönder"
                    >
                      <span>
                        <SendIcon />
                      </span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}

function MessageWidget({ pusher }) {
  return (
    <Suspense
      fallback={
        <div
          className="fixed print:hidden xl:right-[180px] right-0 bottom-0 w-full sm:w-auto"
          style={{ zIndex: "9999999999999" }}
        >
          <div className="bg-white w-[100px] h-[50px] flex justify-center items-center">
            <div className="animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-gray-900"></div>
          </div>
        </div>
      }
    >
      <MessageWidgetContent pusher={pusher} />
    </Suspense>
  );
}

export default MessageWidget;
