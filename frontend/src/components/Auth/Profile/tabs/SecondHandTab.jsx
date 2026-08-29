"use client";

import { useEffect, useMemo, useRef, useState, lazy, Suspense, memo } from "react";
import Link from "next/link";
import { useSelector } from "react-redux";
import { toast } from "react-toastify";
import apiRoutes from "@/appConfig/apiRoutes";
import auth from "@/utils/auth";
import appConfig from "@/appConfig";
import { getSecondHandListingSeoPath } from "@/api/secondHandPublic";
import { secondHandListingUrl, secondHandPageUrl } from "@/utils/secondHandSite";
import ConsentModal from "@/components/Helpers/ConsentModal";
import LegalConsentCheckboxes, { allRequiredChecked } from "@/components/Legal/LegalConsentCheckboxes";
import { SECOND_HAND_REQUIRED_CONSENTS } from "@/config/legalDocuments";
import { recordLegalConsents } from "@/api/recordLegalConsents";
import {
  useSecondHandVerificationQuery,
  useSecondHandVerificationSubmitMutation,
  useSecondHandMyListingsQuery,
  useSecondHandCreateDraftMutation,
  useSecondHandUpdateDraftMutation,
  useSecondHandPublishListingMutation,
  useSecondHandUploadListingImageMutation,
  useSecondHandDeleteListingImageMutation,
  useSecondHandDeactivateListingMutation,
  useSecondHandActivateListingMutation,
  useSecondHandMarkSoldListingMutation,
  useSecondHandInboxQuery,
  useSecondHandConversationMessagesQuery,
  useSecondHandSendToConversationMutation,
  useSecondHandMarkConversationReadMutation,
  useSecondHandBlockUserMutation,
  useSecondHandUnblockUserMutation,
  useSecondHandReportCreateMutation,
} from "@/redux/features/secondHand/apiSlice";

const TurkeyAddressSelects = lazy(() => import("@/components/SecondHand/TurkeyAddressSelects"));

function TurkeyAddressFallback() {
  return (
    <div className="rounded-lg border border-dashed border-gray-200 bg-gray-50/80 px-3 py-4 text-center text-sm text-qgray">
      Adres seçicileri yükleniyor…
    </div>
  );
}

const STATUS_LABEL = {
  draft: "Taslak",
  pending: "Onay bekliyor",
  active: "Yayında",
  inactive: "Pasif",
  rejected: "Reddedildi",
  sold: "Satıldı",
};

const VERIFICATION_LABEL = {
  pending: "İnceleniyor",
  approved: "Onaylı",
  rejected: "Reddedildi",
};

function listingImageSrc(imageId) {
  if (!imageId) return null;
  return `${apiRoutes.secondHandListingImage}${imageId}`;
}

function formatTry(value) {
  const n = Number(value);
  if (Number.isNaN(n)) return "—";
  return new Intl.NumberFormat("tr-TR", {
    style: "currency",
    currency: "TRY",
    maximumFractionDigits: 0,
  }).format(n);
}

/** Web kullanıcı JWT ile /api/user/ai/generate-content */
function secondHandAiFetchHeaders() {
  const token = auth()?.access_token;
  const headers = { "Content-Type": "application/json", Accept: "application/json" };
  if (token) headers.Authorization = `Bearer ${token}`;
  return headers;
}

function SecondHandApprovedTick({ className = "", title = "Doğrulandı" }) {
  return (
    <span
      className={`inline-flex items-center justify-center rounded-full bg-green-600 text-white shadow-sm ${className}`}
      title={title}
      aria-label={title}
    >
      <svg className="w-[55%] h-[55%]" viewBox="0 0 20 20" fill="currentColor" aria-hidden>
        <path
          fillRule="evenodd"
          d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
          clipRule="evenodd"
        />
      </svg>
    </span>
  );
}

/** Mevcut kategori ağacından seçili id'leri çöz (düzenleme formu için). */
function resolveCategorySelection(categories, categoryId, subCategoryId, childCategoryId) {
  const out = { main: "", sub: "", child: "" };
  const cid = categoryId != null ? Number(categoryId) : null;
  const sid = subCategoryId != null ? Number(subCategoryId) : null;
  const chid = childCategoryId != null ? Number(childCategoryId) : null;
  if (!cid || !categories?.length) return out;
  for (const c of categories) {
    if (Number(c.id) !== cid) continue;
    out.main = String(c.id);
    if (!sid) return out;
    for (const s of c.active_sub_categories || []) {
      if (Number(s.id) !== sid) continue;
      out.sub = String(s.id);
      if (!chid) return out;
      for (const ch of s.active_child_categories || []) {
        if (Number(ch.id) === chid) {
          out.child = String(ch.id);
          return out;
        }
      }
      return out;
    }
    return out;
  }
  return out;
}

