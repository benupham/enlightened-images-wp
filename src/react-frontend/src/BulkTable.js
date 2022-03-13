import React from "react"
import { ImageRow } from "./ImageRow"
import "./bulktable.css"

export const BulkTable = ({ images, setImages }) => {
  return (
    <table className="bulk-table">
      <thead>
        <tr>
          <th className="thumbnail">Image</th>
          <th className="filename">File</th>
          <th className="alt-text">
            Alt Text <br />
            <span>click on text to edit</span>
          </th>
          <th className="sisa-meta">Annotation Metadata</th>
          <th className="status">Status</th>
        </tr>
      </thead>
      <tbody>
        {images &&
          images.map((image, index) => {
            return <ImageRow image={image} key={index} setImages={setImages} />
          })}
      </tbody>
    </table>
  )
}
