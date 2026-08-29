"use client";

import { useGetSellerDetailApiQuery } from "@/redux/features/product/apiSlice";
import Image from "next/image";
import ProductCardStyleOne from "../Helpers/Cards/ProductCardStyleOne";
import Star from "../Helpers/icons/Star";
import Loader from "../Helpers/Loader";
import Breadcrumb from "../Breadcrumb";

export default function SellerProfile({ slug }) {
    const { data, isLoading, isError } = useGetSellerDetailApiQuery(slug);
    const baseUrl = process.env.NEXT_PUBLIC_BASE_URL || "";

    if (isLoading) return <Loader />;
    if (isError || !data?.seller) return <div className="py-20 text-center">Satici bulunamadi.</div>;

    const { seller, products } = data;

    return (
        <>
            <Breadcrumb
                title={seller.shop_name}
                paths={[
                    { name: "Anasayfa", path: "/" },
                    { name: "Saticilar", path: "/sellers" },
                    { name: seller.shop_name, path: `/seller/${slug}` }
                ]}
            />
            
            <section className="seller-profile py-10">
                <div className="container-x mx-auto px-4">
                    {/* Seller Header Card */}
                    <div className="bg-white rounded-3xl p-8 md:p-12 shadow-sm border border-gray-100 flex flex-col md:flex-row gap-8 items-center md:items-start mb-16 relative overflow-hidden">
                        <div className="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full -mr-32 -mt-32 blur-3xl" />
                        
                        <div className="relative w-40 h-40 md:w-56 md:h-56 rounded-full overflow-hidden border-8 border-gray-50 shadow-xl shrink-0">
                            <Image
                                src={seller.logo ? `${baseUrl}${seller.logo}` : "/assets/images/placeholder-seller.jpg"}
                                alt={seller.shop_name}
                                fill
                                className="object-contain p-4 bg-white"
                            />
                        </div>

                        <div className="flex-1 text-center md:text-left pt-4">
                            <div className="flex flex-wrap items-center justify-center md:justify-start gap-4 mb-4">
                                <h1 className="text-4xl font-extrabold text-gray-900">{seller.shop_name}</h1>
                                <span className="bg-green-100 text-green-600 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-widest flex items-center gap-1">
                                    <span className="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse" />
                                    Onayli Satici
                                </span>
                            </div>

                            <div className="flex items-center justify-center md:justify-start gap-2 mb-6">
                                <div className="flex">
                                    {[...Array(5)].map((_, i) => (
                                        <Star key={i} defaultValue={i < parseInt(seller.averageRating)} />
                                    ))}
                                </div>
                                <span className="text-gray-900 font-bold ml-1">({seller.averageRating || 0})</span>
                                <span className="text-gray-400 text-sm ml-4 border-l border-gray-200 pl-4">{products?.total || 0} urun</span>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                                <div className="flex items-start gap-3">
                                    <div className="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0">
                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div className="overflow-hidden">
                                        <span className="block text-[10px] uppercase font-bold tracking-widest text-gray-400">E-posta</span>
                                        <span className="text-gray-900 font-medium truncate block">{seller.email}</span>
                                    </div>
                                </div>
                                <div className="flex items-start gap-3">
                                    <div className="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-500 shrink-0">
                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <span className="block text-[10px] uppercase font-bold tracking-widest text-gray-400">Telefon</span>
                                        <span className="text-gray-900 font-medium">{seller.phone}</span>
                                    </div>
                                </div>
                                <div className="flex items-start gap-3">
                                    <div className="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center text-orange-500 shrink-0">
                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <span className="block text-[10px] uppercase font-bold tracking-widest text-gray-400">Konum</span>
                                        <span className="text-gray-900 font-medium line-clamp-1">{seller.address}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <button className="bg-qblack text-white px-8 py-3 rounded-2xl font-bold hover:bg-black transition-all flex items-center gap-2 transform active:scale-95 shadow-xl shadow-black/10">
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                Saticiya Ulas
                            </button>
                        </div>
                    </div>

                    {/* Shop Products Section */}
                    <div className="mb-20">
                        <div className="flex items-center justify-between mb-10">
                            <h2 className="text-2xl font-extrabold text-gray-900 border-l-4 border-primary pl-4">{seller.shop_name} magazasinin urunleri</h2>
                        </div>
                        
                        {products?.data?.length > 0 ? (
                            <div className="grid grid-cols-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 md:gap-6">
                                {products.data.map((product) => (
                                    <div key={product.id} className="animate-in fade-in slide-in-from-bottom-2 duration-500">
                                        <ProductCardStyleOne datas={product} />
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-20 bg-gray-50 rounded-3xl border border-dashed border-gray-200">
                                <p className="text-gray-500">Bu saticinin henuz yayinda urunu bulunmuyor.</p>
                            </div>
                        )}
                    </div>
                </div>
            </section>
        </>
    );
}
