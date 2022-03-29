import React, { useEffect, useState, useRef, useCallback } from "react"
import "./dashboard.css"
import { ProgressBar } from "./ProgressBar"
import { ImageCard } from "./ImageCard"
import { BulkTable } from "./BulkTable"
import { msToTime } from "./helper"
import { nonce, urls } from "./api"

export const Dashboard = ({ options }) => {
  const [images, setImages] = useState([])
  const [errorMessage, setErrorMessage] = useState("")
  const [bulkRunning, setBulkRunning] = useState(false)
  const [stats, setStats] = useState({})
  const [paused, setPaused] = useState(false)
  const [startTime, setStartTime] = useState()
  const [estimate, setEstimate] = useState()

  const bulkTotal = useRef()
  const bulkRemaining = useRef()
  const bulkErrors = useRef()
  const pause = useRef(false)

  const prepBulkAnnotate = async () => {
    let response = null
    let data = {}

    try {
      response = await fetch(urls.proxy, {
        headers: new Headers({ "X-WP-Nonce": nonce, "Cache-Control": "no-cache" })
      })
      const json = await response.json()
      data = json.body
      console.log("prepbulk data", data)
    } catch (error) {
      setErrorMessage(error)
    }
    setStats({ total: data.count, errors: 0, remaining: data.count, credits: data.credits })
    setStartTime(data.start)
    bulkTotal.current = data.count
    bulkErrors.current = 0
    bulkRemaining.current = data.count
  }

  const bulkAnnotate = useCallback(async () => {
    setBulkRunning(true)
    let response = null
    let data = {}

    while (bulkRemaining.current > 0 && pause.current === false) {
      try {
        const startRun = Date.now()

        console.log(`${urls.proxy}?start=${startTime}`)
        response = await fetch(`${urls.proxy}?start=${startTime}`, {
          headers: new Headers({ "X-WP-Nonce": nonce, "Cache-Control": "no-cache" })
        })
        const json = await response.json()
        data = json.body
        console.log("bulk response", data)

        const endRun = Date.now()
        const elapsed = endRun - startRun
        const msEst = elapsed * Math.ceil(data.count / data.image_data.length)
        const hourEst = msToTime(msEst)
        setEstimate(hourEst)
      } catch (error) {
        console.log("bulk error", error)
        setErrorMessage(error)
        break
      }
      bulkRemaining.current = data.count
      bulkErrors.current = data.errors

      setStats((prev) => ({
        ...prev,
        errors: prev.errors + data.errors,
        remaining: data.count,
        credits: data.credits
      }))
      console.log(stats)
      setImages((prev) => [...prev, ...data.image_data])

      if (data.errors === data.image_data.length) {
        const errors = data.image_data[0].error.errors
        const errorMsg = errors[Object.keys(errors)[0]]

        setErrorMessage(
          `Stopping bulk annotation as the most recent batch was all errors. First error: ${errorMsg}`
        )
        pause.current = true
      }
    }
    setBulkRunning(false)
  }, [urls, nonce, startTime])

  useEffect(() => {
    prepBulkAnnotate()
  }, [options])

  const handleBulkAnnotate = (e) => {
    e.preventDefault()
    bulkAnnotate()
  }

  const handlePause = (e) => {
    e.preventDefault()
    if (pause.current === true) {
      pause.current = false
      setPaused(false)
      bulkAnnotate()
    } else {
      pause.current = true
      setPaused(true)
    }
  }

  return (
    <div className={options.isPro === 0 || options.hasPro === 0 ? `bulk wrap` : `bulk`}>
      <h3>Total images remaining to analyze: {stats.remaining ? stats.remaining : "loading..."}</h3>
      {options.isPro === 1 && (
        <>
          <h4 className="credits">
            Credits Remaining: {stats.credits ? stats.credits : "loading..."}{" "}
            <a
              href="https://enlightenedimageswp.com/my-account/"
              className="buy-credits"
              rel="noreferrer"
              target="_blank">
              Buy more credits
            </a>{" "}
          </h4>
        </>
      )}
      {!bulkRunning && !paused && stats.remaining > 0 && (
        <button className="button button-primary trigger-bulk" onClick={handleBulkAnnotate}>
          Start Bulk Annotation
        </button>
      )}
      {(paused || bulkRunning) && (
        <button className="button trigger-bulk" onClick={handlePause}>
          {paused ? (bulkRunning ? "Stopping..." : "Resume") : "Stop"}
        </button>
      )}
      {stats.remaining === 0 && <h3>Complete!</h3>}
      <h3>Estimated Time remaining: {estimate}</h3>

      <ProgressBar stats={stats} />
      <div className={bulkRunning === true ? "bulk-running bulk-table-wrap" : "bulk-table-wrap"}>
        {errorMessage && (
          <div className="error settings-error">
            <p>{errorMessage}</p>
          </div>
        )}{" "}
        {options.hasPro === 1 && images && <BulkTable images={images} setImages={setImages} />}
        {options.hasPro === 0 &&
          images &&
          images.map((image, index) => {
            return <ImageCard image={image} key={index} />
          })}
        {bulkRunning === true && (
          <div className="loading">
            <div className="lds-ring">
              <div></div>
              <div></div>
              <div></div>
              <div></div>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}

export default Dashboard
