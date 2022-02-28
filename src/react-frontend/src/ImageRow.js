import React, { useRef } from "react"
import ContentEditable from "react-contenteditable"
import { updateAltText } from "./api"

export const ImageRow = ({ image, setImages }) => {
  // console.log("image", image)
  let status = "OK"
  let metaData = image.meta_data

  const altText = useRef(image.alt_text?.smartimage)

  const handleChange = (evt) => {
    altText.current = evt.target.value
  }

  const handleKeyDown = (evt) => {
    if (evt.key === "Enter" || evt.key === "Tab") {
      evt.preventDefault()
      evt.currentTarget.blur()
    }
  }

  const handleBlur = async () => {
    const result = await updateAltText(image.id, altText.current)
    setImages((prev) => {
      const newImages = [...prev].map((img) => {
        return img.id === image.id
          ? { ...img, alt_text: { smartimage: result, existing: result } }
          : img
      })
      return newImages
    })
  }

  const error = image.error ? true : false
  if (error) {
    status = "Error"
    metaData = image.error.errors[Object.keys(image.error.errors)[0]]
  }

  return (
    <tr className="image-row">
      <td className="thumbnail">
        <a className="image-link" target="_blank" rel="noreferrer" href={image.attachmentURL}>
          <img width="75" height="75" src={image.thumbnail} alt={image.alt_text?.smartimage} />
        </a>
      </td>
      <td className="filename">
        <a className="image-link" target="_blank" rel="noreferrer" href={image.attachmentURL}>
          {image.file}
        </a>
      </td>
      <td className="alt-text">
        <ContentEditable
          className="editable"
          html={image.alt_text?.smartimage}
          onBlur={handleBlur}
          onChange={handleChange}
          onKeyDown={handleKeyDown}
        />

        {image.alt_text?.existing != image.alt_text?.smartimage &&
          image.alt_text.existing.length > 0 && (
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
