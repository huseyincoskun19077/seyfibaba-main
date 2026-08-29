import React from "react";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { library } from "@fortawesome/fontawesome-svg-core";

// Import only the specific icons we actually use
import {
  faShoppingCart,
  faUser,
  faHome,
  faSearch,
  faHeart,
  faStar,
  faPhone,
  faEnvelope,
  faMapMarkerAlt,
  faTruck,
  faCreditCard,
  faShieldAlt,
  faClock,
  faCheck,
  faTimes,
  faPlus,
  faMinus,
  faArrowRight,
  faArrowLeft,
  faChevronRight,
  faChevronLeft,
  faBars,
  faEdit,
  faTrash,
  faCog,
  faTachometerAlt,
  faShoppingBag,
  faHeadset,
  faComment,
  faSignOutAlt,
  faLock,
  faUsers,
  faQuestionCircle,
} from "@fortawesome/free-solid-svg-icons";
import {
  faFacebookF,
  faInstagram,
  faXTwitter,
  faLinkedinIn,
  faYoutube,
} from "@fortawesome/free-brands-svg-icons";

// Add only the icons we need
library.add(
  faShoppingCart, faUser, faHome, faSearch, faHeart, faStar,
  faPhone, faEnvelope, faMapMarkerAlt, faTruck, faCreditCard,
  faShieldAlt, faClock, faCheck, faTimes, faPlus, faMinus,
  faArrowRight, faArrowLeft, faChevronRight, faChevronLeft,
  faBars, faEdit, faTrash, faCog, faTachometerAlt, faShoppingBag,
  faHeadset, faComment, faSignOutAlt, faLock, faUsers, faQuestionCircle,
  faFacebookF, faInstagram, faXTwitter, faLinkedinIn, faYoutube
);

// Common icon mappings for fallback
const iconMappings = {
  "shopping-cart": "fas fa-shopping-cart",
  cart: "fas fa-shopping-cart",
  user: "fas fa-user",
  home: "fas fa-home",
  search: "fas fa-search",
  heart: "fas fa-heart",
  star: "fas fa-star",
  phone: "fas fa-phone",
  envelope: "fas fa-envelope",
  "map-marker": "fas fa-map-marker-alt",
  truck: "fas fa-truck",
  "credit-card": "fas fa-credit-card",
  shield: "fas fa-shield-alt",
  clock: "fas fa-clock",
  check: "fas fa-check",
  times: "fas fa-times",
  plus: "fas fa-plus",
  minus: "fas fa-minus",
  "arrow-right": "fas fa-arrow-right",
  "arrow-left": "fas fa-arrow-left",
  "chevron-right": "fas fa-chevron-right",
  "chevron-left": "fas fa-chevron-left",
  menu: "fas fa-bars",
  close: "fas fa-times",
  edit: "fas fa-edit",
  delete: "fas fa-trash",
  settings: "fas fa-cog",
  dashboard: "fas fa-tachometer-alt",
  orders: "fas fa-shopping-bag",
  wishlist: "fas fa-heart",
  address: "fas fa-map-marker-alt",
  payment: "fas fa-credit-card",
  support: "fas fa-headset",
  message: "fas fa-comment",
  logout: "fas fa-sign-out-alt",
  password: "fas fa-lock",
  people: "fas fa-users",
  review: "fas fa-star",
  love: "fas fa-heart",
};

function FontAwesomeCom({ icon, size, className }) {
  if (!icon) return null;

  try {
    let prefix, iconName;

    if (typeof icon === "string") {
      if (icon.includes(" ")) {
        const text = icon.split(" ");
        if (text.length < 2) return null;
        prefix = text[0];
        iconName = text[1].replace("fa-", "");
      } else {
        const mappedIcon = iconMappings[icon.toLowerCase()];
        if (mappedIcon) {
          const text = mappedIcon.split(" ");
          prefix = text[0];
          iconName = text[1].replace("fa-", "");
        } else {
          prefix = "fas";
          iconName = icon.replace("fa-", "");
        }
      }
    } else if (Array.isArray(icon)) {
      [prefix, iconName] = icon;
    } else {
      return null;
    }

    const iconArray = [prefix, iconName];
    const iconExists =
      library.definitions[prefix] && library.definitions[prefix][iconName];

    if (!iconExists) {
      return (
        <FontAwesomeIcon
          className={className}
          icon={["fas", "question-circle"]}
          size={size}
        />
      );
    }

    return (
      <FontAwesomeIcon className={className} icon={iconArray} size={size} />
    );
  } catch {
    return null;
  }
}

export default FontAwesomeCom;