function SecondHandTab({ subNav = "" }) {
  const SECOND_HAND_CONSENT_STORAGE_KEY = "second_hand_verification_consents_v1";
  const tokenReady = !!auth()?.access_token;
  const [section, setSection] = useState("verification");
  const [listPage, setListPage] = useState(1);
  const [listStatus, setListStatus] = useState("");
  const [listCondition, setListCondition] = useState("");
  const [listSearchInput, setListSearchInput] = useState("");
  const [listSearch, setListSearch] = useState("");
  const [selectedConversationId, setSelectedConversationId] = useState(null);
  const lastMarkedReadId = useRef(null);
  const [replyBody, setReplyBody] = useState("");
  const [replyFiles, setReplyFiles] = useState([]);
  const [actionsOpen, setActionsOpen] = useState(false);
  const [actionError, setActionError] = useState("");
  const [reportReason, setReportReason] = useState("spam");
  const [reportDetails, setReportDetails] = useState("");
  const [mediaOpen, setMediaOpen] = useState(false);
  const [offerOpen, setOfferOpen] = useState(false);
  const [offerAmount, setOfferAmount] = useState("");
  const [messageBox, setMessageBox] = useState("incoming"); // incoming: bana gelen (satıcı), outgoing: benim yazdıklarım (alıcı)
  const [acceptTerms, setAcceptTerms] = useState(false);
  const [acceptPrivacy, setAcceptPrivacy] = useState(false);
  const [consentModal, setConsentModal] = useState("");

  const {
    data: verData,
    isLoading: verIsLoading,
    isFetching: verIsFetching,
  } = useSecondHandVerificationQuery(undefined, {
    skip: !tokenReady,
  });
  const [submitVerification, { isLoading: verSubmitting }] = useSecondHandVerificationSubmitMutation();

  const verification = verData?.verification;
  const isApproved = verification?.status === "approved";

  useEffect(() => {
    const k = (subNav || "").trim().toLowerCase();
    if (k === "messages") {
      setSection("messages");
    } else if (k === "verification" || k === "") {
      setSection("verification");
    } else if (k === "add" && isApproved) {
      setSection("create");
    } else if (k === "listings" && isApproved) {
      setSection("listings");
    } else if (k === "listings" || k === "add") {
      setSection("verification");
    }
  }, [subNav, isApproved]);

  useEffect(() => {
    if (typeof window === "undefined") return;
    if (section !== "messages") return;
    if (selectedConversationId) return;
    const conv = new URLSearchParams(window.location.search).get("c2c_conv");
    if (conv) setSelectedConversationId(Number(conv) || conv);
  }, [section, isApproved, selectedConversationId]);

  useEffect(() => {
    if ((subNav || "").trim().toLowerCase() !== "add" || !isApproved) {
      return undefined;
    }
    const t = window.setTimeout(() => {
      document.getElementById("second-hand-create-form")?.scrollIntoView({ behavior: "smooth", block: "start" });
    }, 180);
    return () => clearTimeout(t);
  }, [subNav, isApproved]);

  useEffect(() => {
    const t = window.setTimeout(() => {
      setListSearch(listSearchInput.trim());
      setListPage(1);
    }, 400);
    return () => clearTimeout(t);
  }, [listSearchInput]);

  useEffect(() => {
    if (typeof window === "undefined") return;
    try {
      const raw = localStorage.getItem(SECOND_HAND_CONSENT_STORAGE_KEY);
      if (!raw) return;
      const parsed = JSON.parse(raw);
      setAcceptTerms(Boolean(parsed?.acceptTerms));
      setAcceptPrivacy(Boolean(parsed?.acceptPrivacy));
    } catch {
      // ignore invalid data
    }
  }, []);

  useEffect(() => {
    if (typeof window === "undefined") return;
    try {
      localStorage.setItem(
        SECOND_HAND_CONSENT_STORAGE_KEY,
        JSON.stringify({ acceptTerms, acceptPrivacy })
      );
    } catch {
      // storage may be unavailable
    }
  }, [acceptTerms, acceptPrivacy]);

  const { data: listingsData, isFetching: listingsLoading, refetch: refetchMyListings } = useSecondHandMyListingsQuery(
    {
      page: listPage,
      status: listStatus || undefined,
      q: listSearch || undefined,
      condition: listCondition || undefined,
    },
    { skip: !tokenReady || !isApproved }
  );

  const { data: inboxData, isLoading: inboxIsLoading, isFetching: inboxIsFetching, error: inboxError } = useSecondHandInboxQuery(undefined, {
    // Hız: diğer sekmelerde inbox çekme.
    skip: !tokenReady || section !== "messages",
    refetchOnFocus: true,
    refetchOnReconnect: true,
  });

  const { data: threadData, isLoading: threadIsLoading, isFetching: threadIsFetching } = useSecondHandConversationMessagesQuery(
    selectedConversationId,
    { skip: !tokenReady || !selectedConversationId }
  );

  const [createDraft, { isLoading: creating }] = useSecondHandCreateDraftMutation();
  const [updateDraft] = useSecondHandUpdateDraftMutation();
  const [publishListing] = useSecondHandPublishListingMutation();
  const [uploadImage] = useSecondHandUploadListingImageMutation();
  const [deleteImage] = useSecondHandDeleteListingImageMutation();
  const [deactivateListing] = useSecondHandDeactivateListingMutation();
  const [activateListing] = useSecondHandActivateListingMutation();
  const [markSold] = useSecondHandMarkSoldListingMutation();
  const [sendReply] = useSecondHandSendToConversationMutation();
  const [markConversationRead] = useSecondHandMarkConversationReadMutation();
  const [blockUser, { isLoading: blockIsLoading }] = useSecondHandBlockUserMutation();
  const [unblockUser, { isLoading: unblockIsLoading }] = useSecondHandUnblockUserMutation();
  const [submitReport, { isLoading: reportIsLoading }] = useSecondHandReportCreateMutation();

  const meId = auth()?.user?.id;

  const productCategories = useSelector((s) => s.websiteSetup?.websiteSetup?.payload?.productCategories) || [];
  const secondHandCategories = useMemo(() => {
    return (productCategories || []).filter((c) => {
      const hay = `${c?.name || ""} ${c?.slug || ""}`.toLowerCase();
      return hay.trim() && !hay.includes("kozmetik");
    });
  }, [productCategories]);

  const [draftCatMain, setDraftCatMain] = useState("");
  const [draftCatSub, setDraftCatSub] = useState("");
  const [draftCatChild, setDraftCatChild] = useState("");
  const [trDraft, setTrDraft] = useState({ province: "", district: "", locality: "", neighborhood: "" });
  const [createPendingFiles, setCreatePendingFiles] = useState([]);
  const [publishConsentValues, setPublishConsentValues] = useState({});
  const publishConsentsAccepted = allRequiredChecked(
    SECOND_HAND_REQUIRED_CONSENTS,
    publishConsentValues
  );

  const handlePublishListing = async (listingId) => {
    if (!publishConsentsAccepted) {
      toast.error("İlan yayınlamak için zorunlu kuralları kabul etmelisiniz.");
      return;
    }

    try {
      await publishListing(listingId).unwrap();
      await recordLegalConsents({
        consents: SECOND_HAND_REQUIRED_CONSENTS.map((item) => ({ slug: item.slug, status: true })),
        context: "second_hand_publish",
      });
      toast.success("Admin onayına gönderildi.");
    } catch (err) {
      toast.error(err?.data?.message || "Yayına alınamadı.");
    }
  };

  const [editCatMain, setEditCatMain] = useState("");
  const [editCatSub, setEditCatSub] = useState("");
  const [editCatChild, setEditCatChild] = useState("");
  const [editTr, setEditTr] = useState({ province: "", district: "", locality: "", neighborhood: "" });

  const draftSubOptions = useMemo(() => {
    const c = secondHandCategories.find((x) => String(x.id) === draftCatMain);
    return c?.active_sub_categories || [];
  }, [secondHandCategories, draftCatMain]);

  const draftChildOptions = useMemo(() => {
    const c = secondHandCategories.find((x) => String(x.id) === draftCatMain);
    const s = c?.active_sub_categories?.find((x) => String(x.id) === draftCatSub);
    return s?.active_child_categories || [];
  }, [secondHandCategories, draftCatMain, draftCatSub]);

  const editSubOptions = useMemo(() => {
    const c = secondHandCategories.find((x) => String(x.id) === editCatMain);
    return c?.active_sub_categories || [];
  }, [secondHandCategories, editCatMain]);

  const editChildOptions = useMemo(() => {
    const c = secondHandCategories.find((x) => String(x.id) === editCatMain);
    const s = c?.active_sub_categories?.find((x) => String(x.id) === editCatSub);
    return s?.active_child_categories || [];
  }, [secondHandCategories, editCatMain, editCatSub]);

  const [draftForm, setDraftForm] = useState({
    title: "",
    description: "",
    price: "",
    condition: "used",
  });
  const [aiSuggesting, setAiSuggesting] = useState(false);
  const [editingId, setEditingId] = useState(null);
  const [editForm, setEditForm] = useState({});

  const conditionOptions = listingsData?.condition_options || {};

  const conversations = useMemo(() => inboxData?.conversations?.data || [], [inboxData]);
  const selectedConversation = useMemo(() => {
    if (!selectedConversationId) return null;
    return (conversations || []).find((c) => String(c?.id) === String(selectedConversationId)) || null;
  }, [conversations, selectedConversationId]);
  const counterpartyId = selectedConversation?.counterparty_id ? Number(selectedConversation.counterparty_id) : null;
  const listings = useMemo(() => listingsData?.listings?.data || [], [listingsData]);
  const lastPage = listingsData?.listings?.last_page || 1;

  const incomingConversations = useMemo(() => {
    // counterparty_role === 'buyer' => ben satıcıyım => gelen kutusu
    return (conversations || []).filter((c) => String(c?.counterparty_role || "") === "buyer");
  }, [conversations]);

  const outgoingConversations = useMemo(() => {
    // counterparty_role === 'seller' => ben alıcıyım => giden kutusu
    return (conversations || []).filter((c) => String(c?.counterparty_role || "") === "seller");
  }, [conversations]);

  const visibleConversations = useMemo(() => {
    return messageBox === "outgoing" ? outgoingConversations : incomingConversations;
  }, [messageBox, incomingConversations, outgoingConversations]);

  useEffect(() => {
    if (section !== "messages") return;
    // Default: önce geleni göster; gelen yoksa giden
    if (incomingConversations.length > 0) setMessageBox("incoming");
    else if (outgoingConversations.length > 0) setMessageBox("outgoing");
    // eslint-disable-next-line react-hooks/exhaustive-deps -- sadece liste değişince default seçimi güncellemek istiyoruz
  }, [section, incomingConversations.length, outgoingConversations.length]);

  useEffect(() => {
    if (typeof window === "undefined") return;
    if (section !== "messages") return;
    const conv = new URLSearchParams(window.location.search).get("c2c_conv");
    if (!conv) return;
    const id = Number(conv) || conv;
    const hit = (conversations || []).find((c) => String(c?.id) === String(id));
    if (!hit) return;
    // Konuşma linki geldiyse, doğru kutuyu da seçelim
    if (String(hit?.counterparty_role || "") === "seller") setMessageBox("outgoing");
    if (String(hit?.counterparty_role || "") === "buyer") setMessageBox("incoming");
  }, [section, conversations]);

  useEffect(() => {
    if (!selectedConversationId) return;
    if (String(lastMarkedReadId.current || "") === String(selectedConversationId)) return;
    lastMarkedReadId.current = selectedConversationId;
    markConversationRead(selectedConversationId).catch(() => {});
    // eslint-disable-next-line react-hooks/exhaustive-deps -- RTK mutation referansı her render'da değişebilir
  }, [selectedConversationId]);

  const handleVerificationSubmit = async (e) => {
    e.preventDefault();
    const form = e.target;
    if (!acceptTerms || !acceptPrivacy) {
      toast.error("Devam etmek için ikinci el sözleşmesi ve KVKK metnini kabul etmelisiniz.");
      return;
    }
    const fd = new FormData();
    fd.append("business_name", form.business_name.value.trim());
    fd.append("tax_number", form.tax_number.value.trim());
    if (form.barber_registry_number?.value != null) {
      fd.append("barber_registry_number", String(form.barber_registry_number.value).trim());
    }
    const doc = form.tax_document?.files?.[0];
    if (doc) fd.append("tax_document", doc);
    const barberDoc = form.barber_document?.files?.[0];
    if (barberDoc) fd.append("barber_document", barberDoc);
    fd.append("accept_terms", "1");
    fd.append("accept_privacy", "1");
    try {
      const res = await submitVerification(fd).unwrap();
      toast.success(res?.message || "Kaydedildi.");
      form.reset();
    } catch (err) {
      toast.error(err?.data?.message || "İşlem başarısız.");
    }
  };

  const onUploadImage = async (listingId, file, options = {}) => {
    const { suppressSuccessToast } = options;
    if (!file) return;
    const fd = new FormData();
    fd.append("image", file);
    try {
      await uploadImage({ id: listingId, formData: fd }).unwrap();
      if (!suppressSuccessToast) toast.success("Fotoğraf yüklendi.");
    } catch (err) {
      toast.error(err?.data?.message || "Yüklenemedi.");
      throw err;
    }
  };

  const handleCreateDraft = async (e) => {
    e.preventDefault();
    const body = {
      title: draftForm.title.trim(),
      description: draftForm.description.trim() || undefined,
      price: Number(draftForm.price),
      condition: draftForm.condition,
    };
    if (draftCatMain) body.category_id = Number(draftCatMain);
    if (draftCatSub) body.sub_category_id = Number(draftCatSub);
    if (draftCatChild) body.child_category_id = Number(draftCatChild);
    if (trDraft.province) body.province = trDraft.province;
    if (trDraft.district) body.district = trDraft.district.slice(0, 120);
    if (trDraft.locality) body.locality = trDraft.locality;
    if (trDraft.neighborhood) body.neighborhood = trDraft.neighborhood;
    try {
      const res = await createDraft(body).unwrap();
      const newId = res?.listing?.id;
      const files = createPendingFiles.slice(0, 6);
      toast.success("Taslak oluşturuldu.");
      if (newId && files.length > 0) {
        let uploaded = 0;
        for (const file of files) {
          try {
            await onUploadImage(newId, file, { suppressSuccessToast: true });
            uploaded += 1;
          } catch {
            break;
          }
        }
        if (uploaded > 0) {
          toast.success(
            uploaded === files.length
              ? `${uploaded} fotoğraf yüklendi.`
              : `${uploaded}/${files.length} fotoğraf yüklendi.`
          );
        }
      }
      setDraftForm({ title: "", description: "", price: "", condition: "used" });
      setDraftCatMain("");
      setDraftCatSub("");
      setDraftCatChild("");
      setTrDraft({ province: "", district: "", locality: "", neighborhood: "" });
      setCreatePendingFiles([]);
      /* İlanlarımda yeni taslağı göstermek: filtre/arama sıfırla, sayfa 1, sonra liste sekmesi */
      setListPage(1);
      setListStatus("");
      setListCondition("");
      setListSearchInput("");
      setListSearch("");
      setSection("listings");
      if (typeof window !== "undefined") {
        const url = `${window.location.pathname}${window.location.search}#second-hand-listings`;
        window.history.replaceState(null, "", url);
        /* replaceState hash’i değiştirir ama hashchange atmaz; üst profil sekmesi senkron kalsın */
        window.dispatchEvent(new HashChangeEvent("hashchange"));
        window.setTimeout(() => {
          refetchMyListings();
        }, 0);
      }
    } catch (err) {
      toast.error(err?.data?.message || "Oluşturulamadı.");
    }
  };

  const normalizeName = (s) =>
    String(s || "")
      .trim()
      .toLowerCase()
      .replaceAll("ı", "i")
      .replaceAll("ğ", "g")
      .replaceAll("ü", "u")
      .replaceAll("ş", "s")
      .replaceAll("ö", "o")
      .replaceAll("ç", "c");

  const parseJsonFromText = (text) => {
    const t = String(text || "").trim();
    try {
      const direct = JSON.parse(t);
      if (direct && typeof direct === "object") return direct;
    } catch {
      // ignore
    }
    const m = t.match(/```(?:json)?\s*\n?([\s\S]*?)\n?```/i);
    if (m?.[1]) {
      try {
        const decoded = JSON.parse(String(m[1]).trim());
        if (decoded && typeof decoded === "object") return decoded;
      } catch {
        // ignore
      }
    }
    const first = t.indexOf("{");
    const last = t.lastIndexOf("}");
    if (first !== -1 && last !== -1 && last > first) {
      try {
        const decoded = JSON.parse(t.slice(first, last + 1));
        if (decoded && typeof decoded === "object") return decoded;
      } catch {
        // ignore
      }
    }
    return null;
  };

  /** Kısa iğnelemeleri (ör. "el") yanlış eşlemesin diye minimum uzunluklu includes */
  const looseNameMatch = (a, b) => {
    const na = normalizeName(a);
    const nb = normalizeName(b);
    if (!na || !nb) return false;
    if (na === nb) return true;
    const short = na.length <= nb.length ? na : nb;
    const long = na.length > nb.length ? na : nb;
    if (short.length < 4) return false;
    return long.includes(short);
  };

  const tokenizeUserHints = (title, desc) => {
    const raw = normalizeName(`${title} ${desc}`);
    const stop = new Set([
      "ilan",
      "satis",
      "satilik",
      "ikinci",
      "urun",
      "fiyat",
      "tl",
      "ve",
      "ile",
      "icin",
      "the",
      "veya",
    ]);
    return raw
      .split(/[^a-z0-9]+/i)
      .map((t) => t.trim())
      .filter((t) => t.length >= 3 && !stop.has(t));
  };

  const scorePathAgainstUserText = (pathBlobNorm, tokens, userBlobNorm) => {
    if (!pathBlobNorm || !tokens.length) return 0;
    let score = 0;
    for (const t of tokens) {
      if (pathBlobNorm.includes(t)) score += t.length >= 5 ? 4 : 2;
      else {
        const words = pathBlobNorm.split(/[^a-z0-9]+/).filter((w) => w.length >= 4);
        for (const w of words) {
          if (w.startsWith(t.slice(0, 4)) || t.startsWith(w.slice(0, 4))) {
            score += 2;
            break;
          }
        }
      }
    }
    const userSaysBekleme =
      userBlobNorm.includes("bekleme") ||
      userBlobNorm.includes("bekleyis") ||
      userBlobNorm.includes("sira");
    const userSalonGear =
      userBlobNorm.includes("berber") ||
      userBlobNorm.includes("kuafor") ||
      userBlobNorm.includes("koltuk") ||
      userBlobNorm.includes("hidrolik") ||
      userBlobNorm.includes("yikama") ||
      userBlobNorm.includes("unitesi") ||
      userBlobNorm.includes("tras") ||
      userBlobNorm.includes("elektrikli");
    if (pathBlobNorm.includes("bekleme") && userSalonGear && !userSaysBekleme) score -= 12;
    return score;
  };

  const findBestCategoryPathFromUserText = (title, desc) => {
    const tokens = tokenizeUserHints(title, desc);
    const userBlobNorm = normalizeName(`${title} ${desc}`);
    if (!tokens.length) return { mainId: "", subId: "", childId: "", score: 0 };
    let best = { mainId: "", subId: "", childId: "", score: -999 };
    for (const c of secondHandCategories || []) {
      const subs = c.active_sub_categories || [];
      for (const s of subs) {
        const children = s.active_child_categories || [];
        const subBlob = normalizeName(`${c.name} ${s.name}`);
        const subScore = scorePathAgainstUserText(subBlob, tokens, userBlobNorm);
        if (subScore > best.score) {
          best = { mainId: String(c.id), subId: String(s.id), childId: "", score: subScore };
        }
        for (const ch of children || []) {
          const fullBlob = normalizeName(`${c.name} ${s.name} ${ch.name}`);
          const sc = scorePathAgainstUserText(fullBlob, tokens, userBlobNorm);
          if (sc > best.score) {
            best = { mainId: String(c.id), subId: String(s.id), childId: String(ch.id), score: sc };
          }
        }
      }
    }
    if (best.score <= 0 || !best.mainId) return { mainId: "", subId: "", childId: "", score: 0 };
    return best;
  };

  /**
   * AI'dan gelen isimleri katalogla eşler; birden fazla aday varsa ilan metnine göre skorlar.
   * "Berber koltuğu" gibi metinlerde "Bekleme" yanlış pozitiflerini azaltır.
   */
  const resolveSecondHandCategoryFromAi = ({ main, sub, child, title, desc }) => {
    const nm = normalizeName(main);
    const ns = normalizeName(sub);
    const nc = normalizeName(child);
    const tokens = tokenizeUserHints(title, desc);
    const userBlobNorm = normalizeName(`${title} ${desc}`);
    const mains = secondHandCategories || [];
    const kw = findBestCategoryPathFromUserText(title, desc);

    if (!nm && !ns && !nc) {
      return {
        mainId: kw.mainId || "",
        subId: kw.subId || "",
        childId: kw.childId || "",
        pickedByKeywords: !!kw.mainId,
      };
    }

    const mainMatches = () => {
      if (!nm) return mains;
      const exact = mains.filter((c) => normalizeName(c.name) === nm);
      if (exact.length) return exact;
      return mains.filter((c) => looseNameMatch(c.name, main));
    };

    const candidates = [];
    const pushPath = (c, s, ch) => {
      if (candidates.length >= 200) return;
      candidates.push({ c, s, ch });
    };

    for (const c of mainMatches()) {
      const subs = c.active_sub_categories || [];
      if (!ns) {
        for (const s of subs) {
          const children = s.active_child_categories || [];
          if (!nc) {
            pushPath(c, s, null);
            continue;
          }
          for (const ch of children || []) {
            if (looseNameMatch(ch.name, child) || normalizeName(ch.name) === nc) pushPath(c, s, ch);
          }
        }
        continue;
      }
      for (const s of subs) {
        if (!(normalizeName(s.name) === ns || looseNameMatch(s.name, sub))) continue;
        const children = s.active_child_categories || [];
        if (!nc || !children?.length) {
          pushPath(c, s, null);
          continue;
        }
        let anyChild = false;
        for (const ch of children) {
          if (normalizeName(ch.name) === nc || looseNameMatch(ch.name, child)) {
            pushPath(c, s, ch);
            anyChild = true;
          }
        }
        if (!anyChild) pushPath(c, s, null);
      }
    }

    const pickBestFromCandidates = () => {
      if (!candidates.length) return { mainId: "", subId: "", childId: "", score: -999 };
      let best = { mainId: "", subId: "", childId: "", score: -999 };
      for (const { c, s, ch } of candidates) {
        const blob = normalizeName([c?.name, s?.name, ch?.name].filter(Boolean).join(" "));
        const sc = scorePathAgainstUserText(blob, tokens, userBlobNorm);
        const ids = {
          mainId: String(c.id),
          subId: s?.id != null ? String(s.id) : "",
          childId: ch?.id != null ? String(ch.id) : "",
          score: sc,
        };
        if (sc > best.score) best = ids;
      }
      return best;
    };

    const fromAi = pickBestFromCandidates();
    let picked = fromAi;
    let pickedByKeywords = false;

    if (kw.score >= 6 && kw.score >= fromAi.score + 2) {
      picked = { mainId: kw.mainId, subId: kw.subId, childId: kw.childId || "", score: kw.score };
      pickedByKeywords = true;
    } else if (picked.mainId && !picked.subId && kw.subId && kw.score >= 4) {
      if (!nm || kw.mainId === picked.mainId) {
        picked = { mainId: kw.mainId, subId: kw.subId, childId: kw.childId || "", score: kw.score };
        pickedByKeywords = true;
      }
    } else if (!picked.mainId && kw.mainId) {
      picked = { mainId: kw.mainId, subId: kw.subId, childId: kw.childId || "", score: kw.score };
      pickedByKeywords = true;
    }

    return {
      mainId: picked.mainId || "",
      subId: picked.subId || "",
      childId: picked.childId || "",
      pickedByKeywords,
    };
  };

  const aiSuggestSecondHand = async (mode = "all") => {
    if (aiSuggesting) return;
    try {
      setAiSuggesting(true);
      const desc = String(draftForm.description || "").trim();
      const title = String(draftForm.title || "").trim();
      const price = String(draftForm.price || "").trim();

      const catsText = (secondHandCategories || [])
        .slice(0, 55)
        .map((c) => {
          const subs = (c.active_sub_categories || []).slice(0, 18).map((s) => s.name).join(", ");
          return `- ${c.name}${subs ? ` (alt: ${subs})` : ""}`;
        })
        .join("\n");

      const prompt = `
Sen Türkiye'de kuaför/berber sektörüne yönelik ikinci el ilan asistanısın.
Kullanıcının ilan metnine göre SEO uyumlu bir başlık ve uygun kategori öner.
SADECE geçerli JSON döndür, açıklama veya markdown yazma.

Kategori kuralları (çok önemli):
- category_main, category_sub ve category_child alanlarında YALNIZCA aşağıdaki listede geçen isimleri AYNEN (aynı yazım/büyük-küçük harf duyarlı değil ama kelimeler aynı) kopyala. Listede olmayan kategori uydurma.
- "Berber koltuğu", "hidrolik koltuk", "yıkama ünitesi", "tras koltuğu" vb. çalışma/berber ekipmanı = bekleme salonu / sıra bekleme mobilyası DEĞİLDİR. Metinde açıkça bekleme alanı, sıra, lounge geçmiyorsa "Bekleme" alt kategorisini seçme.
- Ürün neyse ona göre seç: koltuk/ünite/ayna/ısıtıcı/alet ayrı ayrı değerlendirilir.

İlan bilgisi:
- Mevcut başlık: ${title || "(boş)"}
- Açıklama: ${desc || "(boş)"}
- Fiyat: ${price || "(boş)"}

Kategori listesi (yalnızca buradan seç):
${catsText}

JSON şeması:
{
  "title": "önerilen ilan başlığı",
  "category_main": "ana kategori adı",
  "category_sub": "alt kategori adı (yoksa boş string)",
  "category_child": "alt-alt kategori adı (yoksa boş string)",
  "condition": "new|lightly_used|used|defective — new=sıfır, lightly_used=sıfır ayarında, used=iyi durumda, defective=yıpranmış veya onarım gerekebilir (emin değilsen used)"
}
`.trim();

      const res = await fetch(`${appConfig.BASE_URL}api/user/ai/generate-content`, {
        method: "POST",
        headers: secondHandAiFetchHeaders(),
        body: JSON.stringify({ prompt }),
      });
      const j = await res.json().catch(() => ({}));
      const parsed = parseJsonFromText(j?.generated_content || "");
      if (!parsed) {
        toast.error(
          j?.message ||
            (res.status === 401
              ? "Oturum süresi doldu; lütfen tekrar giriş yapın."
              : res.status === 503
                ? "AI şu an kapalı (admin ayarları)."
                : "AI önerisi alınamadı.")
        );
        return;
      }

      if (mode === "all" || mode === "title") {
        if (parsed.title && String(parsed.title).trim()) {
          setDraftForm((p) => ({ ...p, title: String(parsed.title).trim().slice(0, 140) }));
        }
        const cond = String(parsed.condition || "").trim();
        if (cond && conditionOptions?.[cond]) {
          setDraftForm((p) => ({ ...p, condition: cond }));
        }
      }

      if (mode === "all" || mode === "category") {
        const path = resolveSecondHandCategoryFromAi({
          main: parsed.category_main,
          sub: parsed.category_sub,
          child: parsed.category_child,
          title,
          desc,
        });
        if (path.mainId) {
          setDraftCatMain(path.mainId);
          setDraftCatSub(path.subId || "");
          setDraftCatChild(path.childId || "");
          if (path.pickedByKeywords) {
            toast.info("Kategori, ilan metnine göre düzeltildi.");
          }
        }
      }

      toast.success("AI önerisi uygulandı.");
    } catch {
      toast.error("AI önerisi alınamadı.");
    } finally {
      setAiSuggesting(false);
    }
  };

  const aiEnhanceSecondHandDescription = async () => {
    if (aiSuggesting) return;
    const desc = String(draftForm.description || "").trim();
    if (!desc) {
      toast.warn("Önce açıklama yazın.");
      return;
    }
    try {
      setAiSuggesting(true);
      const title = String(draftForm.title || "").trim();
      const prompt = `
Sen Türkiye'de kuaför/berber sektörüne yönelik ikinci el ilan asistanısın.
Kullanıcının ilan açıklamasını daha anlaşılır, güven veren ve satış odaklı hale getir.
Yalan/abartı ekleme, sadece yazılan bilginin dilini iyileştir ve düzenle.
SADECE geçerli JSON döndür.

Mevcut başlık: ${title || "(boş)"}
Mevcut açıklama:
${desc}

JSON şeması:
{ "description": "iyileştirilmiş açıklama" }
`.trim();

      const res = await fetch(`${appConfig.BASE_URL}api/user/ai/generate-content`, {
        method: "POST",
        headers: secondHandAiFetchHeaders(),
        body: JSON.stringify({ prompt }),
      });
      const j = await res.json().catch(() => ({}));
      const parsed = parseJsonFromText(j?.generated_content || "");
      const nextDesc = parsed?.description ? String(parsed.description).trim() : "";
      if (!nextDesc) {
        toast.error(j?.message || "AI açıklama üretemedi.");
        return;
      }
      setDraftForm((p) => ({ ...p, description: nextDesc.slice(0, 4000) }));
      toast.success("Açıklama güncellendi.");
    } catch {
      toast.error("AI açıklama üretemedi.");
    } finally {
      setAiSuggesting(false);
    }
  };

  const saveEdit = async (listingId) => {
    const body = {
      title: editForm.title,
      description: editForm.description,
      price: Number(editForm.price),
      condition: editForm.condition,
    };
    body.province = editTr.province?.trim() || null;
    body.district = editTr.district?.trim() ? editTr.district.trim().slice(0, 120) : null;
    body.locality = editTr.locality?.trim() || null;
    body.neighborhood = editTr.neighborhood?.trim() || null;
    if (editCatMain) body.category_id = Number(editCatMain);
    else body.category_id = null;
    if (editCatSub) body.sub_category_id = Number(editCatSub);
    else body.sub_category_id = null;
    if (editCatChild) body.child_category_id = Number(editCatChild);
    else body.child_category_id = null;
    try {
      await updateDraft({
        id: listingId,
        body,
      }).unwrap();
      toast.success("Güncellendi.");
      setEditingId(null);
    } catch (err) {
      toast.error(err?.data?.message || "Güncellenemedi.");
    }
  };

  const startEdit = (item) => {
    setEditingId(item.id);
    setEditForm({
      title: item.title,
      description: item.description || "",
      price: String(item.price),
      condition: item.condition,
    });
    const chain = resolveCategorySelection(
      productCategories,
      item.category_id,
      item.sub_category_id,
      item.child_category_id
    );
    setEditCatMain(chain.main);
    setEditCatSub(chain.sub);
    setEditCatChild(chain.child);
    setEditTr({
      province: item.province || "",
      district: item.district || "",
      locality: item.locality || "",
      neighborhood: item.neighborhood || "",
    });
  };

  const onDeleteImage = async (listingId, imageId) => {
    try {
      await deleteImage({ listingId, imageId }).unwrap();
      toast.success("Silindi.");
    } catch (err) {
      toast.error(err?.data?.message || "Silinemedi.");
    }
  };

  /** İlan oluşturma kuyruğuna uygun türdeki dosyaları ekler (en fazla 6). */
  const appendCreatePendingFiles = (fileList) => {
    const picked = [];
    for (const f of Array.from(fileList || [])) {
      if (!f) continue;
      if (["image/jpeg", "image/png", "image/webp"].includes(f.type)) picked.push(f);
      else toast.warn("Yalnızca JPEG, PNG veya WebP eklenebilir.");
    }
    if (!picked.length) return;
    setCreatePendingFiles((prev) => {
      const next = [...prev];
      for (const f of picked) {
        if (next.length >= 6) break;
        next.push(f);
      }
      return next;
    });
  };

  const messages = useMemo(() => {
    const raw = threadData?.messages?.data || [];
    return [...raw].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
  }, [threadData]);

  const mediaItems = useMemo(() => {
    const out = [];
    for (const m of messages || []) {
      const atts = Array.isArray(m?.attachments) ? m.attachments : [];
      for (const a of atts) {
        const href = a.url ? a.url : (a.path ? `${appConfig.BASE_URL}storage/${a.path}` : null);
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
    return out.reverse();
  }, [messages]);

  const sendReplyHandler = async () => {
    if (!selectedConversationId) return;
    const text = replyBody.trim();
    if (!text && replyFiles.length === 0) return;
    try {
      const filesToSend = replyFiles;
      setReplyFiles([]);
      setReplyBody("");

      if (filesToSend.length > 0) {
        const fd = new FormData();
        if (text) fd.append("body", text);
        for (const f of filesToSend) fd.append("attachments[]", f);
        await sendReply({ conversationId: selectedConversationId, body: fd }).unwrap();
      } else {
        await sendReply({ conversationId: selectedConversationId, body: { body: text } }).unwrap();
      }
      setReplyBody("");
      toast.success("Gönderildi.");
    } catch (err) {
      toast.error(err?.data?.message || "Gönderilemedi.");
    }
  };

  const sendQuickText = async (text) => {
    if (!selectedConversationId) return;
    const t = String(text || "").trim();
    if (!t) return;
    try {
      setReplyBody("");
      await sendReply({ conversationId: selectedConversationId, body: { body: t } }).unwrap();
    } catch (err) {
      toast.error(err?.data?.message || "Mesaj gönderilemedi.");
    }
  };

  const sendOffer = async () => {
    const raw = String(offerAmount || "").replace(/[^\d.,]/g, "").replace(",", ".");
    const n = Number(raw);
    if (!Number.isFinite(n) || n <= 0) {
      toast.error("Teklif tutarı geçersiz.");
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
      setActionsOpen(false);
      toast.success("Kullanıcı engellendi.");
    } catch (err) {
      setActionError(err?.data?.message || "Engelleme başarısız.");
    }
  };

  const doUnblock = async () => {
    if (!counterpartyId) return;
    try {
      setActionError("");
      await unblockUser(counterpartyId).unwrap();
      setActionsOpen(false);
      toast.success("Engel kaldırıldı.");
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
      setActionsOpen(false);
      toast.success("Şikayetiniz alındı. Teşekkürler.");
    } catch (err) {
      const msg = err?.data?.message || "Şikayet gönderilemedi.";
      setActionError(msg);
    }
  };

  const syncSecondHandHash = (id) => {
    const map = {
      verification: "second-hand-verification",
      listings: "second-hand-listings",
      create: "second-hand-add",
      messages: "second-hand-messages",
    };
    const h = map[id];
    if (h && typeof window !== "undefined") {
      window.history.replaceState(null, "", `/profile#${h}`);
    }
  };

  return (
    <div className="w-full max-w-4xl">
      <h3 className="text-lg font-bold text-qblack mb-1">İkinci El Sat</h3>
      <p className="text-sm text-qgray mb-4">İlanlarınızı yönetin veya yeni ilan oluşturun.</p>

      <div className="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-3">
        {[
          { id: "verification", label: "Doğrulama" },
          { id: "listings", label: "İlanlarım", disabled: !isApproved },
          { id: "create", label: "İlan ekle", disabled: !isApproved },
          { id: "messages", label: "Mesajlar" },
        ].map((t) => (
          <button
            key={t.id}
            type="button"
            disabled={t.disabled}
            onClick={() => {
              setSection(t.id);
              syncSecondHandHash(t.id);
            }}
            className={`px-4 py-2 rounded-md text-sm font-600 ${
              section === t.id ? "bg-qyellow text-qblack" : "bg-gray-100 text-qgray"
            } ${t.disabled ? "opacity-40 cursor-not-allowed" : ""}`}
          >
            <span className="inline-flex items-center gap-2">
              {t.label}
              {t.id === "verification" && isApproved && (
                <SecondHandApprovedTick className="w-5 h-5 shrink-0" title="İkinci el doğrulandı" />
              )}
            </span>
          </button>
        ))}
      </div>

      {section === "verification" && (
        <div>
          {verIsLoading ? (
            <div
              className="max-w-md space-y-3 rounded-lg border border-gray-100 bg-gray-50/60 p-5 animate-pulse"
              aria-busy="true"
              aria-label="Doğrulama bilgileri yükleniyor"
            >
              <div className="h-3 w-28 rounded bg-gray-200" />
              <div className="h-10 w-full rounded-md bg-gray-100" />
              <div className="h-10 w-full rounded-md bg-gray-100" />
              <div className="h-10 w-3/4 max-w-[200px] rounded-md bg-gray-100" />
              <div className="h-9 w-32 rounded-md bg-gray-200" />
            </div>
          ) : (
            <>
              {verIsFetching && verData != null && (
                <p className="mb-3 text-[11px] text-qgray" aria-live="polite">
                  Bilgiler güncelleniyor…
                </p>
              )}
              {verification && (
                <div className="mb-4 p-4 bg-gray-50 rounded-lg text-sm">
                  <div className="flex flex-wrap items-center gap-2">
                    <span className="text-qgray">Durum: </span>
                    <strong className="inline-flex items-center gap-2">
                      {VERIFICATION_LABEL[verification.status] || verification.status}
                      {verification.status === "approved" && (
                        <SecondHandApprovedTick className="w-5 h-5" title="İkinci el doğrulandı" />
                      )}
                    </strong>
                  </div>
                  {verification.business_name && (
                    <div className="mt-1">
                      {verification.business_name} · {verification.tax_number}
                    </div>
                  )}
                  {verification.status === "rejected" && verification.admin_note && (
                    <div className="mt-2 text-red-700">Not: {verification.admin_note}</div>
                  )}
                </div>
              )}

              {verification?.status === "approved" ? (
                <div className="flex items-start gap-3 rounded-lg border border-green-200 bg-green-50/80 p-4 text-sm text-green-800">
                  <SecondHandApprovedTick className="w-9 h-9 shrink-0 mt-0.5" title="İkinci el doğrulandı" />
                  <p className="leading-relaxed m-0 pt-0.5">
                    İkinci el hesabınız doğrulanmış. İlan ve mesajlar sekmesini kullanabilirsiniz.
                  </p>
                </div>
              ) : verification?.status === "pending" ? (
                <p className="text-qgray text-sm">Başvurunuz inceleniyor. Sonuç e-posta veya bu sayfa üzerinden güncellenecektir.</p>
              ) : (
                <form onSubmit={handleVerificationSubmit} className="space-y-4 max-w-md">
                  <div>
                    <label className="block text-xs text-qgray mb-1">İş yeri adı</label>
                    <input
                      name="business_name"
                      required
                      className="w-full h-10 px-3 border border-gray-200 rounded-md text-sm"
                      defaultValue={verification?.business_name || ""}
                    />
                  </div>
                  <div>
                    <label className="block text-xs text-qgray mb-1">Vergi numarası</label>
                    <input
                      name="tax_number"
                      required
                      className="w-full h-10 px-3 border border-gray-200 rounded-md text-sm"
                      defaultValue={verification?.tax_number || ""}
                    />
                  </div>
                  <div>
                    <label className="block text-xs text-qgray mb-1">Vergi belgesi</label>
                    <input name="tax_document" type="file" accept=".jpg,.jpeg,.png,.pdf" className="text-sm" required />
                    <p className="text-[11px] text-qgray mt-1">JPG/PNG/PDF (max 5MB)</p>
                  </div>
                  <div>
                    <label className="block text-xs text-qgray mb-1">Berberler Odası sicil numarası (veya evrak)</label>
                    <input
                      name="barber_registry_number"
                      className="w-full h-10 px-3 border border-gray-200 rounded-md text-sm"
                      defaultValue={verification?.barber_registry_number || ""}
                      placeholder="Örn: 12345"
                    />
                  </div>
                  <div>
                    <label className="block text-xs text-qgray mb-1">Berberler Odası evrağı (isteğe bağlı)</label>
                    <input name="barber_document" type="file" accept=".jpg,.jpeg,.png,.pdf" className="text-sm" />
                    <p className="text-[11px] text-qgray mt-1">Sicil numarası yoksa evrak yükleyin.</p>
                  </div>
                  <div className="rounded-lg border border-gray-200 bg-gray-50/60 p-3">
                    <label className="flex items-start gap-2 text-xs text-qblack">
                      <input
                        name="accept_terms"
                        type="checkbox"
                        className="mt-0.5"
                        checked={acceptTerms}
                        onChange={(e) => setAcceptTerms(e.target.checked)}
                      />
                      <span className="leading-relaxed">
                        <button
                          type="button"
                          onClick={() => setConsentModal("terms")}
                          className="underline underline-offset-2"
                        >
                          İkinci El Kullanım Koşulları / Sözleşmesi
                        </button>{" "}
                        metnini okudum, kabul ediyorum.
                      </span>
                    </label>
                    <label className="mt-2 flex items-start gap-2 text-xs text-qblack">
                      <input
                        name="accept_privacy"
                        type="checkbox"
                        className="mt-0.5"
                        checked={acceptPrivacy}
                        onChange={(e) => setAcceptPrivacy(e.target.checked)}
                      />
                      <span className="leading-relaxed">
                        <button
                          type="button"
                          onClick={() => setConsentModal("privacy")}
                          className="underline underline-offset-2"
                        >
                          KVKK / Gizlilik Politikası
                        </button>{" "}
                        metnini okudum, kabul ediyorum.
                      </span>
                    </label>
                  </div>
                  <button
                    type="submit"
                    disabled={verSubmitting}
                    className="h-10 px-6 bg-qblack text-white rounded-md text-sm font-600 disabled:opacity-50"
                  >
                    {verSubmitting ? "Gönderiliyor…" : "Başvuruyu gönder"}
                  </button>
                </form>
              )}
            </>
          )}
        </div>
      )}

      {section === "listings" && isApproved && (
        <div>
          <div className="mb-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
            <p className="text-xs font-600 text-qgray uppercase tracking-wide mb-1">İlanlarımda filtrele</p>
            <p className="text-xs text-qgray mb-3">
              Satıldı işaretlediğiniz ilanlar yalnızca sizde görünür; diğer kullanıcılar göremez.
            </p>
            <div className="flex flex-col sm:flex-row flex-wrap gap-3 sm:items-end">
              <div className="min-w-[140px]">
                <label className="block text-xs text-qgray mb-1">Yayın durumu</label>
                <select
                  value={listStatus}
                  onChange={(e) => {
                    setListStatus(e.target.value);
                    setListPage(1);
                  }}
                  className="w-full h-10 px-3 border border-gray-200 rounded-md text-sm bg-white"
                >
                  <option value="">Tümü</option>
                  <option value="draft">Taslak</option>
                  <option value="active">Yayında</option>
                  <option value="inactive">Pasif</option>
                  <option value="sold">Satıldı</option>
                </select>
              </div>
              <div className="min-w-[160px]">
                <label className="block text-xs text-qgray mb-1">Ürün durumu</label>
                <select
                  value={listCondition}
                  onChange={(e) => {
                    setListCondition(e.target.value);
                    setListPage(1);
                  }}
                  className="w-full h-10 px-3 border border-gray-200 rounded-md text-sm bg-white"
                >
                  <option value="">Tümü</option>
                  {Object.entries(conditionOptions).map(([k, v]) => (
                    <option key={k} value={k}>
                      {v}
                    </option>
                  ))}
                </select>
              </div>
              <div className="flex-1 min-w-[200px]">
                <label className="block text-xs text-qgray mb-1">Başlıkta ara</label>
                <input
                  type="search"
                  value={listSearchInput}
                  onChange={(e) => setListSearchInput(e.target.value)}
                  placeholder="İlan başlığı…"
                  className="w-full h-10 px-3 border border-gray-200 rounded-md text-sm"
                />
                <p className="text-[11px] text-qgray mt-1">Yazmayı bitirdikten kısa süre sonra arama uygulanır.</p>
              </div>
            </div>
          </div>

          <LegalConsentCheckboxes
            items={SECOND_HAND_REQUIRED_CONSENTS}
            values={publishConsentValues}
            onChange={(key, value) =>
              setPublishConsentValues((prev) => ({ ...prev, [key]: value }))
            }
            required
            className="mb-4 p-4 border border-gray-200 rounded-lg bg-white"
          />

          {listingsLoading ? (
            <div className="animate-spin rounded-full h-10 w-10 border-t-2 border-b-2 border-gray-900" />
          ) : listings.length === 0 ? (
            <p className="text-sm text-qgray py-8 text-center border border-dashed border-gray-200 rounded-lg">
              Bu filtrelere uygun ilan bulunamadı.
            </p>
          ) : (
            <ul className="space-y-4">
              {listings.map((item) => (
                <li key={item.id} className="border border-gray-200 rounded-lg p-4">
                  <div className="flex flex-wrap justify-between gap-2">
                    <div>
                      <div className="font-600 text-qblack">{item.title}</div>
                      <div className="text-sm text-qgray">
                        {formatTry(item.price)} · {STATUS_LABEL[item.status] || item.status}
                        {item.condition && (
                          <>
                            {" · "}
                            {conditionOptions[item.condition] || item.condition}
                          </>
                        )}
                      </div>
                      {item.status === "active" && (
                        <Link href={secondHandListingUrl(getSecondHandListingSeoPath(item))} className="text-sm text-blue-600 underline mt-1 inline-block">
                          Sayfada görüntüle
                        </Link>
                      )}
                    </div>
                    <div className="flex flex-wrap gap-2">
                      {item.status === "draft" && (
                        <>
                          <button
                            type="button"
                            onClick={() => startEdit(item)}
                            className="text-xs px-3 py-1 border rounded-md"
                          >
                            Düzenle
                          </button>
                          <button
                            type="button"
                            onClick={() => handlePublishListing(item.id)}
                            disabled={!publishConsentsAccepted}
                            className="text-xs px-3 py-1 bg-qyellow rounded-md disabled:opacity-50"
                          >
                            Onaya gönder
                          </button>
                        </>
                      )}
                      {item.status === "active" && (
                        <>
                          <button
                            type="button"
                            onClick={async () => {
                              try {
                                await deactivateListing({ id: item.id }).unwrap();
                                toast.success("Pasife alındı.");
                              } catch (err) {
                                toast.error(err?.data?.message || "İşlem başarısız.");
                              }
                            }}
                            className="text-xs px-3 py-1 border rounded-md"
                          >
                            Pasifle
                          </button>
                          <button
                            type="button"
                            onClick={async () => {
                              try {
                                await markSold(item.id).unwrap();
                                toast.success("Satıldı işaretlendi.");
                              } catch (err) {
                                toast.error(err?.data?.message || "İşlem başarısız.");
                              }
                            }}
                            className="text-xs px-3 py-1 border rounded-md"
                          >
                            Satıldı
                          </button>
                        </>
                      )}
                      {item.status === "inactive" && (
                        <>
                          <button
                            type="button"
                            onClick={async () => {
                              try {
                                await activateListing(item.id).unwrap();
                                toast.success("Admin onayına gönderildi.");
                              } catch (err) {
                                toast.error(err?.data?.message || "İşlem başarısız.");
                              }
                            }}
                            className="text-xs px-3 py-1 bg-qyellow rounded-md"
                          >
                            Onaya gönder
                          </button>
                          <button
                            type="button"
                            onClick={async () => {
                              try {
                                await markSold(item.id).unwrap();
                                toast.success("Satıldı işaretlendi.");
                              } catch (err) {
                                toast.error(err?.data?.message || "İşlem başarısız.");
                              }
                            }}
                            className="text-xs px-3 py-1 border rounded-md"
                          >
                            Satıldı
                          </button>
                        </>
                      )}
                    </div>
                  </div>

                  {editingId === item.id && item.status === "draft" && (
                    <div className="mt-4 pt-4 border-t border-gray-100 space-y-2">
                      <input
                        className="w-full h-9 px-2 border rounded text-sm"
                        value={editForm.title}
                        onChange={(e) => setEditForm((p) => ({ ...p, title: e.target.value }))}
                      />
                      <textarea
                        className="w-full min-h-[60px] px-2 py-1 border rounded text-sm"
                        value={editForm.description}
                        onChange={(e) => setEditForm((p) => ({ ...p, description: e.target.value }))}
                      />
                      <input
                        type="number"
                        className="w-full h-9 px-2 border rounded text-sm"
                        value={editForm.price}
                        onChange={(e) => setEditForm((p) => ({ ...p, price: e.target.value }))}
                      />
                      <select
                        className="w-full h-9 px-2 border rounded text-sm"
                        value={editForm.condition}
                        onChange={(e) => setEditForm((p) => ({ ...p, condition: e.target.value }))}
                      >
                        {Object.entries(conditionOptions).map(([k, v]) => (
                          <option key={k} value={k}>
                            {v}
                          </option>
                        ))}
                      </select>
                      <select
                        className="w-full h-9 px-2 border rounded text-sm"
                        value={editCatMain}
                        onChange={(e) => {
                          setEditCatMain(e.target.value);
                          setEditCatSub("");
                          setEditCatChild("");
                        }}
                      >
                        <option value="">Ana kategori (isteğe bağlı)</option>
                        {secondHandCategories.map((c) => (
                          <option key={c.id} value={String(c.id)}>
                            {c.name}
                          </option>
                        ))}
                      </select>
                      {editSubOptions.length > 0 && (
                        <select
                          className="w-full h-9 px-2 border rounded text-sm"
                          value={editCatSub}
                          onChange={(e) => {
                            setEditCatSub(e.target.value);
                            setEditCatChild("");
                          }}
                        >
                          <option value="">Alt kategori</option>
                          {editSubOptions.map((s) => (
                            <option key={s.id} value={String(s.id)}>
                              {s.name}
                            </option>
                          ))}
                        </select>
                      )}
                      {editChildOptions.length > 0 && (
                        <select
                          className="w-full h-9 px-2 border rounded text-sm"
                          value={editCatChild}
                          onChange={(e) => setEditCatChild(e.target.value)}
                        >
                          <option value="">Alt-alt kategori</option>
                          {editChildOptions.map((ch) => (
                            <option key={ch.id} value={String(ch.id)}>
                              {ch.name}
                            </option>
                          ))}
                        </select>
                      )}
                      <p className="text-xs font-600 text-qgray uppercase tracking-wide">Adres (isteğe bağlı)</p>
                      <Suspense fallback={<TurkeyAddressFallback />}>
                        <TurkeyAddressSelects value={editTr} onChange={setEditTr} />
                      </Suspense>
                      <div className="flex gap-2">
                        <button
                          type="button"
                          onClick={() => saveEdit(item.id)}
                          className="text-xs px-3 py-1 bg-qblack text-white rounded-md"
                        >
                          Kaydet
                        </button>
                        <button type="button" onClick={() => setEditingId(null)} className="text-xs px-3 py-1 border rounded-md">
                          İptal
                        </button>
                      </div>
                    </div>
                  )}

                  {item.status === "draft" && (
                    <div className="mt-3">
                      <span className="mb-2 block text-xs font-600 text-qgray">Fotoğraf (taslak, en fazla 6)</span>
                      <div className="flex flex-wrap gap-2">
                        <label className="inline-flex cursor-pointer items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-600 text-qblack transition hover:bg-gray-50">
                          Galeri
                          <input
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            className="sr-only"
                            onChange={(e) => {
                              const f = e.target.files?.[0];
                              e.target.value = "";
                              if (!f) return;
                              void onUploadImage(item.id, f);
                            }}
                          />
                        </label>
                        <label className="inline-flex cursor-pointer items-center rounded-lg border-2 border-amber-300 bg-amber-50 px-3 py-2 text-xs font-700 text-qblack transition hover:bg-amber-100">
                          Kamera (arka)
                          <input
                            type="file"
                            accept="image/*"
                            capture="environment"
                            className="sr-only"
                            onChange={(e) => {
                              const f = e.target.files?.[0];
                              e.target.value = "";
                              if (!f) return;
                              if (!["image/jpeg", "image/png", "image/webp"].includes(f.type)) {
                                toast.warn(
                                  "Bu görüntü formatı desteklenmiyor. JPEG veya PNG çekin ya da galeriden seçin."
                                );
                                return;
                              }
                              void onUploadImage(item.id, f);
                            }}
                          />
                        </label>
                        <label className="inline-flex cursor-pointer items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-600 text-qblack transition hover:bg-gray-50">
                          Ön kamera
                          <input
                            type="file"
                            accept="image/*"
                            capture="user"
                            className="sr-only"
                            onChange={(e) => {
                              const f = e.target.files?.[0];
                              e.target.value = "";
                              if (!f) return;
                              if (!["image/jpeg", "image/png", "image/webp"].includes(f.type)) {
                                toast.warn(
                                  "Bu görüntü formatı desteklenmiyor. JPEG veya PNG çekin ya da galeriden seçin."
                                );
                                return;
                              }
                              void onUploadImage(item.id, f);
                            }}
                          />
                        </label>
                      </div>
                      <div className="flex flex-wrap gap-2 mt-2">
                        {(item.images || []).map((img) => (
                          <div key={img.id} className="relative w-16 h-16">
                            {/* eslint-disable-next-line @next/next/no-img-element */}
                            <img src={listingImageSrc(img.id)} alt="" className="w-full h-full object-cover rounded border" />
                            <button
                              type="button"
                              title="Sil"
                              onClick={() => onDeleteImage(item.id, img.id)}
                              className="absolute -top-1 -right-1 w-5 h-5 bg-red-600 text-white text-xs rounded-full leading-5"
                            >
                              ×
                            </button>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}
                </li>
              ))}
            </ul>
          )}

          {lastPage > 1 && (
            <div className="mt-4 flex gap-3">
              <button
                type="button"
                disabled={listPage <= 1}
                onClick={() => setListPage((p) => Math.max(1, p - 1))}
                className="text-sm px-3 py-1 border rounded disabled:opacity-40"
              >
                Önceki
              </button>
              <span className="text-sm text-qgray pt-1">
                {listPage} / {lastPage}
              </span>
              <button
                type="button"
                disabled={listPage >= lastPage}
                onClick={() => setListPage((p) => p + 1)}
                className="text-sm px-3 py-1 border rounded disabled:opacity-40"
              >
                Sonraki
              </button>
            </div>
          )}
        </div>
      )}

      {section === "create" && isApproved && (
        <div id="second-hand-create-form" className="scroll-mt-24 max-w-4xl space-y-6">
          <div className="relative overflow-hidden rounded-2xl border-2 border-amber-300/80 bg-gradient-to-br from-amber-50 via-white to-amber-50/40 px-5 py-6 sm:px-7 sm:py-7 shadow-md ring-1 ring-amber-900/5">
            <div className="absolute left-0 top-0 h-full w-1.5 bg-qyellow sm:w-2" aria-hidden />
            <div className="pl-3 sm:pl-4">
              <p className="text-[11px] font-extrabold uppercase tracking-[0.12em] text-amber-900/75">İlan ekle</p>
              <h4 className="mt-1 text-xl sm:text-2xl font-bold text-qblack leading-tight">Yeni ilan taslak oluştur</h4>
              <p className="mt-3 max-w-2xl text-sm leading-relaxed text-qgray">
                Zorunlu alanları doldurun; kaydettikten sonra otomatik olarak{" "}
                <span className="font-600 text-qblack">İlanlarım</span> listesine gidersiniz. Taslak üzerinden fotoğraf
                ekleyebilir, metni düzenleyebilir ve <span className="font-600 text-qblack">yayına al</span>abilirsiniz.
              </p>
            </div>
          </div>

          <form onSubmit={handleCreateDraft} className="space-y-5">
            <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm ring-1 ring-black/[0.03] sm:p-6">
              <h5 className="text-sm font-700 text-qblack">Temel bilgiler</h5>
              <p className="mt-0.5 text-xs text-qgray">Başlık ve fiyat zorunludur.</p>
              <div className="mt-4 grid gap-4 md:grid-cols-2">
                <div className="md:col-span-2">
                  <label htmlFor="sh-draft-title" className="mb-1.5 block text-xs font-600 text-qblack">
                    İlan başlığı <span className="text-red-600">*</span>
                  </label>
                  <div className="flex gap-2">
                  <input
                    id="sh-draft-title"
                    placeholder="Örn. Berber koltuğu — az kullanılmış"
                    value={draftForm.title}
                    onChange={(e) => setDraftForm((p) => ({ ...p, title: e.target.value }))}
                    className="h-11 w-full rounded-lg border border-gray-200 bg-white px-3.5 text-sm outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-200/60"
                    required
                  />
                  <button
                    type="button"
                    disabled={aiSuggesting || (!draftForm.description?.trim() && !draftForm.title?.trim())}
                    onClick={() => aiSuggestSecondHand("title")}
                    className="h-11 shrink-0 rounded-lg border border-gray-200 bg-white px-3 text-xs font-800 text-qblack hover:bg-gray-50 disabled:opacity-50"
                    title="AI ile başlık öner"
                  >
                    {aiSuggesting ? "AI…" : "Başlık öner"}
                  </button>
                  </div>
                </div>
                <div>
                  <label htmlFor="sh-draft-price" className="mb-1.5 block text-xs font-600 text-qblack">
                    Fiyat (TRY) <span className="text-red-600">*</span>
                  </label>
                  <input
                    id="sh-draft-price"
                    type="number"
                    min="0"
                    step="0.01"
                    placeholder="0"
                    value={draftForm.price}
                    onChange={(e) => setDraftForm((p) => ({ ...p, price: e.target.value }))}
                    className="h-11 w-full rounded-lg border border-gray-200 bg-white px-3.5 text-sm outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-200/60"
                    required
                  />
                </div>
                <div>
                  <label htmlFor="sh-draft-condition" className="mb-1.5 block text-xs font-600 text-qblack">
                    Ürün durumu
                  </label>
                  <select
                    id="sh-draft-condition"
                    value={draftForm.condition}
                    onChange={(e) => setDraftForm((p) => ({ ...p, condition: e.target.value }))}
                    className="h-11 w-full cursor-pointer rounded-lg border border-gray-200 bg-white px-3.5 text-sm outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-200/60"
                  >
                    {Object.entries(conditionOptions).map(([k, v]) => (
                      <option key={k} value={k}>
                        {v}
                      </option>
                    ))}
                  </select>
                </div>
              </div>
            </div>

            <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm ring-1 ring-black/[0.03] sm:p-6">
              <div className="flex items-start justify-between gap-3">
                <div>
                  <h5 className="text-sm font-700 text-qblack">Kategori</h5>
                  <p className="mt-0.5 text-xs text-qgray">İsteğe bağlı; doğru kategori ilanınızın bulunmasına yardımcı olur.</p>
                </div>
                <button
                  type="button"
                  disabled={aiSuggesting || (!draftForm.description?.trim() && !draftForm.title?.trim())}
                  onClick={() => aiSuggestSecondHand("category")}
                  className="h-9 shrink-0 rounded-lg border border-gray-200 bg-white px-3 text-xs font-800 text-qblack hover:bg-gray-50 disabled:opacity-50"
                  title="AI ile kategori öner"
                >
                  {aiSuggesting ? "AI…" : "Kategori öner"}
                </button>
              </div>
              <div className="mt-4 grid gap-3">
                <select
                  value={draftCatMain}
                  onChange={(e) => {
                    setDraftCatMain(e.target.value);
                    setDraftCatSub("");
                    setDraftCatChild("");
                  }}
                  className="h-11 w-full cursor-pointer rounded-lg border border-gray-200 bg-white px-3.5 text-sm outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-200/60"
                >
                  <option value="">Ana kategori seçin</option>
                  {secondHandCategories.map((c) => (
                    <option key={c.id} value={String(c.id)}>
                      {c.name}
                    </option>
                  ))}
                </select>
                {draftSubOptions.length > 0 && (
                  <select
                    value={draftCatSub}
                    onChange={(e) => {
                      setDraftCatSub(e.target.value);
                      setDraftCatChild("");
                    }}
                    className="h-11 w-full cursor-pointer rounded-lg border border-gray-200 bg-white px-3.5 text-sm outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-200/60"
                  >
                    <option value="">Alt kategori</option>
                    {draftSubOptions.map((s) => (
                      <option key={s.id} value={String(s.id)}>
                        {s.name}
                      </option>
                    ))}
                  </select>
                )}
                {draftChildOptions.length > 0 && (
                  <select
                    value={draftCatChild}
                    onChange={(e) => setDraftCatChild(e.target.value)}
                    className="h-11 w-full cursor-pointer rounded-lg border border-gray-200 bg-white px-3.5 text-sm outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-200/60"
                  >
                    <option value="">Alt-alt kategori</option>
                    {draftChildOptions.map((ch) => (
                      <option key={ch.id} value={String(ch.id)}>
                        {ch.name}
                      </option>
                    ))}
                  </select>
                )}
              </div>
            </div>

            <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm ring-1 ring-black/[0.03] sm:p-6">
              <h5 className="text-sm font-700 text-qblack">Konum</h5>
              <p className="mt-0.5 text-xs text-qgray">İsteğe bağlı; il, ilçe ve mahalle seçebilirsiniz.</p>
              <div className="mt-4">
                <Suspense fallback={<TurkeyAddressFallback />}>
                  <TurkeyAddressSelects value={trDraft} onChange={setTrDraft} className="mt-1" />
                </Suspense>
              </div>
            </div>

            <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm ring-1 ring-black/[0.03] sm:p-6">
              <div className="flex flex-wrap items-start justify-between gap-2">
                <div>
                  <h5 className="text-sm font-700 text-qblack">Fotoğraflar</h5>
                  <p className="mt-0.5 text-xs text-qgray">JPEG, PNG veya WebP — en fazla 6 dosya.</p>
                </div>
                <span className="rounded-full bg-gray-100 px-2.5 py-0.5 text-[11px] font-700 text-qgray">
                  {createPendingFiles.length} / 6
                </span>
              </div>
              <div
                className="mt-4 rounded-xl border-2 border-dashed border-amber-200/90 bg-amber-50/30 px-4 py-6 text-center transition hover:border-amber-300 hover:bg-amber-50/50"
                onDragOver={(e) => {
                  e.preventDefault();
                  e.stopPropagation();
                }}
                onDrop={(e) => {
                  e.preventDefault();
                  e.stopPropagation();
                  appendCreatePendingFiles(e.dataTransfer.files);
                }}
              >
                <div className="flex flex-col items-center gap-3">
                  <p className="text-xs text-qgray">
                    Galeriden çoklu seçin, dosya sürükleyin veya{" "}
                    <span className="font-600 text-qblack">kamerayla anında çekin</span>.
                  </p>
                  <div className="flex flex-wrap items-center justify-center gap-2">
                    <label className="inline-flex cursor-pointer">
                      <span className="rounded-lg bg-qyellow px-4 py-2.5 text-sm font-700 text-qblack shadow-sm transition hover:brightness-95">
                        Galeriden seç
                      </span>
                      <input
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        multiple
                        className="sr-only"
                        onChange={(e) => {
                          appendCreatePendingFiles(e.target.files);
                          e.target.value = "";
                        }}
                      />
                    </label>
                    <label className="inline-flex cursor-pointer">
                      <span className="rounded-lg border-2 border-amber-400 bg-white px-4 py-2.5 text-sm font-700 text-qblack shadow-sm transition hover:bg-amber-50">
                        Kamera (arka)
                      </span>
                      <input
                        type="file"
                        accept="image/*"
                        capture="environment"
                        className="sr-only"
                        onChange={(e) => {
                          const f = e.target.files?.[0];
                          e.target.value = "";
                          if (!f) return;
                          appendCreatePendingFiles([f]);
                        }}
                      />
                    </label>
                    <label className="inline-flex cursor-pointer">
                      <span className="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-600 text-qblack shadow-sm transition hover:bg-gray-50">
                        Ön kamera
                      </span>
                      <input
                        type="file"
                        accept="image/*"
                        capture="user"
                        className="sr-only"
                        onChange={(e) => {
                          const f = e.target.files?.[0];
                          e.target.value = "";
                          if (!f) return;
                          appendCreatePendingFiles([f]);
                        }}
                      />
                    </label>
                  </div>
                </div>
              </div>
              <p className="mt-2 text-center text-[11px] text-qgray sm:hidden">
                Mobilde &quot;Kamera ile çek&quot; ürün fotoğrafı için arka kamerayı açar.
              </p>
              {createPendingFiles.length > 0 && (
                <ul className="mt-4 divide-y divide-gray-100 rounded-lg border border-gray-100 bg-gray-50/50">
                  {createPendingFiles.map((f, i) => (
                    <li key={`${f.name}-${i}`} className="flex items-center justify-between gap-3 px-3 py-2.5 text-sm">
                      <span className="min-w-0 truncate font-medium text-qblack">{f.name}</span>
                      <button
                        type="button"
                        className="shrink-0 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs font-600 text-red-600 transition hover:bg-red-50"
                        onClick={() => setCreatePendingFiles((p) => p.filter((_, j) => j !== i))}
                      >
                        Kaldır
                      </button>
                    </li>
                  ))}
                </ul>
              )}
            </div>

            <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm ring-1 ring-black/[0.03] sm:p-6">
              <div className="flex items-start justify-between gap-3">
                <div>
                  <label htmlFor="sh-draft-desc" className="text-sm font-700 text-qblack">
                    Açıklama
                  </label>
                  <p className="mt-0.5 text-xs text-qgray">İsteğe bağlı; ürünün durumu ve teslimat tercihlerinizi yazabilirsiniz.</p>
                </div>
                <button
                  type="button"
                  disabled={aiSuggesting || !draftForm.description?.trim()}
                  onClick={aiEnhanceSecondHandDescription}
                  className="h-9 shrink-0 rounded-lg border border-gray-200 bg-white px-3 text-xs font-800 text-qblack hover:bg-gray-50 disabled:opacity-50"
                  title="AI ile açıklamayı güzelleştir"
                >
                  {aiSuggesting ? "AI…" : "Açıklamayı güzelleştir"}
                </button>
              </div>
              <textarea
                id="sh-draft-desc"
                placeholder="Ürün hakkında kısa bilgi…"
                value={draftForm.description}
                onChange={(e) => setDraftForm((p) => ({ ...p, description: e.target.value }))}
                rows={5}
                className="mt-3 min-h-[120px] w-full resize-y rounded-lg border border-gray-200 bg-white px-3.5 py-3 text-sm leading-relaxed outline-none transition focus:border-amber-400 focus:ring-2 focus:ring-amber-200/60"
              />
            </div>

            <div className="flex flex-col gap-4 rounded-xl border-2 border-amber-200/70 bg-gradient-to-r from-amber-50/80 to-white px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
              <p className="max-w-md text-xs leading-relaxed text-qgray">
                <span className="font-600 text-qblack">Taslak oluştur</span> dediğinizde ilanınız kaydedilir ve doğrudan{" "}
                <span className="font-600 text-qblack">İlanlarım</span> ekranına yönlendirilirsiniz.
              </p>
              <button
                type="submit"
                disabled={creating}
                className="h-12 shrink-0 rounded-xl bg-qyellow px-8 text-sm font-extrabold text-qblack shadow-md ring-1 ring-amber-900/10 transition hover:brightness-95 disabled:cursor-not-allowed disabled:opacity-50 sm:min-w-[200px]"
              >
                {creating ? "Kaydediliyor…" : "Taslak oluştur"}
              </button>
            </div>
          </form>
        </div>
      )}

      {section === "messages" && (
        <div className="grid md:grid-cols-3 gap-4">
          <div className="md:col-span-1 border border-gray-200 rounded-lg max-h-[480px] overflow-y-auto">
            <div className="sticky top-0 z-10 bg-white border-b border-gray-100 p-2 flex gap-2">
              <button
                type="button"
                onClick={() => setMessageBox("incoming")}
                className={`flex-1 h-9 rounded-md text-xs font-700 border ${
                  messageBox === "incoming" ? "bg-qyellow text-qblack border-qyellow" : "bg-white text-qgray border-gray-200"
                }`}
              >
                Gelen ({incomingConversations.length})
              </button>
              <button
                type="button"
                onClick={() => setMessageBox("outgoing")}
                className={`flex-1 h-9 rounded-md text-xs font-700 border ${
                  messageBox === "outgoing" ? "bg-qyellow text-qblack border-qyellow" : "bg-white text-qgray border-gray-200"
                }`}
              >
                Giden ({outgoingConversations.length})
              </button>
            </div>
            {inboxError ? (
              <div className="p-4 text-sm text-red-600">
                {inboxError?.data?.message || "Mesajlar yüklenemedi."}
              </div>
            ) : null}
            {inboxIsLoading ? (
              <div className="p-4">Yükleniyor…</div>
            ) : visibleConversations.length === 0 ? (
              <div className="p-4 text-sm text-qgray">
                {messageBox === "incoming" ? "Gelen mesaj yok." : "Giden mesaj yok."}
              </div>
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
                      <div className="font-600 truncate">{c.listing?.title || "İlan"}</div>
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
          <div className="md:col-span-2 border border-gray-200 rounded-lg flex flex-col min-h-[360px]">
            {!selectedConversationId ? (
              <div className="p-4 text-sm text-qgray">Soldan bir konuşma seçin.</div>
            ) : threadIsLoading && messages.length === 0 ? (
              <div className="p-4">Yükleniyor…</div>
            ) : (
              <>
                <div className="px-3 py-2 border-b border-gray-100 flex items-center justify-between gap-2">
                  <div className="min-w-0">
                    <div className="text-xs text-qgray truncate">{selectedConversation?.listing?.title || "İlan"}</div>
                    <div className="text-sm font-700 text-qblack truncate">
                      {String(selectedConversation?.counterparty_role || "") === "seller"
                        ? (selectedConversation?.seller_business_name || selectedConversation?.counterparty_display || "Satıcı")
                        : (selectedConversation?.counterparty_display || "Alıcı")}
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <button
                      type="button"
                      onClick={() => setMediaOpen((v) => !v)}
                      className="h-8 px-3 rounded-lg border border-gray-200 text-xs font-700 text-qblack hover:bg-gray-50 disabled:opacity-50"
                      disabled={!selectedConversationId}
                    >
                      Medya
                    </button>
                    <button
                      type="button"
                      onClick={() => {
                        setActionsOpen((v) => !v);
                        setActionError("");
                      }}
                      className="h-8 px-3 rounded-lg border border-gray-200 text-xs font-700 text-qblack hover:bg-gray-50 disabled:opacity-50"
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
                      <button type="button" onClick={sendOffer} className="h-9 px-3 rounded-lg bg-qblack text-white text-xs font-700">
                        Gönder
                      </button>
                      <button type="button" onClick={() => setOfferOpen(false)} className="h-9 px-3 rounded-lg border border-gray-200 text-xs font-700 text-qblack hover:bg-gray-50">
                        Kapat
                      </button>
                    </div>
                  </div>
                ) : null}

                {mediaOpen ? (
                  <div className="px-3 py-3 border-b border-gray-100 bg-white">
                    {mediaItems.length === 0 ? (
                      <div className="text-xs text-qgray">Bu konuşmada henüz ek yok.</div>
                    ) : (
                      <>
                        <div className="text-[11px] text-qgray mb-2">Toplam {mediaItems.length} ek</div>
                        <div className="grid grid-cols-6 gap-2">
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

                {actionsOpen ? (
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
                        className="h-9 px-3 rounded-lg bg-qblack text-white text-xs font-700 disabled:opacity-50"
                      >
                        {reportIsLoading ? "Gönderiliyor…" : "Şikayet et"}
                      </button>
                      <button
                        type="button"
                        onClick={doBlock}
                        disabled={blockIsLoading || !counterpartyId}
                        className="h-9 px-3 rounded-lg border border-red-200 text-red-700 text-xs font-700 hover:bg-red-50 disabled:opacity-50"
                      >
                        {blockIsLoading ? "İşleniyor…" : "Engelle"}
                      </button>
                      <button
                        type="button"
                        onClick={doUnblock}
                        disabled={unblockIsLoading || !counterpartyId}
                        className="h-9 px-3 rounded-lg border border-gray-200 text-xs font-700 text-qblack hover:bg-gray-50 disabled:opacity-50"
                      >
                        {unblockIsLoading ? "İşleniyor…" : "Engeli kaldır"}
                      </button>
                      <button
                        type="button"
                        onClick={() => setActionsOpen(false)}
                        className="h-9 px-3 rounded-lg border border-gray-200 text-xs font-700 text-qblack hover:bg-gray-50"
                      >
                        Kapat
                      </button>
                    </div>
                    {actionError ? <div className="mt-2 text-xs text-red-600">{actionError}</div> : null}
                    <div className="mt-1 text-[11px] text-qgray">
                      Not: Şikayet/engelleme için ikinci el doğrulamanızın onaylı olması gerekir.
                    </div>
                  </div>
                ) : null}

                {threadIsFetching && messages.length > 0 ? (
                  <div className="px-3 py-2 text-[11px] text-qgray border-b border-gray-100">
                    Güncelleniyor…
                  </div>
                ) : null}
                <div className="flex-1 overflow-y-auto p-3 space-y-2 max-h-[320px]">
                  {messages.map((m) => (
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
                            const href = a.url ? a.url : (a.path ? `${appConfig.BASE_URL}storage/${a.path}` : "#");
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
                      <div className="text-[10px] text-qgray mt-1">
                        {m.created_at ? new Date(m.created_at).toLocaleString("tr-TR") : ""}
                        {m.read_at ? " · Okundu" : ""}
                      </div>
                    </div>
                  ))}
                </div>
                <div className="px-3 pt-2 border-t border-gray-100 bg-white">
                  <div className="flex flex-wrap gap-2">
                    <button
                      type="button"
                      onClick={() => sendQuickText("Merhaba, ürün halen satılık mı?")}
                      className="h-8 px-3 rounded-full border border-gray-200 text-xs font-700 text-qblack hover:bg-gray-50 disabled:opacity-50"
                      disabled={!selectedConversationId}
                    >
                      Satılık mı?
                    </button>
                    <button
                      type="button"
                      onClick={() => setOfferOpen((v) => !v)}
                      className="h-8 px-3 rounded-full border border-gray-200 text-xs font-700 text-qblack hover:bg-gray-50 disabled:opacity-50"
                      disabled={!selectedConversationId}
                    >
                      Teklif yap
                    </button>
                  </div>
                </div>
                <div className="p-2 border-t border-gray-200 flex gap-2">
                  <label className="h-10 w-10 inline-flex items-center justify-center rounded-md border border-gray-200 cursor-pointer hover:bg-gray-50">
                    <input
                      type="file"
                      multiple
                      accept="image/*,.pdf,.heic,.heif"
                      className="hidden"
                      onChange={(e) => {
                        const picked = Array.from(e.target.files || []);
                        if (!picked.length) return;
                        setReplyFiles((prev) => [...(prev || []), ...picked].slice(0, 3));
                        e.target.value = "";
                      }}
                    />
                    +
                  </label>
                  <input
                    value={replyBody}
                    onChange={(e) => setReplyBody(e.target.value)}
                    placeholder="Yanıt yazın…"
                    className="flex-1 h-10 px-3 border border-gray-200 rounded-md text-sm"
                    onKeyDown={(e) => {
                      if (e.key === "Enter" && !e.shiftKey) {
                        e.preventDefault();
                        sendReplyHandler();
                      }
                    }}
                  />
                  <button
                    type="button"
                    onClick={sendReplyHandler}
                    className="h-10 px-4 bg-qblack text-white rounded-md text-sm"
                  >
                    Gönder
                  </button>
                </div>
                {replyFiles.length > 0 ? (
                  <div className="px-3 pb-2 text-xs text-qgray flex flex-wrap gap-2">
                    {replyFiles.map((f) => (
                      <span key={`${f.name}-${f.size}`} className="inline-flex items-center gap-2 rounded-full bg-gray-100 px-2 py-1">
                        {f.name}
                        <button
                          type="button"
                          className="text-qblack"
                          onClick={() => setReplyFiles((prev) => (prev || []).filter((x) => x !== f))}
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
      )}
      <ConsentModal
        open={consentModal === "terms"}
        title="İkinci El Kullanım Koşulları"
        body="İkinci el doğrulama ve ilan süreçlerinde geçerli sözleşme maddelerini bu pencereden inceleyebilirsiniz. Tam metni yeni sekmede açabilirsiniz."
        href={secondHandPageUrl("/ikinci-el-sozlesmesi")}
        onClose={() => setConsentModal("")}
      />
      <ConsentModal
        open={consentModal === "privacy"}
        title="KVKK / Gizlilik Politikası"
        body="Doğrulama başvurusunda paylaştığınız verilerin nasıl işlendiğini ve korunduğunu bu metinden inceleyebilirsiniz. Tam metni yeni sekmede açabilirsiniz."
        href={secondHandPageUrl("/ikinci-el-kvkk")}
        onClose={() => setConsentModal("")}
      />
    </div>
  );
}

export default memo(SecondHandTab);
