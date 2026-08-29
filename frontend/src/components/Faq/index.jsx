"use client";
import Accodion from "../Helpers/Accodion";
import PageTitle from "../Helpers/PageTitle";
import ServeLangItem from "../Helpers/ServeLangItem";
import ContactForm from "../Contact/ContactForm";

function TitleShape() {
  return (
    <svg
      width="354"
      height="30"
      viewBox="0 0 354 30"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
    >
      <path
        d="M1 28.8027C17.6508 20.3626 63.9476 8.17089 113.509 17.8802C166.729 28.3062 341.329 42.704 353 1"
        stroke="#FCBF49"
        strokeWidth="2"
        strokeLinecap="round"
      />
    </svg>
  );
}

export default function Faq({ datas }) {
  const { faqs } = datas;

  return (
    <>
      <div className="faq-page-wrapper w-full mb-10">
        <div className="page-title w-full">
          <PageTitle
            title={ServeLangItem()?.Frequently_asked_questions}
            breadcrumb={[
              { name: ServeLangItem()?.home, path: "/" },
              { name: ServeLangItem()?.FAQ, path: "/faq" },
            ]}
          />
        </div>
      </div>
      <div className="contact-wrapper w-full mb-10">
        <div className="container-x mx-auto">
          <div className="main-wrapper w-full lg:flex lg:space-x-[30px] rtl:space-x-reverse">
            <div className="lg:w-1/2 w-full mb-10 lg:mb-0">
              <h2 className="text-qblack font-bold text-[22px] mb-4">
                {ServeLangItem()?.Frequently_asked_questions}
              </h2>
              <div className="flex flex-col space-y-7 justify-between">
                {faqs.map((faq) => (
                  <Accodion
                    key={faq.id}
                    title={faq.question}
                    des={faq.answer}
                  />
                ))}
              </div>
            </div>
            <div className="flex-1">
              <div className="bg-white sm:p-10 p-5">
                <div className="title flex flex-col items-center">
                  <h2 className="lg:text-[34px] text-xl font-bold text-qblack">
                    {ServeLangItem()?.Have_Any_Qustion}
                  </h2>
                  <span className="-mt-3 block">
                    <TitleShape />
                  </span>
                </div>
                {/* Contact Form */}
                <ContactForm />
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
