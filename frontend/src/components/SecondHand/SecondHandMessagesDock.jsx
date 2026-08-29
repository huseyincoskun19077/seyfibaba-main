"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { usePathname } from "next/navigation";
import auth from "@/utils/auth";
import appConfig from "@/appConfig";
import settings from "@/utils/settings";
import Pusher from "pusher-js";
import Echo from "laravel-echo";
import { useSecondHandInboxQuery } from "@/redux/features/secondHand/apiSlice";
import SecondHandMessagesModal from "@/components/SecondHand/SecondHandMessagesModal";

export default function SecondHandMessagesDock() {
  const pathname = usePathname();
  const session = auth();
  const tokenReady = !!session?.access_token;

  const [toast, setToast] = useState(null);
  const [modalOpen, setModalOpen] = useState(false);
  const [modalConvId, setModalConvId] = useState(null);
  const hideTimer = useRef(null);

  const pusherInfo = settings()?.pusher || (typeof window !== "undefined" ? JSON.parse(localStorage.getItem("pusher") || "null") : null);

  // Kalıcı küçük buton için: polling yok, sadece ilk load + focus/reconnect.
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

  if (!tokenReady) return null;

  // Form ağırlıklı sayfalarda dock içeriğin üzerine binmemesi için gizle
  const HIDDEN_PATHS = ["/profile", "/checkout", "/payment", "/cart"];
  if (HIDDEN_PATHS.some((p) => pathname.startsWith(p))) return null;

  // Letgo benzeri: kullanıcı giriş yaptıysa "Mesajlar" her zaman erişilebilir olmalı.
  // Inbox 403 (doğrulama yok) olsa bile modal içinde kullanıcıyı yönlendireceğiz.

  const openModal = (conversationId) => {
    if (conversationId) setModalConvId(conversationId);
    setModalOpen(true);
    setToast(null);
  };

  return (
    <>
      <SecondHandMessagesModal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        initialConversationId={modalConvId || toast?.conversation_id || null}
      />

      {!modalOpen ? (
      <div className="fixed left-0 right-0 z-[60] pointer-events-none" style={{ bottom: "max(12px, env(safe-area-inset-bottom))" }}>
        <div className="container-x mx-auto px-4">
          <div className="pointer-events-auto flex justify-end">
            <button
              type="button"
              onClick={() => openModal(toast?.conversation_id || null)}
              className={`inline-flex items-center gap-3 rounded-2xl shadow-[0_12px_30px_rgba(0,0,0,0.22)] bg-qblack text-white px-4 py-3 w-[min(92vw,420px)] transition ${
                toast ? "ring-2 ring-qyellow/70" : ""
              }`}
              aria-label="İkinci el mesajları"
            >
              <span className="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/10">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden>
                  <path d="M21 15a4 4 0 01-4 4H8l-5 3V7a4 4 0 014-4h10a4 4 0 014 4v8z" stroke="currentColor" strokeWidth="2" strokeLinejoin="round" />
                </svg>
              </span>
              <span className="min-w-0 flex-1 text-left">
                <span className="block text-sm font-800 truncate">Mesajlar</span>
                <span className="block text-[12px] text-white/80 truncate">
                  {toast
                    ? `${toast.title}: ${toast.body}`
                    : (conversations.length ? "Konuşmalarını aç" : "İkinci el mesajlarını aç")}
                </span>
              </span>
              {totalUnread > 0 ? (
                <span className="min-w-[22px] h-[22px] px-1.5 rounded-full bg-qyellow text-qblack text-[11px] font-900 inline-flex items-center justify-center">
                  {totalUnread}
                </span>
              ) : null}
            </button>
          </div>
        </div>
      </div>
      ) : null}
    </>
  );
}

