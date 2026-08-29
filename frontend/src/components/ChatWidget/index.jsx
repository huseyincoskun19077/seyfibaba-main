"use client";
import { useState, useRef, useEffect, useCallback } from "react";
import { useSelector } from "react-redux";
import appConfig from "@/appConfig";

const generateSessionId = () =>
  `sess_${Date.now()}_${Math.random().toString(36).substring(2, 10)}`;

export default function ChatWidget() {
  const [isOpen, setIsOpen] = useState(false);
  const [messages, setMessages] = useState([]);
  const [input, setInput] = useState("");
  const [isTyping, setIsTyping] = useState(false);
  const [sessionId] = useState(() => {
    if (typeof window !== "undefined") {
      const stored = localStorage.getItem("ai_chat_session_id");
      if (stored) return stored;
      const id = generateSessionId();
      localStorage.setItem("ai_chat_session_id", id);
      return id;
    }
    return generateSessionId();
  });
  const [enabled, setEnabled] = useState(null);

  const messagesEndRef = useRef(null);
  const inputRef = useRef(null);

  // Get auth token from redux
  const { userInfo } = useSelector((state) => state.auth) || {};
  const token = userInfo?.token;

  // Load messages from localStorage on mount
  useEffect(() => {
    if (typeof window !== "undefined") {
      try {
        const stored = localStorage.getItem("ai_chat_messages");
        if (stored) setMessages(JSON.parse(stored).slice(-50));
      } catch {}
    }
  }, []);

  // Save messages to localStorage
  useEffect(() => {
    if (typeof window !== "undefined" && messages.length > 0) {
      localStorage.setItem(
        "ai_chat_messages",
        JSON.stringify(messages.slice(-50))
      );
    }
  }, [messages]);

  // Check if AI chat is enabled
  useEffect(() => {
    fetch(`${appConfig.BASE_URL}api/ai-chat/status`)
      .then((res) => {
        if (!res.ok) return null;
        return res.json();
      })
      .then((data) => {
        if (data?.success) {
          const canUse = data.data.enabled && (token || data.data.guest_enabled);
          setEnabled(canUse);
        } else {
          setEnabled(false);
        }
      })
      .catch(() => setEnabled(false));
  }, [token]);

  // Auto-scroll
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages, isTyping]);

  // Focus input on open
  useEffect(() => {
    if (isOpen) setTimeout(() => inputRef.current?.focus(), 100);
  }, [isOpen]);

  const handleSend = useCallback(async () => {
    const trimmed = input.trim();
    if (!trimmed || isTyping) return;

    setMessages((prev) => [...prev, { role: "user", content: trimmed }]);
    setInput("");
    setIsTyping(true);

    try {
      const endpoint = token
        ? `${appConfig.BASE_URL}api/user/ai-chat/send`
        : `${appConfig.BASE_URL}api/ai-chat/guest/send`;

      const headers = {
        "Content-Type": "application/json",
        Accept: "application/json",
      };
      if (token) headers["Authorization"] = `Bearer ${token}`;

      const res = await fetch(endpoint, {
        method: "POST",
        headers,
        body: JSON.stringify({ message: trimmed, session_id: sessionId }),
      });

      const data = await res.json();

      if (data.success) {
        setMessages((prev) => [
          ...prev,
          { role: "assistant", content: data.data.message },
        ]);
      } else {
        setMessages((prev) => [
          ...prev,
          {
            role: "assistant",
            content: data.message || "Bir hata oluştu. Lütfen tekrar deneyin.",
          },
        ]);
      }
    } catch {
      setMessages((prev) => [
        ...prev,
        { role: "assistant", content: "Bağlantı hatası. Lütfen tekrar deneyin." },
      ]);
    } finally {
      setIsTyping(false);
    }
  }, [input, isTyping, token, sessionId]);

  const clearMessages = () => {
    setMessages([]);
    localStorage.removeItem("ai_chat_messages");
    const newId = generateSessionId();
    localStorage.setItem("ai_chat_session_id", newId);
  };

  // Don't render if not enabled or still loading
  if (enabled === null || enabled === false) return null;

  return (
    <>
      {/* Floating Button */}
      {!isOpen && (
        <button
          onClick={() => setIsOpen(true)}
          className="fixed z-[9990] group bottom-6 left-6 max-md:bottom-[calc(5.5rem+env(safe-area-inset-bottom,0px))] max-md:left-auto max-md:right-4 md:bottom-6 md:left-6 md:right-auto"
          aria-label="AI Chat Aç"
        >
          <div className="relative">
            <div className="absolute inset-0 rounded-full bg-gradient-to-r from-qyellow to-yellow-500 opacity-75 blur-md transition-opacity group-hover:opacity-100" />
            <div className="relative flex h-14 w-14 items-center justify-center rounded-full bg-white shadow-lg transition-transform group-hover:scale-110 border border-gray-200">
              <svg
                width="28"
                height="28"
                viewBox="0 0 24 24"
                fill="none"
                stroke="#ffbb38"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
              >
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                <circle cx="9" cy="10" r="1" fill="#ffbb38" />
                <circle cx="12" cy="10" r="1" fill="#ffbb38" />
                <circle cx="15" cy="10" r="1" fill="#ffbb38" />
              </svg>
            </div>
            <div className="absolute inset-0 rounded-full bg-qyellow opacity-0 animate-ping" />
          </div>
        </button>
      )}

      {/* Chat Panel */}
      {isOpen && (
        <div className="fixed z-[9990] flex flex-col bottom-6 left-6 max-md:bottom-[calc(5.5rem+env(safe-area-inset-bottom,0px))] max-md:left-4 max-md:right-4 md:bottom-6 md:left-6 md:right-auto w-[350px] max-w-[calc(100vw-2rem)] h-[480px] max-md:h-[min(480px,calc(100vh-8rem))] rounded-2xl bg-white shadow-2xl border border-gray-200 overflow-hidden">
          {/* Header */}
          <div className="relative overflow-hidden bg-qyellow px-4 py-4">
            <div className="relative flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-white/30 backdrop-blur-sm">
                  <svg
                    width="22"
                    height="22"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="#222"
                    strokeWidth="2"
                  >
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                  </svg>
                </div>
                <div>
                  <h3 className="text-sm font-semibold text-[#222] flex items-center gap-1.5">
                    AI Asistan
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="#222" stroke="none">
                      <path d="M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 16.8l-6.2 4.5 2.4-7.4L2 9.4h7.6z" />
                    </svg>
                  </h3>
                  <p className="text-xs text-[#222]/70">Her zaman online</p>
                </div>
              </div>
              <button
                onClick={() => setIsOpen(false)}
                className="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white/30 text-[#222] transition-colors hover:bg-white/50"
                aria-label="Kapat"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <line x1="18" y1="6" x2="6" y2="18" />
                  <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
              </button>
            </div>
          </div>

          {/* Messages */}
          <div className="flex-1 space-y-3 overflow-y-auto bg-gray-50 p-4">
            {messages.length === 0 && (
              <div className="flex flex-col items-center justify-center py-12 text-center">
                <div className="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-yellow-50">
                  <svg width="32" height="32" viewBox="0 0 24 24" fill="#ffbb38" stroke="none">
                    <path d="M12 2l2.4 7.4H22l-6.2 4.5 2.4 7.4L12 16.8l-6.2 4.5 2.4-7.4L2 9.4h7.6z" />
                  </svg>
                </div>
                <p className="text-sm text-gray-700">Merhaba! Size nasıl yardımcı olabilirim?</p>
                <p className="mt-1 text-xs text-gray-400">Ürünler, siparişler veya herhangi bir konuda sorabilirsiniz.</p>
              </div>
            )}

            {messages.map((msg, i) => (
              <div
                key={i}
                className={`flex ${msg.role === "user" ? "justify-end" : "justify-start"}`}
              >
                <div
                  className={`max-w-[85%] whitespace-pre-wrap rounded-2xl px-4 py-2.5 text-sm shadow-sm ${
                    msg.role === "user"
                      ? "bg-qyellow text-[#222]"
                      : "bg-white text-gray-800 border border-gray-200"
                  }`}
                >
                  {msg.content}
                </div>
              </div>
            ))}

            {isTyping && (
              <div className="flex justify-start">
                <div className="rounded-2xl bg-white border border-gray-200 px-4 py-3 shadow-sm">
                  <div className="flex items-center gap-1.5">
                    <span className="h-2 w-2 animate-bounce rounded-full bg-gray-400" style={{ animationDelay: "0ms" }} />
                    <span className="h-2 w-2 animate-bounce rounded-full bg-gray-400" style={{ animationDelay: "150ms" }} />
                    <span className="h-2 w-2 animate-bounce rounded-full bg-gray-400" style={{ animationDelay: "300ms" }} />
                  </div>
                </div>
              </div>
            )}
            <div ref={messagesEndRef} />
          </div>

          {/* Input */}
          <div className="border-t bg-white p-3">
            {messages.length > 0 && (
              <div className="mb-2 flex justify-end">
                <button
                  onClick={clearMessages}
                  className="flex items-center gap-1.5 rounded-lg px-2 py-1 text-xs text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500"
                >
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <polyline points="3,6 5,6 21,6" />
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                  </svg>
                  Temizle
                </button>
              </div>
            )}
            <form
              onSubmit={(e) => {
                e.preventDefault();
                handleSend();
              }}
              className="flex gap-2"
            >
              <input
                ref={inputRef}
                type="text"
                value={input}
                onChange={(e) => setInput(e.target.value)}
                placeholder="Mesajınızı yazın..."
                className="flex-1 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-800 placeholder-gray-400 focus:border-qyellow focus:bg-white focus:outline-none focus:ring-2 focus:ring-qyellow/20"
                disabled={isTyping}
                maxLength={2000}
              />
              <button
                type="submit"
                disabled={!input.trim() || isTyping}
                className="flex h-10 w-10 items-center justify-center rounded-xl bg-qyellow text-[#222] shadow-sm transition-all hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <line x1="22" y1="2" x2="11" y2="13" />
                  <polygon points="22,2 15,22 11,13 2,9" />
                </svg>
              </button>
            </form>
          </div>
        </div>
      )}
    </>
  );
}
