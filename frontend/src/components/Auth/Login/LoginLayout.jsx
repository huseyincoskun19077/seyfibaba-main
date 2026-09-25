function LoginLayout({ children, scrollable = false }) {
  return (
    <div className="w-full min-h-[50vh] bg-[#f4f7f9]">
      <div
        className={
          scrollable
            ? "container-x mx-auto px-4 py-6 pb-28 lg:py-10 lg:pb-10"
            : "container-x mx-auto px-4 py-8 md:py-10"
        }
      >
        <div className="mx-auto max-w-md">
          <div className="rounded-2xl border border-[#04334a]/10 bg-white p-5 shadow-sm sm:p-8">
            {children}
          </div>
        </div>
      </div>
    </div>
  );
}

export default LoginLayout;
