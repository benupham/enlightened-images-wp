import React, { useEffect, useState } from "react"
import ReactDOM from "react-dom"
import Dashboard from "./Dashboard"
import Settings from "./Settings"

const App = (props) => {
  const [notice, setNotice] = useState([])
  const { urls, nonce, imageSizes } = window.smartimagesearch_ajax
  useEffect(() => {
    window.scrollTo(0, 0)
  }, [notice])

  return (
    <>
      <h1>Smart Image AI Alt Text Generator</h1>
      {notice.length > 0 && <Notice notice={notice} />}
      <Settings nonce={nonce} urls={urls} setNotice={setNotice} />
      <Dashboard urls={urls} nonce={nonce} setNotice={setNotice} />
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

const dashboardContainer = document.getElementById("smartimagesearch_dashboard")

if (dashboardContainer) {
  ReactDOM.render(<App />, dashboardContainer)
}
