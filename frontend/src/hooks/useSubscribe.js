import { useSubscribeRequestMutation } from "@/redux/features/websiteSetup/apiSlice";

export const useSubscribe = () => {
  const [subscribeRequest, { isLoading }] = useSubscribeRequestMutation();

  return { subscribeRequest, isLoading };
};
