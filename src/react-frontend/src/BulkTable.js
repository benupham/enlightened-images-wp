import React from "react"
import { ImageRow } from "./ImageRow"

export const BulkTable = ({ images }) => {
  return (
    <table className="sisa_bulk_table">
      <thead>
        <tr>
          <th>Image</th>
          <th>File</th>
          <th>Alt Text</th>
          <th>Smart Meta</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        {images &&
          images.map((image, index) => {
            return <ImageRow image={image} key={index} />
          })}
      </tbody>
    </table>
  )
}
