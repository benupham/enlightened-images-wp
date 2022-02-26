import React from "react"

export const ImageRow = ({ image }) => {
  // console.log("image", image)
  let status = "OK"
  let metaData = image.meta_data

  const error = image.error ? true : false
  if (error) {
    status = "Error"
    metaData = image.error.errors[Object.keys(image.error.errors)[0]]
  }
  return (
    <tr>
      <td className="thumbnail">
        <img width="75" height="75" src={image.thumbnail} alt={image.alt_text?.smartimage} />
      </td>
      <td className="filename">{image.file}</td>
      <td className="alt-text">
        &#8220;{image.alt_text?.smartimage}&#8221;{" "}
        {image.alt_text?.existing.length > 0 && (
          <>
            <br />
            <span className="existing-alt-text">{image.alt_text?.existing}</span>
          </>
        )}
      </td>
      <td className={`sisa-meta ${status}`}>{metaData}</td>
      <td className={`status ${status}`}>{status}</td>
    </tr>
  )
}
