import React, { useEffect, useState } from "react"
import ReactDOM from "react-dom"
import Settings from "./Settings"
import "./index.css"
import "@fontsource/alata"
import { nonce, urls } from "./api"

const App = (props) => {
  const [notice, setNotice] = useState([])
  const [estimate, setEstimate] = useState()
  const [count, setCount] = useState()
  
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

  if (process.env.NODE_ENV === "production") console.log = function no_console() {}

  return (
    <>
      <h1>
        <span className="enlightened">Enlightened Images</span> Image Alt Text Generator
      </h1>
      <div className="wrap elim">
        {notice.length > 0 && <Notice notice={notice} />}
        <Settings setNotice={setNotice} estimate={estimate} count={count} />
      </div>
    </>
  )
}

// TODO: useContext to make notice, notice type available globally
const Notice = ({ notice }) => {
  const [message, type] = notice
  const classList = type === "success" ? "notice notice-success" : "error settings-error"
  return (
    <div className={classList}>
      <p>{message}</p>
    </div>
  )
}

const dashboardContainer = document.getElementById("elim-dashboard")

if (dashboardContainer) {
  ReactDOM.render(<App />, dashboardContainer)
}
