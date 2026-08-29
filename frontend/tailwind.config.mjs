function withOpacity(variableName) {
  return ({ opacityValue }) => {
    if (opacityValue !== undefined) {
      return `rgba(var(${variableName}), ${opacityValue})`;
    } else {
      return `rgb(var(${variableName}))`;
    }
  };
}

// Create a named configuration object
const tailwindConfig = {
  content: ["./src/**/*.{js,ts,jsx,tsx,mdx}"],
  theme: {
    extend: {
      colors: {
        primarygray: "#f8f8f8",
        qblack: "rgb(var(--secondary-color))",
        qyellow: "rgb(var(--primary-color))",
        qyellowlow: withOpacity("--primary-color"),
        qred: "#EF262C",
        qgray: "#707070",
        qblacktext: "#1D1D1D",
        qgraytwo: "#666666",
        "qgray-border": "#EFEFEF",
        "qblue-white": "#CBECFF",
        "qh2-green": "#2D6F6D",
      },
      scale: {
        60: "0.6",
      },
    },
  },
  variants: {
    extend: {
      textColor: ["focus-within"],
      borderColor: ["last"],
      space: ["responsive", "direction"],
    },
  },
};

// Export the named configuration
export default tailwindConfig;
