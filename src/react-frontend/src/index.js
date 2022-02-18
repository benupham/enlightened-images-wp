import React, { useEffect, useState } from "react"
import ReactDOM from "react-dom"
import Settings from "./Settings"

const App = (props) => {
  const [notice, setNotice] = useState([])
  const [estimate, setEstimate] = useState()
  const { urls, nonce } = window.smartimagesearch_ajax
  useEffect(() => {
    window.scrollTo(0, 0)
  }, [notice])

  useEffect(() => {
    let response = null
    let data = {}

    async function getEstimate() {
      try {
        response = await fetch(urls.proxy, {
          headers: new Headers({ "X-WP-Nonce": nonce })
        })
        const json = await response.json()
        data = json.body
        console.log(data)
        setEstimate(data.estimate)
      } catch (error) {
        console.log(error)
        setNotice(error)
      }
    }
    getEstimate()
  }, [])

  return (
    <>
      <h1>Smart Image AI Alt Text Generator</h1>
      {notice.length > 0 && <Notice notice={notice} />}
      <Settings nonce={nonce} urls={urls} setNotice={setNotice} estimate={estimate} />
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
