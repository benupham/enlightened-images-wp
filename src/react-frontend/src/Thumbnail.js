import React from "react"
import "./thumbnail.css"

const Thumbnail = (props) => {
  const thumb = props.thumb
  const id = thumb.size + "_" + thumb.attachment.id
  const checked = thumb.isChecked ? "checked" : ""
  const loading = thumb.isLoading ? "loading" : ""
  const smartcrop = thumb.smartcrop ? "smartcrop" : ""

  return (
    <div key={id} className={`thumb ${checked} ${loading} ${smartcrop}`}>
      <div className="lds-ring">
        <div></div>
        <div></div>
        <div></div>
        <div></div>
      </div>
      <input
        type="checkbox"
        name={id}
        id={id}
        checked={thumb.isChecked}
        onChange={(e) => props.handleChange(e, props.index)}
      />
      <label htmlFor={id}>
        <img src={thumb.url + "?cache=" + thumb.cacheId} alt={id} />
      </label>
    </div>
  )
}

export default Thumbnail
