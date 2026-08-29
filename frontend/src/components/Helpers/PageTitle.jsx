import BreadcrumbCom from "../BreadcrumbCom";

export default function PageTitle({
  title,
  breadcrumb = [],
  headingAs = "h1",
}) {
  const HeadingTag = headingAs;

  return (
    <div className="page-title-wrapper bg-qyellowlow/10 w-full h-[173px] py-10">
      <div className="container-x mx-auto">
        <div className="mb-5">
          <BreadcrumbCom paths={breadcrumb} />
        </div>
        <div className="flex justify-center">
          <HeadingTag className="text-3xl font-semibold text-qblack">
            {title}
          </HeadingTag>
        </div>
      </div>
    </div>
  );
}
