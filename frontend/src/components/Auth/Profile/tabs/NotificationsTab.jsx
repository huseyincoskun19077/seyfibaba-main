"use client";
import { useMemo } from "react";
import {
  useBuyerNotificationsApiQuery,
  useMarkAllBuyerNotificationsReadApiMutation,
  useMarkBuyerNotificationReadApiMutation,
} from "@/redux/features/auth/apiSlice";
import auth from "../../../../utils/auth";

function extractMessage(notification) {
  return (
    notification?.data?.message ||
    notification?.data?.body ||
    notification?.data?.title ||
    "Yeni bildirim"
  );
}

function extractTitle(notification) {
  const type = notification?.data?.type || "";
  const title = notification?.data?.title || notification?.data?.subject;
  if (title) return title;
  if (type === "order") return "Sipariş bildirimi";
  if (type === "campaign") return "Kampanya";
  if (type === "discount") return "İndirim";
  if (type === "admin_broadcast") return "Duyuru";
  return "Bildirim";
}

function formatDateTime(value) {
  if (!value) return "";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleString("tr-TR", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

export default function NotificationsTab() {
  const token = auth()?.access_token;
  const { data, isFetching, refetch } = useBuyerNotificationsApiQuery(
    { token, perPage: 30 },
    { skip: !token }
  );
  const [markOne, { isLoading: isMarkingOne }] =
    useMarkBuyerNotificationReadApiMutation();
  const [markAll, { isLoading: isMarkingAll }] =
    useMarkAllBuyerNotificationsReadApiMutation();

  const notifications = data?.notifications?.data || [];
  const unreadCount = data?.unread_count || 0;
  const totalCount = data?.notifications?.total || notifications.length;

  const items = useMemo(
    () =>
      notifications.map((notification) => ({
        ...notification,
        title: extractTitle(notification),
        message: extractMessage(notification),
      })),
    [notifications]
  );

  const handleMarkOne = async (id) => {
    await markOne({
      token,
      id,
      success: () => refetch(),
    });
  };

  const handleMarkAll = async () => {
    await markAll({
      token,
      success: () => refetch(),
    });
  };

  return (
    <div className="w-full">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-5">
        <div>
          <h2 className="text-xl font-semibold text-qblack">Bildirimler</h2>
          <p className="mt-1 text-sm text-qgray">Sipariş, kampanya ve duyuru bildirimleriniz.</p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <span className="rounded-full bg-qblack text-white px-3 py-1 text-sm font-semibold">
            Gelen {totalCount}
          </span>
          <span className="rounded-full bg-red-50 text-red-700 px-3 py-1 text-sm font-semibold">
            Okunmamış {unreadCount}
          </span>
          <button
            type="button"
            disabled={isMarkingAll || unreadCount === 0}
            onClick={handleMarkAll}
            className="text-sm font-semibold text-qblack underline disabled:opacity-50"
          >
            Tümünü oku
          </button>
        </div>
      </div>

      {isFetching && items.length === 0 ? (
        <div className="flex justify-center py-16">
          <div className="animate-spin rounded-full h-10 w-10 border-t-2 border-b-2 border-gray-900" />
        </div>
      ) : items.length === 0 ? (
        <div className="rounded-2xl border border-qgray-border bg-white p-10 text-center">
          <p className="text-qblack font-semibold">Bildirim yok</p>
          <p className="mt-1 text-sm text-qgray">Sipariş ve kampanya bildirimleri burada görünür.</p>
        </div>
      ) : (
        <div className="space-y-3">
          {items.map((notification) => {
            const unread = !notification.read_at;
            return (
              <div
                key={notification.id}
                className={`rounded-2xl border p-4 shadow-sm ${
                  unread
                    ? "border-yellow-200 bg-yellow-50/70"
                    : "border-qgray-border bg-white"
                }`}
              >
                <div className="flex items-start justify-between gap-4">
                  <div>
                    <div className="flex items-center gap-2">
                      <p className="text-sm font-semibold text-qblack">
                        {notification.title}
                      </p>
                      {unread && (
                        <span className="rounded-full bg-red-600 px-2 py-0.5 text-[10px] font-bold uppercase text-white">
                          Yeni
                        </span>
                      )}
                    </div>
                    <p className="mt-1 text-sm text-qgraytwo leading-6">
                      {notification.message}
                    </p>
                    <p className="mt-2 text-xs text-qgray">
                      {formatDateTime(notification.created_at)}
                    </p>
                  </div>
                  {unread ? (
                    <button
                      type="button"
                      disabled={isMarkingOne}
                      onClick={() => handleMarkOne(notification.id)}
                      className="shrink-0 rounded-full bg-qblack px-3 py-1 text-xs font-semibold text-white disabled:opacity-50"
                    >
                      Okundu
                    </button>
                  ) : (
                    <span className="shrink-0 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                      Okundu
                    </span>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
