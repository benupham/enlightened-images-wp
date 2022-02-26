import React, { useEffect, useState } from "react"
import ReactDOM from "react-dom"
import Settings from "./Settings"
import "./index.css"
import "@fontsource/alata"

const App = (props) => {
  const [notice, setNotice] = useState([])
  const [estimate, setEstimate] = useState()
  const [count, setCount] = useState()
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
        setCount(data.count)
      } catch (error) {
        console.log(error)
        setNotice(error)
      }
    }
    getEstimate()
  }, [])

  return (
    <>
      <h1>
        <span className="enlightened">EnlightenedImages</span> Alt Text and AI Image Annotation
      </h1>
      <div className="wrap sisa">
        {notice.length > 0 && <Notice notice={notice} />}
        <Settings
          nonce={nonce}
          urls={urls}
          setNotice={setNotice}
          estimate={estimate}
          count={count}
        />
      </div>
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

const dashboardContainer = document.getElementById("sisa-dashboard")

if (dashboardContainer) {
  ReactDOM.render(<App />, dashboardContainer)
}
