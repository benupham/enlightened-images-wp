import React, { useEffect, useState } from "react"
import ReactDOM from "react-dom"
import Dashboard from "./Dashboard"
import Settings from "./Settings"
import { getCroppedSizes } from "./helper"

const App = (props) => {
  const [notice, setNotice] = useState([])
  const { urls, nonce, imageSizes } = window.smart_image_crop_ajax
  const croppedSizes = getCroppedSizes(imageSizes)
  // [noticeType, setNoticeType] = useState("")

  useEffect(() => {
    window.scrollTo(0, 0)
  }, [notice])

  return (
    <>
      <h1>Smart Image Crop AI</h1>
      {notice.length > 0 && <Notice notice={notice} />}
      <Settings nonce={nonce} urls={urls} croppedSizes={croppedSizes} setNotice={setNotice} />
      <Dashboard
        urls={urls}
        nonce={nonce}
        imageSizes={imageSizes}
        croppedSizes={croppedSizes}
        setNotice={setNotice}
      />
    </>
  )
}

const Notice = ({ notice }) => {
  const [message, type] = notice
  const classList = type === "success" ? "notice notice-success" : "error settings-error"
  return (
    <div className={classList}>
      <p>{message}</p>
    </div>
  )
}

const dashboardContainer = document.getElementById("smart_image_crop_dashboard")

if (dashboardContainer) {
  ReactDOM.render(<App />, dashboardContainer)
}
