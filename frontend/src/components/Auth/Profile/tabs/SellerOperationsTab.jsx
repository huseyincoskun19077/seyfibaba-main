"use client";

import { useEffect, useState } from "react";
import { toast } from "react-toastify";
import apiRoutes from "@/appConfig/apiRoutes";
import {
  useDeleteSellerKycDocumentApiMutation,
  useSellerBulkImportsApiQuery,
  useSellerKycDocumentsApiQuery,
  useSellerKycStatusApiQuery,
  useSellerLowStockProductsApiQuery,
  useSellerNotificationsApiQuery,
  useMarkSellerNotificationReadApiMutation,
  useMarkAllSellerNotificationsReadApiMutation,
  useUploadSellerBulkImportApiMutation,
  useUploadSellerKycDocumentApiMutation,
} from "@/redux/features/auth/apiSlice";

const DOCUMENT_TYPE_OPTIONS = [
  { value: "tax_certificate", label: "Vergi Levhası" },
];

const DOCUMENT_TYPE_LABELS = {
  tax_certificate: "Vergi Levhası",
  identity_front: "Kimlik Ön Yüz",
  identity_back: "Kimlik Arka Yüz",
  address_proof: "Adres Belgesi",
  bank_statement: "Banka Hesap Özeti",
  iban_document: "IBAN Belgesi",
};

const STATUS_STYLES = {
  not_submitted: "bg-gray-100 text-gray-700",
  pending: "bg-yellow-100 text-yellow-800",
  approved: "bg-green-100 text-green-800",
  rejected: "bg-red-100 text-red-800",
};

const STATUS_LABELS = {
  not_submitted: "Gönderilmedi",
  pending: "İnceleniyor",
  approved: "Onaylandı",
  rejected: "Reddedildi",
};

const DOCUMENT_STATUS_STYLES = {
  pending: "bg-yellow-100 text-yellow-800",
  approved: "bg-green-100 text-green-800",
  rejected: "bg-red-100 text-red-800",
};

const DOCUMENT_STATUS_LABELS = {
  pending: "İnceleniyor",
  approved: "Onaylandı",
  rejected: "Reddedildi",
};

function formatDateTime(value) {
  if (!value) {
    return "-";
  }

  try {
    return new Intl.DateTimeFormat("tr-TR", {
      dateStyle: "medium",
      timeStyle: "short",
    }).format(new Date(value));
  } catch (error) {
    return value;
  }
}

