export default function HomepageSkeleton() {
  return (
    <div className="w-full">
      {/* Header Skeleton */}
      <header className="header-section-wrapper relative print:hidden">
        {/* TopBar Skeleton */}
        <div className="quomodo-shop-top-bar bg-gray-100 py-2">
          <div className="container-x mx-auto">
            <div className="flex justify-between items-center">
              <div className="w-48 h-4 bg-gray-200 rounded animate-pulse"></div>
              <div className="flex space-x-4">
                <div className="w-24 h-4 bg-gray-200 rounded animate-pulse"></div>
                <div className="w-24 h-4 bg-gray-200 rounded animate-pulse"></div>
              </div>
            </div>
          </div>
        </div>

        {/* Middlebar Skeleton - Hidden on mobile */}
        <div className="quomodo-shop-middle-bar lg:block hidden py-4 border-b">
          <div className="container-x mx-auto">
            <div className="flex justify-between items-center">
              {/* Logo */}
              <div className="w-[153px] h-[44px] bg-gray-200 rounded animate-pulse"></div>

              {/* Search Bar */}
              <div className="flex-1 mx-8">
                <div className="w-full h-12 bg-gray-200 rounded animate-pulse"></div>
              </div>

              {/* Header Actions */}
              <div className="flex space-x-4">
                <div className="w-10 h-10 bg-gray-200 rounded-full animate-pulse"></div>
                <div className="w-10 h-10 bg-gray-200 rounded-full animate-pulse"></div>
                <div className="w-10 h-10 bg-gray-200 rounded-full animate-pulse"></div>
              </div>
            </div>
          </div>
        </div>

        {/* Mobile Header Skeleton */}
        <div className="quomodo-shop-drawer lg:hidden block w-full h-[60px] bg-white">
          <div className="w-full h-full flex justify-between items-center px-5">
            <div className="w-6 h-6 bg-gray-200 rounded animate-pulse"></div>
            <div className="w-[153px] h-[44px] bg-gray-200 rounded animate-pulse"></div>
            <div className="w-6 h-6 bg-gray-200 rounded animate-pulse"></div>
          </div>
        </div>

        {/* Navigation Skeleton - Hidden on mobile */}
        <div className="quomodo-shop-nav-bar lg:block hidden py-3 bg-gray-50">
          <div className="container-x mx-auto">
            <div className="flex space-x-8">
              {[...Array(8)].map((_, i) => (
                <div
                  key={i}
                  className="w-20 h-4 bg-gray-200 rounded animate-pulse"
                ></div>
              ))}
            </div>
          </div>
        </div>
      </header>

      {/* Main Content Skeleton */}
      <main className="w-full pt-[30px] pb-[60px]">
        <div className="container-x mx-auto">
          {/* Banner Skeleton */}
          <div className="banner-wrapper md:mb-[60px] mb-[30px]">
            <div className="main-wrapper w-full">
              <div className="banner-card xl:flex xl:space-x-[30px] rtl:space-x-0 xl:h-[600px] mb-[30px]">
                {/* Main Slider */}
                <div className="xl:w-[740px] w-full xl:h-full md:h-[500px] h-[220px] xl:mb-0 mb-2">
                  <div className="w-full h-full bg-gray-200 rounded animate-pulse"></div>
                </div>

                {/* Sidebar Banners */}
                <div className="flex-1 flex xl:flex-col flex-row xl:space-y-[30px] xl:h-full md:h-[350px] h-[150px]">
                  <div className="w-full xl:h-1/2 xl:mr-0 mr-2 bg-gray-200 rounded animate-pulse"></div>
                  <div className="w-full xl:h-1/2 bg-gray-200 rounded animate-pulse"></div>
                </div>
              </div>

              {/* Services Section */}
              <div className="best-services w-full bg-white flex flex-col space-y-10 lg:space-y-0 lg:flex-row lg:justify-between lg:items-center lg:h-[110px] px-10 lg:py-0 py-10">
                {[...Array(4)].map((_, i) => (
                  <div key={i} className="item">
                    <div className="flex space-x-5 rtl:space-x-reverse items-center">
                      <div className="w-10 h-10 bg-gray-200 rounded animate-pulse"></div>
                      <div>
                        <div className="w-32 h-4 bg-gray-200 rounded animate-pulse mb-1"></div>
                        <div className="w-24 h-3 bg-gray-200 rounded animate-pulse"></div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>

          {/* Categories Skeleton */}
          <div className="category-section-wrapper w-full">
            <div className="container-x mx-auto pb-[60px]">
              <div className="mx-auto w-full max-w-[720px] flex flex-col gap-4 md:gap-6">
                <div className="grid grid-cols-2 gap-4 md:gap-6">
                  {[...Array(2)].map((_, i) => (
                    <div key={`cat-top-${i}`} className="item w-full">
                      <div className="w-full aspect-square relative rounded-2xl bg-white flex justify-center items-center border">
                        <div className="w-[80px] h-[80px] bg-gray-200 rounded animate-pulse"></div>
                      </div>
                      <div className="w-20 h-4 bg-gray-200 rounded animate-pulse mx-auto mt-4"></div>
                    </div>
                  ))}
                </div>
                <div className="grid grid-cols-2 gap-4 md:gap-6">
                  {[...Array(2)].map((_, i) => (
                    <div key={`cat-bottom-${i}`} className="item w-full">
                      <div className="w-full aspect-square relative rounded-2xl bg-white flex justify-center items-center border">
                        <div className="w-[80px] h-[80px] bg-gray-200 rounded animate-pulse"></div>
                      </div>
                      <div className="w-20 h-4 bg-gray-200 rounded animate-pulse mx-auto mt-4"></div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>

          {/* Popular Category Section Skeleton */}
          <div className="section-style-one category-products md:mb-[60px] mb-[30px]">
            <div className="section-wrapper w-full">
              <div className="container-x mx-auto">
                <div className="section-title flex justify-between items-center mb-5">
                  <div className="w-48 h-8 bg-gray-200 rounded animate-pulse"></div>
                  <div className="w-24 h-6 bg-gray-200 rounded animate-pulse"></div>
                </div>
                <div className="products-section w-full">
                  <div className="grid grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 xl:gap-[30px] gap-2.5">
                    {/* Category Sidebar - Hidden on smaller screens */}
                    <div className="category-card hidden xl:block w-full">
                      <div className="w-full h-64 bg-gray-200 rounded animate-pulse mb-4"></div>
                      <div className="space-y-2">
                        {[...Array(6)].map((_, i) => (
                          <div
                            key={i}
                            className="w-full h-6 bg-gray-200 rounded animate-pulse"
                          ></div>
                        ))}
                      </div>
                    </div>

                    {/* Products Grid - 3 items */}
                    {[...Array(3)].map((_, i) => (
                      <div key={i} className="item">
                        <div className="border rounded p-4">
                          <div className="w-full h-48 bg-gray-200 rounded animate-pulse mb-3"></div>
                          <div className="w-3/4 h-4 bg-gray-200 rounded animate-pulse mb-2"></div>
                          <div className="w-1/2 h-4 bg-gray-200 rounded animate-pulse mb-2"></div>
                          <div className="w-1/3 h-6 bg-gray-200 rounded animate-pulse"></div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Brand Section Skeleton */}
          <div className="brand-section-wrapper w-full md:mb-[60px] mb-[30px]">
            <div className="container-x mx-auto">
              <div className="section-title flex justify-between items-center mb-5">
                <div className="w-48 h-8 bg-gray-200 rounded animate-pulse"></div>
              </div>
              <div className="grid lg:grid-cols-6 sm:grid-cols-4 grid-cols-2">
                {[...Array(6)].map((_, i) => (
                  <div key={i} className="item">
                    <div className="w-full h-[130px] p-[30px] bg-gray-200 border border-primarygray rounded animate-pulse"></div>
                  </div>
                ))}
              </div>
            </div>
          </div>

          {/* Campaign Countdown Skeleton */}
          <div className="campaign-section w-full lg:h-[460px] md:mb-[60px] mb-[30px]">
            <div className="container-x mx-auto h-full">
              <div className="lg:flex xl:space-x-[30px] lg:space-x-5 rtl:space-x-reverse items-center h-full">
                {/* Campaign Left Side */}
                <div className="campaign-countdown lg:w-1/2 h-[300px] sm:h-[400px] lg:h-full w-full mb-5 lg:mb-0 bg-gray-200 rounded animate-pulse">
                  <div className="w-full xl:p-12 p-5 h-full flex flex-col justify-between">
                    {/* Countdown */}
                    <div className="countdown-wrapper w-full flex lg:justify-between justify-evenly lg:mb-10 mb-2">
                      {[...Array(4)].map((_, i) => (
                        <div key={i} className="countdown-item">
                          <div className="countdown-number sm:w-[100px] sm:h-[100px] w-[50px] h-[50px] rounded-full bg-white animate-pulse"></div>
                          <div className="w-12 h-4 bg-gray-300 rounded animate-pulse mx-auto mt-2"></div>
                        </div>
                      ))}
                    </div>
                    {/* Title */}
                    <div className="w-48 h-8 bg-gray-300 rounded animate-pulse mb-4"></div>
                    {/* Button */}
                    <div className="w-32 h-12 bg-gray-300 rounded animate-pulse"></div>
                  </div>
                </div>

                {/* Download App Right Side */}
                <div className="download-app flex-1 lg:h-full h-[430px] xl:p-12 p-5 bg-gray-200 rounded animate-pulse">
                  <div className="flex flex-col h-full justify-between">
                    <div>
                      <div className="w-24 h-4 bg-gray-300 rounded animate-pulse mb-3"></div>
                      <div className="w-64 h-8 bg-gray-300 rounded animate-pulse mb-8"></div>
                      <div className="flex space-x-5 rtl:space-x-reverse items-center">
                        <div className="w-[170px] h-[60px] bg-white rounded animate-pulse"></div>
                        <div className="w-[170px] h-[60px] bg-white rounded animate-pulse"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Top Rated Products Skeleton */}
          <div className="top-selling-product md:mb-[60px] mb-[30px]">
            <div className="section-wrapper w-full">
              <div className="container-x mx-auto">
                <div className="section-title flex justify-between items-center mb-5">
                  <div className="w-48 h-8 bg-gray-200 rounded animate-pulse"></div>
                  <div className="w-24 h-6 bg-gray-200 rounded animate-pulse"></div>
                </div>
                <div className="section-content w-full grid sm:grid-cols-2 grid-cols-1 xl:gap-[30px] gap-5">
                  {[...Array(4)].map((_, i) => (
                    <div key={i} className="item w-full">
                      <div className="border rounded p-4 flex space-x-4">
                        <div className="w-24 h-24 bg-gray-200 rounded animate-pulse"></div>
                        <div className="flex-1">
                          <div className="w-3/4 h-4 bg-gray-200 rounded animate-pulse mb-2"></div>
                          <div className="w-1/2 h-4 bg-gray-200 rounded animate-pulse mb-2"></div>
                          <div className="w-1/3 h-6 bg-gray-200 rounded animate-pulse"></div>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>

          {/* Best Sellers Skeleton - Only for multivendor */}
          <div className="best-sellers-section md:mb-[60px] mb-[30px]">
            <div className="section-wrapper w-full">
              <div className="container-x mx-auto">
                <div className="section-title flex justify-between items-center mb-5">
                  <div className="w-48 h-8 bg-gray-200 rounded animate-pulse"></div>
                  <div className="w-24 h-6 bg-gray-200 rounded animate-pulse"></div>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:gap-[30px] gap-5">
                  {[...Array(4)].map((_, i) => (
                    <div key={i} className="border rounded p-4 text-center">
                      <div className="w-20 h-20 bg-gray-200 rounded-full animate-pulse mx-auto mb-3"></div>
                      <div className="w-3/4 h-4 bg-gray-200 rounded animate-pulse mx-auto mb-2"></div>
                      <div className="w-1/2 h-4 bg-gray-200 rounded animate-pulse mx-auto"></div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>

          {/* Two Column Ads Skeleton */}
          <div className="two-column-ads md:mb-[60px] mb-[30px]">
            <div className="container-x mx-auto">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="w-full h-48 bg-gray-200 rounded animate-pulse"></div>
                <div className="w-full h-48 bg-gray-200 rounded animate-pulse"></div>
              </div>
            </div>
          </div>

          {/* Featured Products Skeleton - Same as Popular Category */}
          <div className="section-style-one category-products md:mb-[60px] mb-[30px]">
            <div className="section-wrapper w-full">
              <div className="container-x mx-auto">
                <div className="section-title flex justify-between items-center mb-5">
                  <div className="w-48 h-8 bg-gray-200 rounded animate-pulse"></div>
                  <div className="w-24 h-6 bg-gray-200 rounded animate-pulse"></div>
                </div>
                <div className="products-section w-full">
                  <div className="grid grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 xl:gap-[30px] gap-2.5">
                    {/* Category Sidebar */}
                    <div className="category-card hidden xl:block w-full">
                      <div className="w-full h-64 bg-gray-200 rounded animate-pulse mb-4"></div>
                      <div className="space-y-2">
                        {[...Array(6)].map((_, i) => (
                          <div
                            key={i}
                            className="w-full h-6 bg-gray-200 rounded animate-pulse"
                          ></div>
                        ))}
                      </div>
                    </div>

                    {/* Products Grid */}
                    {[...Array(3)].map((_, i) => (
                      <div key={i} className="item">
                        <div className="border rounded p-4">
                          <div className="w-full h-48 bg-gray-200 rounded animate-pulse mb-3"></div>
                          <div className="w-3/4 h-4 bg-gray-200 rounded animate-pulse mb-2"></div>
                          <div className="w-1/2 h-4 bg-gray-200 rounded animate-pulse mb-2"></div>
                          <div className="w-1/3 h-6 bg-gray-200 rounded animate-pulse"></div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Single Banner Skeleton */}
          <div className="single-banner md:mb-[60px] mb-[30px]">
            <div className="container-x mx-auto">
              <div className="w-full h-32 bg-gray-200 rounded animate-pulse"></div>
            </div>
          </div>

          {/* New Arrivals Skeleton */}
          <div className="new-products md:mb-[60px] mb-[30px]">
            <div className="section-wrapper w-full">
              <div className="container-x mx-auto">
                <div className="section-title flex justify-between items-center mb-5">
                  <div className="w-48 h-8 bg-gray-200 rounded animate-pulse"></div>
                  <div className="w-24 h-6 bg-gray-200 rounded animate-pulse"></div>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:gap-[30px] gap-5">
                  {[...Array(16)].map((_, i) => (
                    <div key={i} className="border rounded p-4">
                      <div className="w-full h-48 bg-gray-200 rounded animate-pulse mb-3"></div>
                      <div className="w-3/4 h-4 bg-gray-200 rounded animate-pulse mb-2"></div>
                      <div className="w-1/2 h-4 bg-gray-200 rounded animate-pulse mb-2"></div>
                      <div className="w-1/3 h-6 bg-gray-200 rounded animate-pulse"></div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>

          {/* Single Banner Two Skeleton */}
          <div className="w-full text-white md:mb-[60px] mb-[30px]">
            <div className="container-x mx-auto">
              <div className="w-full h-32 bg-gray-200 rounded animate-pulse"></div>
            </div>
          </div>

          {/* Best Products Skeleton */}
          <div className="category-products md:mb-[60px] mb-[30px]">
            <div className="section-wrapper w-full">
              <div className="container-x mx-auto">
                <div className="section-title flex justify-between items-center mb-5">
                  <div className="w-48 h-8 bg-gray-200 rounded animate-pulse"></div>
                  <div className="w-24 h-6 bg-gray-200 rounded animate-pulse"></div>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:gap-[30px] gap-5">
                  {[...Array(8)].map((_, i) => (
                    <div key={i} className="border rounded p-4">
                      <div className="w-full h-48 bg-gray-200 rounded animate-pulse mb-3"></div>
                      <div className="w-3/4 h-4 bg-gray-200 rounded animate-pulse mb-2"></div>
                      <div className="w-1/2 h-4 bg-gray-200 rounded animate-pulse mb-2"></div>
                      <div className="w-1/3 h-6 bg-gray-200 rounded animate-pulse"></div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>

      {/* Footer Skeleton */}
      <footer className="bg-gray-900 text-white py-12">
        <div className="container-x mx-auto">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
            {[...Array(4)].map((_, i) => (
              <div key={i}>
                <div className="w-32 h-6 bg-gray-700 rounded animate-pulse mb-4"></div>
                <div className="space-y-2">
                  {[...Array(5)].map((_, j) => (
                    <div
                      key={j}
                      className="w-24 h-4 bg-gray-700 rounded animate-pulse"
                    ></div>
                  ))}
                </div>
              </div>
            ))}
          </div>

          {/* Newsletter Skeleton */}
          <div className="mt-8 pt-8 border-t border-gray-700">
            <div className="w-48 h-6 bg-gray-700 rounded animate-pulse mb-4"></div>
            <div className="flex">
              <div className="flex-1 h-12 bg-gray-700 rounded-l animate-pulse"></div>
              <div className="w-24 h-12 bg-gray-600 rounded-r animate-pulse"></div>
            </div>
          </div>

          {/* Copyright Skeleton */}
          <div className="mt-8 pt-8 border-t border-gray-700">
            <div className="w-64 h-4 bg-gray-700 rounded animate-pulse mx-auto"></div>
          </div>
        </div>
      </footer>
    </div>
  );
}
