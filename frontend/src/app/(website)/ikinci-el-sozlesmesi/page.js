import { fetchSecondHandAgreements } from "@/api/secondHandPublic";

export const revalidate = 120;

export default async function SecondHandTermsPage() {
  const data = await fetchSecondHandAgreements();
  const title = data?.terms_title || "İkinci El Kullanım Koşulları";
  const content = String(data?.terms_content || "").trim();

  return (
    <div className="container-x mx-auto py-10">
      <div className="max-w-4xl mx-auto rounded-xl border border-gray-200 bg-white p-6">
        <h1 className="text-2xl font-700 text-qblack mb-4">{title}</h1>
        {content ? (
          <div className="text-sm text-qblack whitespace-pre-wrap leading-relaxed">{content}</div>
        ) : (
          <p className="text-sm text-qgray">Sözleşme metni henüz eklenmedi.</p>
        )}
      </div>
    </div>
  );
}

