import React from "react"
import "./sidebar.css"

export const Sidebar = ({ estimate, count, isPro, hasPro }) => {
  return (
    <div className="sidebar">
      {isPro === 0 && (
        <div className="estimate">
          <h3 className="title">Purchase an API Key</h3>
          <p>Generate missing alt text for all images for only:</p>
          <span className="price">${estimate}</span>
          <a
            href={`https://dev-smart-image-ai.pantheonsite.io/checkout/?count=${count}`}
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
            Generate alt text on every image upload in the background, add text, logo, and landmark
            recognition, and more.
          </p>
          <span className="price">$5.99</span>
          <a
            href={`https://dev-smart-image-ai.pantheonsite.io/checkout/?count=${count}&pro=1`}
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
