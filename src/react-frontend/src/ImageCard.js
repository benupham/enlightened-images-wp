import React from "react"
import "./imagecard.css"

export const ImageCard = ({ image }) => {
  // console.log("image", image)
  return (
    <div className="image-card">
      <div className="thumb">
        <a href={image.attachmentURL} target="_blank" rel="noreferrer">
          <img width="125" height="125" src={image.thumbnail} alt={image.alt_text?.smartimage} />
        </a>
      </div>
      <div className="details">
        <div className="alt-text">
          <div className="alt-text">&#8220;{image.alt_text?.smartimage}&#8221;</div>
        </div>
        <div className="file">{image.file}</div>
      </div>
    </div>
  )
}
