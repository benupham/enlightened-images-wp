import React from "react"

export const ImageRow = ({ image }) => {
  // console.log("image", image)
  const status = image.error ? "Error" : "OK"
  return (
    <tr>
      <td className="thumbnail">
        <img width="75" height="75" src={image.thumbnail} alt={image.alt_text?.smartimage} />
      </td>
      <td className="filename">{image.file}</td>
      <td className="alt-text">&#8220;{image.alt_text?.smartimage}&#8221;</td>
      <td className="sisa-meta">{image.meta_data}</td>
      <td className={`status ${status}`}>{status}</td>
    </tr>
  )
}
