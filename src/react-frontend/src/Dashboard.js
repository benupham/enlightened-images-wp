import React, { useEffect, useState, useRef, useCallback } from "react"
import "./dashboard.css"
import { ProgressBar } from "./ProgressBar"
import { ImageCard } from "./ImageCard"
import { BulkTable } from "./BulkTable"
import { msToTime } from "./helper"

export const Dashboard = ({ urls, nonce, options }) => {
  const [images, setImages] = useState([])
  const [errorMessage, setErrorMessage] = useState("")
  const [bulkRunning, setBulkRunning] = useState(false)
  const [complete, setComplete] = useState(false)
  const [stats, setStats] = useState({})
  const bulkTotal = useRef()
  const bulkRemaining = useRef()
  const bulkErrors = useRef()
  const pause = useRef(false)
  const [paused, setPaused] = useState(false)
  const [startTime, setStartTime] = useState()
  const [estimate, setEstimate] = useState()

  const prepBulkAnnotate = async () => {
    let response = null
    let data = {}
    // console.log(urls)
    try {
      response = await fetch(urls.proxy, {
        headers: new Headers({ "X-WP-Nonce": nonce })
      })
      const json = await response.json()
      data = json.body
      console.log(data)
    } catch (error) {
      setErrorMessage(error)
    }
    setStats({ total: data.count, errors: 0, remaining: data.count })
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
        const start = Date.now()

        console.log(`${urls.proxy}?start=${startTime}`)
        response = await fetch(`${urls.proxy}?start=${startTime}`, {
          headers: new Headers({ "X-WP-Nonce": nonce, "Cache-Control": "no-cache" })
        })
        const json = await response.json()
        data = json.body
        console.log("bulk response", data)

        const end = Date.now()
        const elapsed = end - start
        const msEst = elapsed * Math.ceil(data.count / data.image_data.length)
        const hourEst = msToTime(msEst)
        setEstimate(hourEst)
      } catch (error) {
        console.log("bulk error", error)
        // setErrorMessage(error)
        break
      }
      bulkRemaining.current = data.count
      bulkErrors.current = data.errors

      setStats((prev) => ({
        ...prev,
        errors: prev.errors + data.errors,
        remaining: data.count
      }))
      setImages((prev) => [...prev, ...data.image_data])

      if (data.errors === data.image_data.length) {
        const errors = data.image_data[0].gcv_data.errors
        const errorMsg = errors[Object.keys(errors)[0]]

        setErrorMessage(
          `Stopping bulk annotation as the most recent batch was all errors. First error: ${errorMsg}`
        )
        pause.current = true
      }
    }
    setBulkRunning(false)

    if (bulkRemaining.current === 0) setComplete(true)
  }, [urls, nonce, startTime])

  useEffect(() => {
    prepBulkAnnotate()
  }, [])

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
    <div className="sisa-wrapper">
      <h3>Images missing alt text: {stats.remaining}</h3>
      {estimate && <h3>Time remaining: {estimate}</h3>}

      {!bulkRunning && !paused && stats.remaining > 0 && (
        <button className="button button-primary" onClick={handleBulkAnnotate}>
          Start Bulk
        </button>
      )}
      {(paused || bulkRunning) && (
        <button className="button" onClick={handlePause}>
          {paused ? (bulkRunning ? "Stopping..." : "Resume") : "Stop"}
        </button>
      )}
      {stats.remaining === 0 && <h3>Complete!</h3>}

      <ProgressBar stats={stats} />
      <div className={bulkRunning === true ? "bulk-running sisa-bulk-wrap" : "sisa-bulk-wrap"}>
        {errorMessage && <div className="error settings-error">{errorMessage}</div>}{" "}
        {options.hasPro === 1 && images && <BulkTable images={images} />}
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
