import React from "react"

export const ImageRow = ({ image }) => {
  // console.log("image", image)
  return (
    <tr>
      <td>
        <img width="75" height="75" src={image.thumbnail} alt={image.alt_text.smartimage} />
      </td>
      <td>{image.alt_text.smartimage}</td>
      <td>{image.smartsearch_meta}</td>
      <td></td>
    </tr>
  )
}