function formatBytes(size) {
  if (!size) {
    return "0 B";
  }

  const units = ["B", "KB", "MB", "GB"];
  let value = size;
  let unitIndex = 0;

  while (value >= 1024 && unitIndex < units.length - 1) {
    value /= 1024;
    unitIndex += 1;
  }

  return `${value.toFixed(unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
}

function ErrorList({ errors = [] }) {
  if (!errors.length) {
    return null;
  }

  return (
    <div className="mt-3 rounded-lg bg-red-50 p-3 text-sm text-red-700">
      {errors.slice(0, 3).map((error, index) => (
        <p key={`${error.row || index}-${error.message}`}>
          Satır {error.row || "?"}: {error.message}
        </p>
      ))}
      {errors.length > 3 ? <p>+{errors.length - 3} hata daha</p> : null}
    </div>
  );
}

function extractNotificationMessage(notification) {
  if (notification?.data?.message) {
    return notification.data.message;
  }

  if (notification?.data?.title) {
    return notification.data.title;
  }

  return "Bildirim alındı.";
}

export default function SellerOperationsTab({ token, isActive, isSeller }) {
  const [kycForm, setKycForm] = useState({
    documentType: DOCUMENT_TYPE_OPTIONS[0].value,
    document: null,
    iban: "",
    taxNumber: "",
  });
  const [bulkFile, setBulkFile] = useState(null);

  const {
    data: sellerKycDocumentsApi,
    isFetching: isKycFetching,
    refetch: refetchKycDocuments,
  } = useSellerKycDocumentsApiQuery(
    { token },
    {
      skip: !token || !isActive || !isSeller,
    }
  );

  const {
    data: sellerKycStatusApi,
    isFetching: isKycStatusFetching,
    refetch: refetchKycStatus,
  } = useSellerKycStatusApiQuery(
    { token },
    {
      skip: !token || !isActive || !isSeller,
    }
  );

  const {
    data: sellerBulkImportsApi,
    isFetching: isBulkImportsFetching,
    refetch: refetchBulkImports,
  } = useSellerBulkImportsApiQuery(
    { token },
    {
      skip: !token || !isActive || !isSeller,
    }
  );
  const {
    data: sellerLowStockProductsApi,
    isFetching: isLowStockFetching,
    refetch: refetchLowStockProducts,
  } = useSellerLowStockProductsApiQuery(
    { token, perPage: 10 },
    {
      skip: !token || !isActive || !isSeller,
    }
  );
  const {
    data: sellerNotificationsApi,
    isFetching: isNotificationsFetching,
    refetch: refetchNotifications,
  } = useSellerNotificationsApiQuery(
    { token, perPage: 10 },
    {
      skip: !token || !isActive || !isSeller,
    }
  );

  const [uploadSellerKycDocumentApi, { isLoading: isKycUploading }] =
    useUploadSellerKycDocumentApiMutation();
  const [deleteSellerKycDocumentApi, { isLoading: isKycDeleting }] =
    useDeleteSellerKycDocumentApiMutation();
  const [uploadSellerBulkImportApi, { isLoading: isBulkUploading }] =
    useUploadSellerBulkImportApiMutation();
  const [markSellerNotificationReadApi, { isLoading: isNotificationUpdating }] =
    useMarkSellerNotificationReadApiMutation();
  const [markAllSellerNotificationsReadApi, { isLoading: isMarkingAllNotifications }] =
    useMarkAllSellerNotificationsReadApiMutation();

  useEffect(() => {
    if (sellerKycStatusApi?.status) {
      setKycForm((prev) => ({
        ...prev,
        iban: sellerKycStatusApi.status.iban || "",
        taxNumber: sellerKycStatusApi.status.tax_number || "",
      }));
    }
  }, [sellerKycStatusApi]);

  if (!isSeller) {
    return (
      <div className="rounded-lg border border-qgray-border p-6">
        <h2 className="text-xl font-semibold text-qblack">Hesap Doğrulama</h2>
        <p className="mt-3 text-sm text-qgray">
          Bu alan yalnızca satıcı hesaplarında kullanılabilir.
        </p>
      </div>
    );
  }

  const handleKycUpload = async (event) => {
    event.preventDefault();

    if (!kycForm.document) {
      toast.error("Lütfen bir belge dosyası seçin.");
      return;
    }

    await uploadSellerKycDocumentApi({
      token,
      data: {
        document_type: kycForm.documentType,
        document: kycForm.document,
        iban: kycForm.iban.trim() || null,
        tax_number: kycForm.taxNumber.trim() || null,
      },
      success: async (data) => {
        toast.success(data?.message || "KYC belgesi yüklendi.");
        setKycForm((prev) => ({
          ...prev,
          document: null,
        }));
        const fileInput = document.getElementById("seller-kyc-document");
        if (fileInput) {
          fileInput.value = "";
        }
        await Promise.all([refetchKycDocuments(), refetchKycStatus()]);
      },
      error: (error) => {
        toast.error(error?.data?.message || "KYC yükleme başarısız oldu.");
      },
    });
  };

  const handleDeleteDocument = async (id) => {
    await deleteSellerKycDocumentApi({
      token,
      id,
      success: async (data) => {
        toast.success(data?.message || "KYC belgesi silindi.");
        await Promise.all([refetchKycDocuments(), refetchKycStatus()]);
      },
      error: (error) => {
        toast.error(error?.data?.message || "Belge silinemedi.");
      },
    });
  };

  const handleBulkUpload = async (event) => {
    event.preventDefault();

    if (!bulkFile) {
      toast.error("Lütfen bir CSV veya XLSX dosyası seçin.");
      return;
    }

    await uploadSellerBulkImportApi({
      token,
      file: bulkFile,
      success: async (data) => {
        toast.success(data?.message || "Toplu içe aktarma işlendi.");
        setBulkFile(null);
        const fileInput = document.getElementById("seller-bulk-import-file");
        if (fileInput) {
          fileInput.value = "";
        }
        await refetchBulkImports();
      },
      error: (error) => {
        toast.error(error?.data?.message || "Toplu içe aktarma başarısız oldu.");
      },
    });
  };

  const kycStatus = sellerKycStatusApi?.status?.kyc_status || "not_submitted";
  const documents = sellerKycDocumentsApi?.documents || [];
  const imports = sellerBulkImportsApi?.imports?.data || [];
  const lowStockProducts = sellerLowStockProductsApi?.products?.data || [];
  const notifications = sellerNotificationsApi?.notifications?.data || [];
  const unreadCount = sellerNotificationsApi?.unread_count || 0;
  const lowStockThreshold = sellerLowStockProductsApi?.threshold;
  const templateUrl = `${apiRoutes.sellerBulkImportTemplate}?token=${token}`;

  const handleNotificationRead = async (id) => {
    await markSellerNotificationReadApi({
      token,
      id,
      success: async (data) => {
        toast.success(data?.message || "Bildirim okundu olarak işaretlendi.");
        await refetchNotifications();
      },
      error: (error) => {
        toast.error(error?.data?.message || "Bildirim güncellenemedi.");
      },
    });
  };

  const handleMarkAllNotificationsRead = async () => {
    await markAllSellerNotificationsReadApi({
      token,
      success: async (data) => {
        toast.success(data?.message || "Tüm bildirimler okundu olarak işaretlendi.");
        await refetchNotifications();
      },
      error: (error) => {
        toast.error(error?.data?.message || "Toplu bildirim güncellemesi başarısız oldu.");
      },
    });
  };

  return (
    <div className="space-y-8">
      <div className="rounded-lg border border-qgray-border p-6">
        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 className="text-xl font-semibold text-qblack">Satıcı KYC</h2>
            <p className="mt-1 text-sm text-qgray">
              Satıcı hesabınızı aktif tutmak için gerekli belgeleri yükleyin.
            </p>
          </div>
          <span
            className={`inline-flex w-fit rounded-full px-3 py-1 text-sm font-semibold ${
              STATUS_STYLES[kycStatus] || STATUS_STYLES.not_submitted
            }`}
          >
            {STATUS_LABELS[kycStatus] || kycStatus.replace("_", " ")}
          </span>
        </div>

        <div className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <div className="rounded-lg bg-gray-50 p-4">
            <p className="text-sm text-qgray">Gönderildi</p>
            <p className="mt-2 text-sm font-semibold text-qblack">
              {formatDateTime(sellerKycStatusApi?.status?.submitted_at)}
            </p>
          </div>
          <div className="rounded-lg bg-gray-50 p-4">
            <p className="text-sm text-qgray">Onaylandı</p>
            <p className="mt-2 text-sm font-semibold text-qblack">
              {formatDateTime(sellerKycStatusApi?.status?.approved_at)}
            </p>
          </div>
          <div className="rounded-lg bg-gray-50 p-4">
            <p className="text-sm text-qgray">Belgeler</p>
            <p className="mt-2 text-sm font-semibold text-qblack">
              {sellerKycStatusApi?.status?.document_count || 0}
            </p>
          </div>
          <div className="rounded-lg bg-gray-50 p-4">
            <p className="text-sm text-qgray">Reddedilen Türler</p>
            <p className="mt-2 text-sm font-semibold text-qblack">
              {sellerKycStatusApi?.status?.rejected_document_types?.length || 0}
            </p>
          </div>
        </div>

        <form className="mt-6 grid gap-4 lg:grid-cols-2" onSubmit={handleKycUpload}>
          <div>
            <label className="mb-2 block text-sm text-qgray">Belge Türü</label>
            <select
              value={kycForm.documentType}
              onChange={(event) =>
                setKycForm((prev) => ({
                  ...prev,
                  documentType: event.target.value,
                }))
              }
              className="h-[50px] w-full border border-qgray-border px-4 text-sm focus:outline-none"
            >
              {DOCUMENT_TYPE_OPTIONS.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="mb-2 block text-sm text-qgray">Belge Dosyası</label>
            <input
              id="seller-kyc-document"
              type="file"
              accept=".pdf,.jpg,.jpeg,.png"
              onChange={(event) =>
                setKycForm((prev) => ({
                  ...prev,
                  document: event.target.files?.[0] || null,
                }))
              }
              className="flex h-[50px] w-full items-center border border-qgray-border px-4 py-3 text-sm focus:outline-none"
            />
          </div>

          <div>
            <label className="mb-2 block text-sm text-qgray">IBAN</label>
            <input
              type="text"
              value={kycForm.iban}
              onChange={(event) =>
                setKycForm((prev) => ({
                  ...prev,
                  iban: event.target.value,
                }))
              }
              placeholder="TR..."
              className="h-[50px] w-full border border-qgray-border px-4 text-sm focus:outline-none"
            />
          </div>

          <div>
            <label className="mb-2 block text-sm text-qgray">Vergi Numarası</label>
            <input
              type="text"
              value={kycForm.taxNumber}
              onChange={(event) =>
                setKycForm((prev) => ({
                  ...prev,
                  taxNumber: event.target.value,
                }))
              }
              className="h-[50px] w-full border border-qgray-border px-4 text-sm focus:outline-none"
            />
          </div>

          <div className="lg:col-span-2 flex justify-end">
            <button
              type="submit"
              disabled={isKycUploading}
              className="h-[46px] px-6 bg-qyellow text-qblack text-sm font-semibold disabled:opacity-60"
            >
              {isKycUploading ? "Yükleniyor..." : "KYC Belgesini Yükle"}
            </button>
          </div>
        </form>

        <div className="mt-6 overflow-x-auto">
          <table className="w-full text-sm text-left text-gray-600">
            <thead>
              <tr className="border-b border-qgray-border text-qgray">
                <th className="py-3 pr-4 font-medium">Belge</th>
                <th className="py-3 pr-4 font-medium">Dosya</th>
                <th className="py-3 pr-4 font-medium">Size</th>
                <th className="py-3 pr-4 font-medium">Durum</th>
                <th className="py-3 pr-4 font-medium">İncelendi</th>
                <th className="py-3 font-medium text-right">İşlem</th>
              </tr>
            </thead>
            <tbody>
              {documents.length > 0 ? (
                documents.map((documentItem) => (
                  <tr key={documentItem.id} className="border-b border-qgray-border/60">
                    <td className="py-4 pr-4 font-medium text-qblack">
                      {DOCUMENT_TYPE_LABELS[documentItem.document_type] ||
                        DOCUMENT_TYPE_OPTIONS.find(
                          (option) => option.value === documentItem.document_type
                        )?.label ||
                        documentItem.document_type}
                    </td>
                    <td className="py-4 pr-4">{documentItem.original_name}</td>
                    <td className="py-4 pr-4">{formatBytes(documentItem.file_size)}</td>
                    <td className="py-4 pr-4">
                      <span
                        className={`rounded-full px-3 py-1 text-xs font-semibold ${
                          DOCUMENT_STATUS_STYLES[documentItem.status] ||
                          "bg-gray-100 text-gray-700"
                        }`}
                      >
                        {DOCUMENT_STATUS_LABELS[documentItem.status] || documentItem.status}
                      </span>
                    </td>
                    <td className="py-4 pr-4">
                      {formatDateTime(documentItem.reviewed_at)}
                    </td>
                    <td className="py-4 text-right">
                      {documentItem.status === "pending" ? (
                        <button
                          type="button"
                          disabled={isKycDeleting}
                          onClick={() => handleDeleteDocument(documentItem.id)}
                          className="text-sm font-medium text-red-600 disabled:opacity-60"
                        >
                          Sil
                        </button>
                      ) : (
                        <span className="text-xs text-qgray">Kilitli</span>
                      )}
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan="6" className="py-6 text-center text-qgray">
                    {isKycFetching || isKycStatusFetching
                      ? "Satıcı KYC verileri yükleniyor..."
                      : "Henüz KYC belgesi yüklenmedi."}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      <div className="grid gap-8 xl:grid-cols-2">
        <div className="rounded-lg border border-qgray-border p-6">
          <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
              <h2 className="text-xl font-semibold text-qblack">Düşük Stoklu Ürünler</h2>
              <p className="mt-1 text-sm text-qgray">
                Mevcut stok eşiğinde veya altında olan aktif ürünler.
              </p>
            </div>
            <div className="rounded-full bg-red-50 px-3 py-1 text-sm font-semibold text-red-700">
              Eşik: {lowStockThreshold ?? "-"}
            </div>
          </div>

          <div className="mt-6 space-y-3">
            {lowStockProducts.length > 0 ? (
              lowStockProducts.map((product) => (
                <div
                  key={product.id}
                  className="flex items-start justify-between rounded-lg border border-qgray-border p-4"
                >
                  <div>
                    <p className="text-sm font-semibold text-qblack">{product.name}</p>
                    <p className="mt-1 text-xs text-qgray">
                      SKU: {product.sku || "-"} | Slug: {product.slug}
                    </p>
                  </div>
                  <div className="text-right">
                    <p className="text-xs text-qgray">Adet</p>
                    <p className="text-lg font-bold text-red-700">{product.qty}</p>
                  </div>
                </div>
              ))
            ) : (
              <div className="rounded-lg bg-gray-50 p-4 text-sm text-qgray">
                {isLowStockFetching
                  ? "Düşük stoklu ürünler yükleniyor..."
                  : "Düşük stoklu ürün bulunamadı."}
              </div>
            )}
          </div>
        </div>

        <div className="rounded-lg border border-qgray-border p-6">
          <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
              <h2 className="text-xl font-semibold text-qblack">Bildirimler</h2>
              <p className="mt-1 text-sm text-qgray">
                Satıcı uyarıları ve operasyonel mesajlar.
              </p>
            </div>
            <div className="flex items-center gap-3">
              <span className="rounded-full bg-yellow-50 px-3 py-1 text-sm font-semibold text-yellow-800">
                Okunmamış: {unreadCount}
              </span>
              <button
                type="button"
                disabled={isMarkingAllNotifications || unreadCount === 0}
                onClick={handleMarkAllNotificationsRead}
                className="text-sm font-semibold text-qblack underline disabled:opacity-50"
              >
                Tümünü okundu yap
              </button>
            </div>
          </div>

          <div className="mt-6 space-y-3">
            {notifications.length > 0 ? (
              notifications.map((notification) => (
                <div
                  key={notification.id}
                  className={`rounded-lg border p-4 ${
                    notification.read_at
                      ? "border-qgray-border bg-white"
                      : "border-yellow-200 bg-yellow-50/60"
                  }`}
                >
                  <div className="flex items-start justify-between gap-4">
                    <div>
                      <p className="text-sm font-semibold text-qblack">
                        {extractNotificationMessage(notification)}
                      </p>
                      <p className="mt-1 text-xs text-qgray">
                        Tür: {notification.data?.type || "genel"} |{" "}
                        {formatDateTime(notification.created_at)}
                      </p>
                    </div>
                    {notification.read_at ? (
                      <span className="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                        Okundu
                      </span>
                    ) : (
                      <button
                        type="button"
                        disabled={isNotificationUpdating}
                        onClick={() => handleNotificationRead(notification.id)}
                        className="text-sm font-semibold text-qblack underline disabled:opacity-50"
                      >
                        Okundu yap
                      </button>
                    )}
                  </div>
                </div>
              ))
            ) : (
              <div className="rounded-lg bg-gray-50 p-4 text-sm text-qgray">
                {isNotificationsFetching
                  ? "Bildirimler yükleniyor..."
                  : "Bildirim bulunamadı."}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
