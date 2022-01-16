import React, { useEffect, useState, useRef, useCallback } from "react"
import "./dashboard.css"
import { ProgressBar } from "./ProgressBar"
import { ImageRow } from "./ImageRow"

const Dashboard = ({ urls, nonce, setNotice }) => {
  const [images, setImages] = useState([])
  const [errorMessage, setErrorMessage] = useState("")
  const [bulkRunning, setBulkRunning] = useState(false)
  const [stats, setStats] = useState({})
  const bulkTotal = useRef()
  const bulkRemaining = useRef()
  const bulkErrors = useRef()
  const [pause, setPause] = useState(false)
  const [startTime, setStartTime] = useState()

  const prepBulkAnnotate = async () => {
    let response = null
    let data = {}
    try {
      response = await fetch(urls.proxy, {
        headers: new Headers({ "X-WP-Nonce": nonce })
      })
      data = await response.json()
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

    while (bulkRemaining.current > 0 && pause === false) {
      try {
        response = await fetch(`${urls.proxy}?start=${startTime}`, {
          headers: new Headers({ "X-WP-Nonce": nonce })
        })
        data = await response.json()
        console.log("bulk response", data)
      } catch (error) {
        console.log("bulk error", error)
        setErrorMessage(error)
        break
      }
      bulkRemaining.current = data.count
      bulkErrors.current = data.errors

      setStats((prev) => ({ ...prev, errors: data.errors, remaining: data.count }))
      setImages((prev) => [...prev, data.image_data])
      if (data.errors === data.image_data.length) {
        setErrorMessage("Stopping bulk annotation as the most recent batch was all errors.")
        setPause(true)
      }
    }
    setBulkRunning(false)
  }, [pause, urls, nonce, startTime])

  useEffect(() => {
    prepBulkAnnotate()
  }, [])

  return (
    <div className="sisa_wrapper wrap">
      <button onClick={bulkAnnotate}>Bulk Annotation</button>
      <ProgressBar stats={stats} />
      <div className={bulkRunning === true ? "bulk-running" : ""}>
        {errorMessage && (
          <div className="error settings-error">
            <p>{errorMessage}</p>
          </div>
        )}{" "}
        <table className="sisa_bulk_table">
          <thead>
            <tr>
              <th>Image</th>
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
      </div>
      <div>
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
