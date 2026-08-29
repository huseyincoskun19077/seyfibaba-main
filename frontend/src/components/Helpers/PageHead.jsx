import React from "react";
function PageHead(props) {
  const { title, metaDes } = props;
  return (
    <head>
      <title>{title}</title>
      <meta name="description" content={metaDes} />
    </head>
  );
}

export default PageHead;
