"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import Link from "next/link";
import auth from "@/utils/auth";
import appConfig from "@/appConfig";
import settings from "@/utils/settings";
import Pusher from "pusher-js";
import Echo from "laravel-echo";
import {
  useSecondHandInboxQuery,
  useSecondHandConversationMessagesQuery,
  useSecondHandSendToConversationMutation,
  useSecondHandMarkConversationReadMutation,
  useSecondHandBlockUserMutation,
  useSecondHandUnblockUserMutation,
  useSecondHandReportCreateMutation,
} from "@/redux/features/secondHand/apiSlice";

export default function SecondHandMessagesModal({ open, onClose, initialConversationId }) {
  const tokenReady = !!auth()?.access_token;
  const meId = auth()?.user?.id;

  const [messageBox, setMessageBox] = useState("incoming");
  const [selectedConversationId, setSelectedConversationId] = useState(null);
  const [replyBody, setReplyBody] = useState("");
  const [pendingFiles, setPendingFiles] = useState([]);
  const [sendError, setSendError] = useState("");
  const [actionError, setActionError] = useState("");
  const [reportOpen, setReportOpen] = useState(false);
  const [reportReason, setReportReason] = useState("spam");
  const [reportDetails, setReportDetails] = useState("");
  const [mediaOpen, setMediaOpen] = useState(false);
  const [offerOpen, setOfferOpen] = useState(false);
  const [offerAmount, setOfferAmount] = useState("");
  const [liveMessages, setLiveMessages] = useState([]);
  const lastSeenTs = useRef(0);
  const lastMarkedReadId = useRef(null);
  const threadRef = useRef(null);
  const endRef = useRef(null);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const didAutoSelect = useRef(false);
  const shouldStickToBottom = useRef(true);
  const pusherInfo =
    settings()?.pusher ||
    (typeof window !== "undefined" ? JSON.parse(localStorage.getItem("pusher") || "null") : null);

  useEffect(() => {
    if (!open) return;
    if (initialConversationId) setSelectedConversationId(initialConversationId);
    didAutoSelect.current = false;
    shouldStickToBottom.current = true;
  }, [open, initialConversationId]);

  useEffect(() => {
    if (!open) return;
    // Konuşma değişince paging reset
    setPage(1);
    setHasMore(true);
    setLoadingMore(false);
  }, [open, selectedConversationId]);

  const { data: inboxData, isLoading: inboxIsLoading, isFetching: inboxIsFetching, error: inboxError } = useSecondHandInboxQuery(undefined, {
    skip: !open || !tokenReady,
    refetchOnFocus: true,
    refetchOnReconnect: true,
  });

  const conversations = useMemo(() => inboxData?.conversations?.data || [], [inboxData]);
  const selectedConversation = useMemo(() => {
    if (!selectedConversationId) return null;
    return (conversations || []).find((c) => String(c?.id) === String(selectedConversationId)) || null;
  }, [conversations, selectedConversationId]);
  const counterpartyId = selectedConversation?.counterparty_id ? Number(selectedConversation.counterparty_id) : null;

  const mediaItems = useMemo(() => {
    const out = [];
    for (const m of liveMessages || []) {
      const atts = Array.isArray(m?.attachments) ? m.attachments : [];
      for (const a of atts) {
        const href = a.local_url ? a.local_url : (a.url ? a.url : (a.path ? `${appConfig.BASE_URL}storage/${a.path}` : null));
        if (!href) continue;
        const kind = String(a.kind || "").toLowerCase();
        const mime = String(a.mime || "").toLowerCase();
        const isImg = kind === "image" || mime.startsWith("image/");
        out.push({
          key: String(a.id || href),
          href,
          isImg,
          name: a.original_name || (isImg ? "Foto" : "Dosya"),
          created_at: m.created_at,
        });
      }
    }
    // en yeni üstte
    return out.reverse();
  }, [liveMessages]);

  const incomingConversations = useMemo(() => {
    return (conversations || []).filter((c) => String(c?.counterparty_role || "") === "buyer");
  }, [conversations]);
  const outgoingConversations = useMemo(() => {
    return (conversations || []).filter((c) => String(c?.counterparty_role || "") === "seller");
  }, [conversations]);
  const visibleConversations = useMemo(() => {
    return messageBox === "outgoing" ? outgoingConversations : incomingConversations;
  }, [messageBox, incomingConversations, outgoingConversations]);

  useEffect(() => {
    if (!open) return;
    if (selectedConversationId) return;
    if (incomingConversations.length) setMessageBox("incoming");
    else if (outgoingConversations.length) setMessageBox("outgoing");
  }, [open, selectedConversationId, incomingConversations.length, outgoingConversations.length]);

  // Modal açılınca ilk konuşmayı otomatik seç (yazma alanı aktif olsun)
  useEffect(() => {
    if (!open) return;
    if (didAutoSelect.current) return;
    if (selectedConversationId) {
      didAutoSelect.current = true;
      return;
    }
    const first = visibleConversations?.[0];
    if (first?.id != null) {
      didAutoSelect.current = true;
      setSelectedConversationId(first.id);
      setReplyBody("");
    }
  }, [open, selectedConversationId, visibleConversations]);

  const scrollToBottom = () => {
    const anchor = endRef.current;
    if (!anchor) return;
    // scrollIntoView en stabil yöntem (yükseklik/paint timing sorunlarını çözer)
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        anchor.scrollIntoView({ block: "end" });
      });
    });
  };

  useEffect(() => {
    if (!open) return;
    if (!selectedConversationId) return;
    if (String(lastMarkedReadId.current || "") === String(selectedConversationId)) return;
    lastMarkedReadId.current = selectedConversationId;
    // eslint-disable-next-line react-hooks/exhaustive-deps
    markConversationRead(selectedConversationId).catch(() => {});
  }, [open, selectedConversationId]);

  const { data: threadData, isLoading: threadIsLoading, isFetching: threadIsFetching } = useSecondHandConversationMessagesQuery(
    { conversationId: selectedConversationId, page },
    {
    skip: !open || !tokenReady || !selectedConversationId,
    refetchOnFocus: true,
    refetchOnReconnect: true,
    }
  );

  const messages = useMemo(() => {
    const raw = threadData?.messages?.data || [];
    return [...raw].sort((a, b) => Number(a.id) - Number(b.id));
  }, [threadData]);

  const [sendReply] = useSecondHandSendToConversationMutation();
  const [markConversationRead] = useSecondHandMarkConversationReadMutation();
  const [blockUser, { isLoading: blockIsLoading }] = useSecondHandBlockUserMutation();
  const [unblockUser, { isLoading: unblockIsLoading }] = useSecondHandUnblockUserMutation();
  const [submitReport, { isLoading: reportIsLoading }] = useSecondHandReportCreateMutation();

  // İlk data geldikçe local listeyi seed'le (sonra realtime ile büyür)
  useEffect(() => {
    if (!open) return;
    // page=1 ise seed; page>1 ise prepend (eski mesajlar)
    if (page === 1) {
      setLiveMessages(messages);
      // açılınca alta kaydır
      scrollToBottom();
    } else if (messages.length) {
      setLiveMessages((prev) => {
        const seen = new Set((prev || []).map((m) => String(m.id)));
        const older = messages.filter((m) => !seen.has(String(m.id)));
        return [...older, ...(prev || [])];
      });
    }
    lastSeenTs.current = Date.now();

    const cur = threadData?.messages;
    if (cur?.current_page && cur?.last_page) {
      setHasMore(Number(cur.current_page) < Number(cur.last_page));
    }
  }, [open, messages]);

  // Kullanıcı aşağıdaysa yeni mesaj gelince yapışık kalsın
  useEffect(() => {
    if (!open) return;
    if (!shouldStickToBottom.current) return;
    if (liveMessages.length === 0) return;
    scrollToBottom();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open, liveMessages.length]);

  // Realtime: seçili konuşmaya yeni mesaj geldikçe anında ekle (yenileme/refetch yok)
  useEffect(() => {
    if (!open || !tokenReady) return undefined;
    if (!pusherInfo?.app_key || !pusherInfo?.app_cluster) return undefined;
    if (!selectedConversationId) return undefined;

    if (typeof window !== "undefined") window.Pusher = Pusher;

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
      if (String(msg?.conversation_id || "") !== String(selectedConversationId)) return;

      const now = Date.now();
      // aynı event'in çift gelmesine karşı basit guard
      if (now - lastSeenTs.current < 100) return;
      lastSeenTs.current = now;

      setLiveMessages((prev) => {
        const next = Array.isArray(prev) ? [...prev] : [];
        next.push({
          id: now,
          sender_id: msg.sender_id,
          sender_display: msg.sender_display,
          body: msg.body,
          attachments: Array.isArray(msg.attachments) ? msg.attachments : [],
          created_at: new Date().toISOString(),
        });
        return next;
      });
    });

    return () => {
      try {
        echo.leave(channel);
      } catch (e) {
        // ignore
      }
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open, tokenReady, pusherInfo?.app_key, pusherInfo?.app_cluster, selectedConversationId]);

  const sendReplyHandler = async () => {
    if (!selectedConversationId) return;
    const text = replyBody.trim();
    if (!text && pendingFiles.length === 0) return;
    try {
      setSendError("");
      setReplyBody("");
      const filesToSend = pendingFiles;
      setPendingFiles([]);

      // Optimistic: anında ekle (yenileme yok)
      const now = Date.now();
      setLiveMessages((prev) => [
        ...(Array.isArray(prev) ? prev : []),
        {
          id: now,
          sender_id: meId,
          sender_display: "Ben",
          body: text,
          attachments:
            filesToSend?.length > 0
              ? filesToSend.map((f) => ({
                  id: `local-${now}-${f.name}`,
                  kind: (f.type || "").startsWith("image/") ? "image" : "file",
                  original_name: f.name,
                  mime: f.type,
                  size: f.size,
                  local_url: URL.createObjectURL(f),
                }))
              : [],
          created_at: new Date().toISOString(),
        },
      ]);

      if (filesToSend.length > 0) {
        const fd = new FormData();
        if (text) fd.append("body", text);
        for (const f of filesToSend) fd.append("attachments[]", f);
        await sendReply({ conversationId: selectedConversationId, body: fd }).unwrap();
      } else {
        await sendReply({ conversationId: selectedConversationId, body: { body: text } }).unwrap();
      }
    } catch (e) {
      setSendError(e?.data?.message || "Gönderilemedi.");
    }
  };

  const sendQuickText = async (text) => {
    if (!selectedConversationId) return;
    const t = String(text || "").trim();
    if (!t) return;
    try {
      setSendError("");
      const now = Date.now();
      setLiveMessages((prev) => [
        ...(Array.isArray(prev) ? prev : []),
        {
          id: now,
          sender_id: meId,
          sender_display: "Ben",
          body: t,
          attachments: [],
          created_at: new Date().toISOString(),
        },
      ]);
      scrollToBottom();
      await sendReply({ conversationId: selectedConversationId, body: { body: t } }).unwrap();
    } catch (err) {
      setSendError(err?.data?.message || "Mesaj gönderilemedi.");
    }
  };

  const sendOffer = async () => {
    const raw = String(offerAmount || "").replace(/[^\d.,]/g, "").replace(",", ".");
    const n = Number(raw);
    if (!Number.isFinite(n) || n <= 0) {
      setSendError("Teklif tutarı geçersiz.");
      return;
    }
    const formatted = new Intl.NumberFormat("tr-TR", { maximumFractionDigits: 0 }).format(n);
    const msg = `Merhaba, ürün için teklifim: ${formatted} TL. Uygunsa görüşebiliriz.`;
    setOfferOpen(false);
    setOfferAmount("");
    await sendQuickText(msg);
  };

  const isOfferBody = (body) => {
    const t = String(body || "").trim().toLowerCase();
    return t.startsWith("merhaba, ürün için teklifim:");
  };

  const doBlock = async () => {
    if (!counterpartyId) return;
    try {
      setActionError("");
      await blockUser({ blocked_id: counterpartyId }).unwrap();
      setReportOpen(false);
      setActionError("Kullanıcı engellendi.");
    } catch (err) {
      setActionError(err?.data?.message || "Engelleme başarısız.");
    }
  };

  const doUnblock = async () => {
    if (!counterpartyId) return;
    try {
      setActionError("");
      await unblockUser(counterpartyId).unwrap();
      setActionError("Engel kaldırıldı.");
    } catch (err) {
      setActionError(err?.data?.message || "Engel kaldırılamadı.");
    }
  };

  const doReportUser = async () => {
    if (!counterpartyId) return;
    try {
      setActionError("");
      await submitReport({
        subject_type: "user",
        subject_id: counterpartyId,
        reason: reportReason,
        details: reportDetails.trim() || undefined,
      }).unwrap();
      setReportDetails("");
      setReportOpen(false);
      setActionError("Şikayetiniz alındı. Teşekkürler.");
    } catch (err) {
      const msg = err?.data?.message || "Şikayet gönderilemedi.";
      setActionError(msg);
    }
  };

  useEffect(() => {
    if (!open) return;
    const onKey = (e) => {
      if (e.key === "Escape") onClose?.();
    };
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [open, onClose]);

  useEffect(() => {
    if (!open) return;
    const prev = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      document.body.style.overflow = prev;
    };
  }, [open]);

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-[80]">
      <button type="button" className="absolute inset-0 bg-black/40" aria-label="Kapat" onClick={onClose} />
      <div
        className="
          absolute bottom-0 left-0 right-0
          bg-white shadow-2xl overflow-hidden
          rounded-t-2xl
          h-[75vh]
          md:h-[70vh]
          md:bottom-[84px] md:left-auto md:right-4 md:w-[420px] md:rounded-2xl
        "
      >
        <div className="flex h-full flex-col min-h-0">
        <div className="flex items-center justify-between gap-3 px-4 py-3 border-b border-gray-100 shrink-0">
          <div className="font-800 text-qblack">Messages</div>
          <div className="flex items-center gap-2">
            <Link
              href={`/profile${selectedConversationId ? `?c2c_conv=${encodeURIComponent(String(selectedConversationId))}` : ""}#second-hand-messages`}
              className="h-9 px-3 inline-flex items-center justify-center rounded-lg bg-qyellow text-xs font-800 text-qblack ring-1 ring-amber-900/10 hover:brightness-95 transition"
              onClick={onClose}
            >
              Profilden aç
            </Link>
            <button type="button" className="h-9 w-9 rounded-lg border border-gray-200" onClick={onClose} aria-label="Kapat">
              ✕
            </button>
          </div>
        </div>

        <div className="grid md:grid-cols-3 flex-1 min-h-0">
          <div className="md:col-span-1 border-r border-gray-100 overflow-y-auto min-h-0">
            <div className="sticky top-0 z-10 bg-white border-b border-gray-100 p-2 flex gap-2">
              <button
                type="button"
                onClick={() => setMessageBox("incoming")}
                className={`flex-1 h-9 rounded-md text-xs font-800 border ${
                  messageBox === "incoming" ? "bg-qyellow text-qblack border-qyellow" : "bg-white text-qgray border-gray-200"
                }`}
              >
                Gelen ({incomingConversations.length})
              </button>
              <button
                type="button"
                onClick={() => setMessageBox("outgoing")}
                className={`flex-1 h-9 rounded-md text-xs font-800 border ${
                  messageBox === "outgoing" ? "bg-qyellow text-qblack border-qyellow" : "bg-white text-qgray border-gray-200"
                }`}
              >
                Giden ({outgoingConversations.length})
              </button>
            </div>

            {inboxError ? (
              <div className="p-4 text-sm text-red-600">{inboxError?.data?.message || "Mesajlar yüklenemedi."}</div>
            ) : inboxIsLoading ? (
              <div className="p-4 text-sm text-qgray">Yükleniyor…</div>
            ) : visibleConversations.length === 0 ? (
              <div className="p-4 text-sm text-qgray">{messageBox === "incoming" ? "Gelen mesaj yok." : "Giden mesaj yok."}</div>
            ) : (
              <ul>
                {visibleConversations.map((c) => (
                  <li key={c.id}>
                    <button
                      type="button"
                      onClick={() => {
                        setSelectedConversationId(c.id);
                        setReplyBody("");
                      }}
                      className={`w-full text-left px-3 py-2 text-sm border-b border-gray-100 hover:bg-gray-50 ${
                        selectedConversationId === c.id ? "bg-qyellow/30" : ""
                      }`}
                    >
                      <div className="font-700 truncate">{c.listing?.title || "İlan"}</div>
                      <div className="text-[11px] text-qgray truncate">
                        {String(c?.counterparty_role || "") === "seller"
                          ? (c.seller_business_name || c.counterparty_display || "Satıcı")
                          : (c.counterparty_display || "Alıcı")}
                      </div>
                      <div className="text-xs text-qgray truncate">
                        {c.last_message_sender_display ? `${c.last_message_sender_display}: ` : ""}
                        {c.last_message_preview || "—"}
                      </div>
                      {c.unread_count > 0 && (
                        <span className="text-xs text-white bg-red-500 px-1.5 rounded">{c.unread_count}</span>
                      )}
                    </button>
                  </li>
                ))}
              </ul>
            )}
          </div>

          <div className="md:col-span-2 flex flex-col min-h-0">
            {!selectedConversationId ? (
              <div className="p-4 text-sm text-qgray">Soldan bir konuşma seçin.</div>
            ) : threadIsLoading && liveMessages.length === 0 ? (
              <div className="p-4 text-sm text-qgray">Yükleniyor…</div>
            ) : (
              <>
                <div className="px-3 py-2 border-b border-gray-100 flex items-center justify-between gap-2 shrink-0">
                  <div className="min-w-0">
                    <div className="text-xs text-qgray truncate">{selectedConversation?.listing?.title || "İlan"}</div>
                    <div className="text-sm font-800 text-qblack truncate">
                      {String(selectedConversation?.counterparty_role || "") === "seller"
                        ? (selectedConversation?.seller_business_name || selectedConversation?.counterparty_display || "Satıcı")
                        : (selectedConversation?.counterparty_display || "Alıcı")}
                    </div>
                  </div>
                  <div className="flex items-center gap-2 shrink-0">
                    <button
                      type="button"
                      onClick={() => setMediaOpen((v) => !v)}
                      className="h-8 px-3 rounded-lg border border-gray-200 text-xs font-800 text-qblack hover:bg-gray-50"
                      disabled={!selectedConversationId}
                    >
                      Medya
                    </button>
                    <button
                      type="button"
                      onClick={() => setReportOpen((v) => !v)}
                      className="h-8 px-3 rounded-lg border border-gray-200 text-xs font-800 text-qblack hover:bg-gray-50"
                      disabled={!counterpartyId}
                    >
                      Şikayet/Engelle
                    </button>
                  </div>
                </div>

                {offerOpen ? (
                  <div className="px-3 py-3 border-b border-gray-100 bg-white">
                    <div className="flex flex-wrap items-end gap-2">
                      <div className="flex-1 min-w-[180px]">
                        <label className="block text-[11px] text-qgray mb-1">Teklif tutarı (TL)</label>
                        <input
                          value={offerAmount}
                          onChange={(e) => setOfferAmount(e.target.value)}
                          inputMode="decimal"
                          className="w-full h-9 px-3 border border-gray-200 rounded-md text-sm"
                          placeholder="örn: 5000"
                        />
                      </div>
                      <button
                        type="button"
                        onClick={sendOffer}
                        className="h-9 px-3 rounded-lg bg-qblack text-white text-xs font-800"
                      >
                        Gönder
                      </button>
                      <button
                        type="button"
                        onClick={() => setOfferOpen(false)}
                        className="h-9 px-3 rounded-lg border border-gray-200 text-xs font-800 text-qblack hover:bg-gray-50"
                      >
                        Kapat
                      </button>
                    </div>
                    <div className="mt-1 text-[11px] text-qgray">
                      Bu, otomatik mesaj olarak gönderilir (Letgo tarzı).
                    </div>
                  </div>
                ) : null}

                {mediaOpen ? (
                  <div className="px-3 py-3 border-b border-gray-100 bg-white">
                    {mediaItems.length === 0 ? (
                      <div className="text-xs text-qgray">Bu konuşmada henüz ek yok.</div>
                    ) : (
                      <>
                        <div className="text-[11px] text-qgray mb-2">
                          Toplam {mediaItems.length} ek
                        </div>
                        <div className="grid grid-cols-4 sm:grid-cols-6 gap-2">
                          {mediaItems.filter((x) => x.isImg).slice(0, 24).map((it) => (
                            <a
                              key={it.key}
                              href={it.href}
                              target="_blank"
                              rel="noreferrer"
                              className="block aspect-square rounded-lg overflow-hidden border border-gray-200 bg-gray-50"
                              title={it.name}
                            >
                              {/* eslint-disable-next-line @next/next/no-img-element */}
                              <img src={it.href} alt={it.name} className="w-full h-full object-cover" />
                            </a>
                          ))}
                        </div>
                        <div className="mt-3 space-y-1">
                          {mediaItems.filter((x) => !x.isImg).slice(0, 20).map((it) => (
                            <a
                              key={it.key}
                              href={it.href}
                              target="_blank"
                              rel="noreferrer"
                              className="block text-xs underline text-blue-600 truncate"
                              title={it.name}
                            >
                              {it.name}
                            </a>
                          ))}
                        </div>
                      </>
                    )}
                  </div>
                ) : null}

                {reportOpen ? (
                  <div className="px-3 py-3 border-b border-gray-100 bg-white">
                    <div className="grid md:grid-cols-3 gap-2">
                      <div className="md:col-span-1">
                        <label className="block text-[11px] text-qgray mb-1">Sebep</label>
                        <select
                          value={reportReason}
                          onChange={(e) => setReportReason(e.target.value)}
                          className="w-full h-9 px-2 border border-gray-200 rounded-md text-sm"
                        >
                          <option value="spam">Spam</option>
                          <option value="scam">Dolandırıcılık</option>
                          <option value="harassment">Taciz</option>
                          <option value="illegal">Yasadışı içerik</option>
                          <option value="other">Diğer</option>
                        </select>
                      </div>
                      <div className="md:col-span-2">
                        <label className="block text-[11px] text-qgray mb-1">Açıklama (isteğe bağlı)</label>
                        <input
                          value={reportDetails}
                          onChange={(e) => setReportDetails(e.target.value)}
                          maxLength={2000}
                          className="w-full h-9 px-3 border border-gray-200 rounded-md text-sm"
                          placeholder="Kısa açıklama…"
                        />
                      </div>
                    </div>
                    <div className="flex flex-wrap gap-2 mt-2">
                      <button
                        type="button"
                        onClick={doReportUser}
                        disabled={reportIsLoading || !counterpartyId}
                        className="h-9 px-3 rounded-lg bg-qblack text-white text-xs font-800 disabled:opacity-50"
                      >
                        {reportIsLoading ? "Gönderiliyor…" : "Şikayet et"}
                      </button>
                      <button
                        type="button"
                        onClick={doBlock}
                        disabled={blockIsLoading || !counterpartyId}
                        className="h-9 px-3 rounded-lg border border-red-200 text-red-700 text-xs font-800 hover:bg-red-50 disabled:opacity-50"
                      >
                        {blockIsLoading ? "İşleniyor…" : "Engelle"}
                      </button>
                      <button
                        type="button"
                        onClick={doUnblock}
                        disabled={unblockIsLoading || !counterpartyId}
                        className="h-9 px-3 rounded-lg border border-gray-200 text-xs font-800 text-qblack hover:bg-gray-50 disabled:opacity-50"
                      >
                        {unblockIsLoading ? "İşleniyor…" : "Engeli kaldır"}
                      </button>
                      <button type="button" onClick={() => setReportOpen(false)} className="h-9 px-3 rounded-lg border border-gray-200 text-xs font-800 text-qblack hover:bg-gray-50">
                        Kapat
                      </button>
                    </div>
                    {actionError ? <div className="mt-2 text-xs text-qgray">{actionError}</div> : null}
                    <div className="mt-1 text-[11px] text-qgray">
                      Not: Şikayet/engelleme için ikinci el doğrulamanızın onaylı olması gerekir.
                    </div>
                  </div>
                ) : null}

                <div
                  className="flex-1 min-h-0 overflow-y-auto p-3 space-y-2"
                  ref={threadRef}
                  onScroll={(e) => {
                    const el = e.currentTarget;
                    const distance = el.scrollHeight - el.scrollTop - el.clientHeight;
                    shouldStickToBottom.current = distance < 80;
                  }}
                >
                  {hasMore ? (
                    <div className="flex justify-center pb-2">
                      <button
                        type="button"
                        disabled={loadingMore || threadIsFetching}
                        onClick={async () => {
                          if (!hasMore || loadingMore) return;
                          setLoadingMore(true);
                          setPage((p) => p + 1);
                          setTimeout(() => setLoadingMore(false), 300);
                        }}
                        className="h-9 px-3 rounded-lg border border-gray-200 text-xs font-800 text-qblack hover:bg-gray-50 disabled:opacity-50"
                      >
                        {loadingMore ? "Yükleniyor…" : "Eski mesajları yükle"}
                      </button>
                    </div>
                  ) : null}
                  {liveMessages.map((m) => (
                    <div
                      key={m.id}
                      className={`text-sm p-2 rounded ${
                        isOfferBody(m.body)
                          ? "bg-amber-50 border border-amber-200"
                          : (meId && m.sender_id === meId ? "bg-gray-100 ml-8" : "bg-blue-50 mr-8")
                      }`}
                    >
                      <div className="text-[10px] text-qgray mb-1 flex items-center gap-2">
                        <span>{meId && m.sender_id === meId ? "Ben" : (m.sender_display || "Karşı taraf")}</span>
                        {isOfferBody(m.body) ? (
                          <span className="inline-flex items-center rounded-full bg-amber-200/70 text-amber-900 px-2 py-0.5 text-[10px] font-900">
                            Teklif
                          </span>
                        ) : null}
                      </div>
                      <div className="whitespace-pre-wrap">{m.body}</div>
                      {Array.isArray(m.attachments) && m.attachments.length > 0 ? (
                        <div className="mt-2 flex flex-wrap gap-2">
                          {m.attachments.map((a) => {
                            const href = a.local_url
                              ? a.local_url
                              : (a.url ? a.url : (a.path ? `${appConfig.BASE_URL}storage/${a.path}` : "#"));
                            const isImg = String(a.kind || "").toLowerCase() === "image" || String(a.mime || "").startsWith("image/");
                            return (
                              <a
                                key={a.id || href}
                                href={href}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-2.5 py-2 text-xs"
                              >
                                <span className="font-800">{isImg ? "Foto" : "Dosya"}</span>
                                <span className="max-w-[160px] truncate">{a.original_name || "Ek"}</span>
                              </a>
                            );
                          })}
                        </div>
                      ) : null}
                      <div className="text-[10px] text-qgray mt-1">{m.created_at ? new Date(m.created_at).toLocaleString("tr-TR") : ""}</div>
                    </div>
                  ))}
                  <div ref={endRef} />
                </div>

                <div className="px-3 pt-2 border-t border-gray-100 bg-white shrink-0">
                  <div className="flex flex-wrap gap-2">
                    <button
                      type="button"
                      onClick={() => sendQuickText("Merhaba, ürün halen satılık mı?")}
                      className="h-8 px-3 rounded-full border border-gray-200 text-xs font-800 text-qblack hover:bg-gray-50 disabled:opacity-50"
                      disabled={!selectedConversationId}
                    >
                      Satılık mı?
                    </button>
                    <button
                      type="button"
                      onClick={() => {
                        setOfferOpen((v) => !v);
                        setSendError("");
                      }}
                      className="h-8 px-3 rounded-full border border-gray-200 text-xs font-800 text-qblack hover:bg-gray-50 disabled:opacity-50"
                      disabled={!selectedConversationId}
                    >
                      Teklif yap
                    </button>
                  </div>
                </div>

                <div className="p-2 border-t border-gray-200 flex gap-2 shrink-0 bg-white">
                  <label className="h-10 w-10 inline-flex items-center justify-center rounded-md border border-gray-200 cursor-pointer hover:bg-gray-50">
                    <input
                      type="file"
                      multiple
                      accept="image/*,.pdf,.heic,.heif"
                      className="hidden"
                      onChange={(e) => {
                        const picked = Array.from(e.target.files || []);
                        if (!picked.length) return;
                        setPendingFiles((prev) => [...(prev || []), ...picked].slice(0, 3));
                        e.target.value = "";
                      }}
                    />
                    +
                  </label>
                  <input
                    value={replyBody}
                    onChange={(e) => setReplyBody(e.target.value)}
                    placeholder={selectedConversationId ? "Yanıt yazın…" : "Önce bir konuşma seçin…"}
                    disabled={!selectedConversationId}
                    className="flex-1 h-10 px-3 border border-gray-200 rounded-md text-sm disabled:bg-gray-50 disabled:text-qgray disabled:cursor-not-allowed"
                    onKeyDown={(e) => {
                      if (e.key === "Enter" && !e.shiftKey) {
                        e.preventDefault();
                        sendReplyHandler();
                      }
                    }}
                  />
                  <button
                    type="button"
                    disabled={!selectedConversationId || (!replyBody.trim() && pendingFiles.length === 0)}
                    onClick={sendReplyHandler}
                    className="h-10 px-4 bg-qblack text-white rounded-md text-sm font-800 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Gönder
                  </button>
                </div>
                {sendError ? (
                  <div className="px-3 pb-2 text-xs text-red-600">{sendError}</div>
                ) : null}
                {pendingFiles.length > 0 ? (
                  <div className="px-3 pb-2 text-xs text-qgray flex flex-wrap gap-2">
                    {pendingFiles.map((f) => (
                      <span key={`${f.name}-${f.size}`} className="inline-flex items-center gap-2 rounded-full bg-gray-100 px-2 py-1">
                        {f.name}
                        <button
                          type="button"
                          className="text-qblack"
                          onClick={() => setPendingFiles((prev) => (prev || []).filter((x) => x !== f))}
                          aria-label="Dosyayı kaldır"
                        >
                          ✕
                        </button>
                      </span>
                    ))}
                  </div>
                ) : null}
              </>
            )}
          </div>
        </div>
        </div>
      </div>
    </div>
  );
}

