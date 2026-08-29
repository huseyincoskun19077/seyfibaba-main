import React from "react";

/**
 * CheckmarkIcon Component
 * A checkmark icon used for checkboxes and confirmation states
 * @param {Object} props - Component props
 * @param {string} props.className - Additional CSS classes
 * @param {string} props.width - Icon width
 * @param {string} props.height - Icon height
 * @param {string} props.fill - Icon fill color
 */
const CheckmarkIcon = ({
  className = "h-5 w-5",
  width = "20",
  height = "20",
  fill = "currentColor",
}) => {
  return (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      className={className}
      width={width}
      height={height}
      viewBox="0 0 20 20"
      fill={fill}
    >
      <path
        fillRule="evenodd"
        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
        clipRule="evenodd"
      />
    </svg>
  );
};

export default CheckmarkIcon;
