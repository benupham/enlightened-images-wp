import React from "react"
import "./sidebar.css"

export const Sidebar = ({ estimate, count, isPro, hasPro }) => {
  return (
    <div className="sidebar">
      {isPro === 0 && count > 0 && typeof estimate === "number" && (
        <div className="estimate">
          <h3 className="title">Purchase an API Key</h3>
          <p>
            Generate alt text now for all <b>{count}</b> images missing it for only:
          </p>
          <span className="price">${estimate}</span>
          <a
            href={`https://enlightenedimageswp.com/checkout/?count=${count}`}
            target="_blank"
            rel="noreferrer"
            className="button-primary">
            Buy Now
          </a>
        </div>
      )}
      {hasPro === 0 && (
        <div className="pro-plugin">
          <h3 className="title">Upgrade to the Pro Version</h3>
          <p>
            Generate alt text on every image upload in the background and edit generated alt text
            from the bulk tool, plus access to all future features. And get rid of this annoying
            notice.
          </p>
          <span className="price">$5.99</span>
          <a
            href={`https://enlightenedimageswp.com/checkout/?count=${count}&pro=1`}
            target="_blank"
            rel="noreferrer"
            className="button-primary">
            Upgrade Now
          </a>
        </div>
      )}
    </div>
  )
}
