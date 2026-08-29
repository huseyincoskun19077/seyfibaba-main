"use client";

import { Provider } from "react-redux";
import store, { persistor } from "./store";
import { useState, useEffect } from "react";

// auth
import auth from "@/utils/auth";
import MessageContext from "@/components/Contexts/MessageContext";
import LoginContext from "@/components/Contexts/LoginContext";
import { FlyingCartProvider } from "@/components/Contexts/FlyingCartContext";

export function Providers({ children }) {
  // Bootstrap redux-persist without blocking render
  useEffect(() => {
    persistor.persist();
  }, []);

  // auth login popup
  const [loginPopup, setLoginPopup] = useState(false);
  const handlerPopup = (value) => {
    setLoginPopup(value);
  };
  const [toggleMessage, setToggleMessage] = useState(false);
  const [addNewSeller, setNewSeller] = useState(null);
  const toggleHandler = (value) => {
    if (auth()) {
      if (value) {
        setNewSeller(value);
      } else {
        setNewSeller(null);
      }
      setToggleMessage(!toggleMessage);
    }
  };

  return (
    <Provider store={store}>
      <MessageContext.Provider
        value={{
          toggle: toggleMessage,
          toggleHandler: toggleHandler,
          newSeller: addNewSeller,
        }}
      >
        <LoginContext.Provider
          value={{ loginPopup: loginPopup, handlerPopup: handlerPopup }}
        >
          <FlyingCartProvider>{children}</FlyingCartProvider>
        </LoginContext.Provider>
      </MessageContext.Provider>
    </Provider>
  );
}
