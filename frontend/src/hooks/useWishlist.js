import { useContext, useEffect, useState, useMemo, useCallback } from "react";
import { useSelector } from "react-redux";
import { toast } from "react-toastify";
import auth from "../utils/auth";
import {
  useLazyAddToWishlistApiQuery,
  useLazyRemoveFromWishlistApiQuery,
} from "../redux/features/product/apiSlice";
import LoginContext from "../components/Contexts/LoginContext";

const useWishlist = (product) => {
  // Redux state
  const { wishlistData } = useSelector((state) => state.wishlistData);
  const wishlist = wishlistData && wishlistData.wishlists;

  // Local state
  const [arWishlist, setArWishlist] = useState(null);
  const loginPopupBoard = useContext(LoginContext);

  // RTK Query hooks
  const [addToWishlistApi, { isLoading: addToWishlistLoading }] =
    useLazyAddToWishlistApiQuery();
  const [removeFromWishlistApi, { isLoading: removeFromWishlistLoading }] =
    useLazyRemoveFromWishlistApiQuery();

  // Check if product is in wishlist
  const wishlisted = useMemo(
    () => wishlist && wishlist.find((id) => id.product.id === product.id),
    [wishlist, product.id]
  );

  // Sync local wishlist state with global state
  useEffect(() => {
    setArWishlist(!!wishlisted);
  }, [wishlisted]);

  // Add product to wishlist
  const addToWishlist = useCallback(
    async (id) => {
      if (auth()) {
        setArWishlist(true);
        const userToken = auth().access_token;
        const data = {
          token: userToken,
          id: id,
        };

        try {
          const result = await addToWishlistApi(data).unwrap();
          if (result.success) {
            toast.success("Product added to wishlist");
          }
        } catch (error) {
          setArWishlist(false); // Revert optimistic update on error
          toast.error(error?.data?.message || "Failed to add to wishlist");
        }
      } else {
        loginPopupBoard.handlerPopup(true);
      }
    },
    [addToWishlistApi, loginPopupBoard]
  );

  // Remove product from wishlist
  const removeToWishlist = useCallback(
    async (id) => {
      if (auth()) {
        setArWishlist(false);
        const userToken = auth().access_token;
        const data = {
          token: userToken,
          id: id,
        };

        try {
          const result = await removeFromWishlistApi(data).unwrap();
          if (result.success) {
            toast.success("Product removed from wishlist");
          }
        } catch (error) {
          setArWishlist(true); // Revert optimistic update on error
          toast.error(error?.data?.message || "Failed to remove from wishlist");
        }
      } else {
        loginPopupBoard.handlerPopup(true);
      }
    },
    [removeFromWishlistApi, loginPopupBoard]
  );

  return {
    wishlisted,
    arWishlist,
    addToWishlist,
    removeToWishlist,
    addToWishlistLoading,
    removeFromWishlistLoading,
  };
};

export default useWishlist;
