"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import Link from "next/link";
import auth from "@/utils/auth";
import appConfig from "@/appConfig";
import settings from "@/utils/settings";
import Pusher from "pusher-js";
import Echo from "laravel-echo";
import { useSecondHandInboxQuery } from "@/redux/features/secondHand/apiSlice";
import SecondHandMessagesModal from "@/components/SecondHand/SecondHandMessagesModal";
import { marketplaceUrl } from "@/utils/secondHandSite";

export default function SecondHandMessagesDock() {
  const session = auth();
  const tokenReady = !!session?.access_token;

  const [toast, setToast] = useState(null);
  const [modalOpen, setModalOpen] = useState(false);
  const [modalConvId, setModalConvId] = useState(null);
  const hideTimer = useRef(null);

  const pusherInfo =
    settings()?.pusher ||
    (typeof window !== "undefined"
      ? JSON.parse(localStorage.getItem("pusher") || "null")
      : null);

  const { data: inboxData } = useSecondHandInboxQuery(undefined, {
    skip: !tokenReady,
    refetchOnFocus: true,
    refetchOnReconnect: true,
  });

  const conversations = inboxData?.conversations?.data || [];
  const totalUnread = useMemo(() => {
    return conversations.reduce((sum, c) => sum + Number(c.unread_count || 0), 0);
  }, [conversations]);

  const playBeep = () => {
    try {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (!AudioCtx) return;
      const ctx = new AudioCtx();
      const o = ctx.createOscillator();
      const g = ctx.createGain();
      o.type = "sine";
      o.frequency.value = 880;
      g.gain.value = 0.04;
      o.connect(g);
      g.connect(ctx.destination);
      o.start();
      setTimeout(() => {
        o.stop();
        ctx.close().catch(() => {});
      }, 180);
    } catch (e) {
      // ignore
    }
  };

  useEffect(() => {
    if (!tokenReady) return undefined;
    if (!pusherInfo?.app_key || !pusherInfo?.app_cluster) return undefined;

    if (typeof window !== "undefined") {
      window.Pusher = Pusher;
    }

    const echo = new Echo({
      broadcaster: "pusher",
      key: pusherInfo.app_key,
      cluster: pusherInfo.app_cluster,
      forceTLS: true,
      encrypted: false,
      authEndpoint: appConfig.BASE_URL + "api/broadcasting/auth",
      auth: {
        headers: {
          Authorization: `Bearer ${auth()?.access_token || ""}`,
          Accept: "application/json",
        },
      },
    });

    const channel = `second-hand-message.${auth()?.user?.id}`;
    echo.private(channel).listen("SecondHandMessageSent", (event) => {
      const msg = event?.message || {};
      setToast({
        conversation_id: msg.conversation_id,
        title: msg.listing_title || "Yeni mesaj",
        body: String(msg.body || "").slice(0, 120),
      });
      playBeep();
      if (hideTimer.current) clearTimeout(hideTimer.current);
      hideTimer.current = setTimeout(() => setToast(null), 8000);
    });

    return () => {
      try {
        echo.leave(channel);
      } catch (e) {
        // ignore
      }
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [tokenReady, pusherInfo?.app_key, pusherInfo?.app_cluster]);

  const openModal = (conversationId) => {
    if (conversationId) setModalConvId(conversationId);
    setModalOpen(true);
    setToast(null);
  };

  const loginHref = marketplaceUrl("/login");

  // Giriş yoksa sağda Bionluk tarzı sekme: tıklanınca login
  if (!tokenReady) {
    return (
      <div className="fixed right-0 top-1/2 z-[60] -translate-y-1/2 pointer-events-none">
        <Link
          href={loginHref}
          className="pointer-events-auto flex flex-col items-center gap-2 rounded-l-xl bg-qblack text-white px-2.5 py-4 shadow-[0_8px_24px_rgba(0,0,0,0.25)] hover:bg-neutral-800 transition"
          aria-label="Mesajlar için giriş yap"
        >
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden>
            <path
              d="M21 15a4 4 0 01-4 4H8l-5 3V7a4 4 0 014-4h10a4 4 0 014 4v8z"
              stroke="currentColor"
              strokeWidth="2"
              strokeLinejoin="round"
            />
          </svg>
          <span
            className="text-[11px] font-800 tracking-wide"
            style={{ writingMode: "vertical-rl", transform: "rotate(180deg)" }}
          >
            Mesajlar
          </span>
        </Link>
      </div>
    );
  }

  return (
    <>
      <SecondHandMessagesModal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        initialConversationId={modalConvId || toast?.conversation_id || null}
        variant="dock"
      />

      {!modalOpen ? (
        <div className="fixed right-0 top-1/2 z-[60] -translate-y-1/2 pointer-events-none">
          <div className="pointer-events-auto flex flex-col items-end gap-2 pr-0">
            {toast ? (
              <button
                type="button"
                onClick={() => openModal(toast.conversation_id || null)}
                className="mr-2 max-w-[260px] rounded-xl bg-white text-left shadow-[0_10px_28px_rgba(0,0,0,0.18)] border border-gray-100 px-3 py-2 ring-2 ring-qyellow/60"
              >
                <span className="block text-xs font-800 text-qblack truncate">{toast.title}</span>
                <span className="block text-[11px] text-qgray truncate">{toast.body}</span>
              </button>
            ) : null}

            <button
              type="button"
              onClick={() => openModal(toast?.conversation_id || null)}
              className={`relative flex flex-col items-center gap-2 rounded-l-xl bg-qblack text-white px-2.5 py-4 shadow-[0_8px_24px_rgba(0,0,0,0.25)] hover:bg-neutral-800 transition ${
                toast ? "ring-2 ring-qyellow/70" : ""
              }`}
              aria-label="İkinci el mesajları"
            >
              <span className="relative inline-flex">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden>
                  <path
                    d="M21 15a4 4 0 01-4 4H8l-5 3V7a4 4 0 014-4h10a4 4 0 014 4v8z"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinejoin="round"
                  />
                </svg>
                {totalUnread > 0 ? (
                  <span className="absolute -top-2 -right-3 min-w-[18px] h-[18px] px-1 rounded-full bg-qyellow text-qblack text-[10px] font-900 inline-flex items-center justify-center">
                    {totalUnread > 99 ? "99+" : totalUnread}
                  </span>
                ) : null}
              </span>
              <span
                className="text-[11px] font-800 tracking-wide"
                style={{ writingMode: "vertical-rl", transform: "rotate(180deg)" }}
              >
                Mesajlar
              </span>
            </button>
          </div>
        </div>
      ) : null}
    </>
  );
}
