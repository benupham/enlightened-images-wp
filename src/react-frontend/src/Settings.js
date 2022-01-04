import React, { useEffect, useMemo, useState } from "react"
import { Accordion } from "./Accordion"
import { checkApiKey } from "./api"

const Settings = ({ nonce, urls, croppedSizes, setNotice }) => {
  const [apiKey, setApiKey] = useState("")
  const [isSaving, setSaving] = useState(false)
  const [isGetting, setGetting] = useState(true)
  const [isOpen, setOpen] = useState(false)

  const updateSettings = async (event) => {
    event.preventDefault()
    setSaving(true)
    await fetch(urls.settings, {
      body: JSON.stringify({ apiKey }),
      method: "POST",
      headers: new Headers({
        "Content-Type": "application/json",
        "X-WP-Nonce": nonce
      })
    })

    // check if API key is valid
    try {
      const data = await checkApiKey(apiKey)
      setNotice(["API key saved and validated with Google API!", "success"])
    } catch (error) {
      if (error.message == "The request is missing a valid API key.") {
        error.message = "API key not valid. Please check your Google Cloud Vision account."
      }
      setNotice([`Key saved, but there was an error: ${error.message}`, "error"])
    }

    setSaving(false)
  }

  const getSettings = async () => {
    let json = null
    let elapsed = false
    setGetting(true)
    // display loading for a minimum amount of time to prevent flashing
    setTimeout(() => {
      elapsed = true
      if (json) {
        setGetting(false)
      }
    }, 300)
    const response = await fetch(urls.settings, {
      headers: new Headers({ "X-WP-Nonce": nonce })
    })
    json = await response.json()
    setApiKey(json.value.apiKey)
    if (json.value.apiKey.length == 0) {
      setOpen(true)
    }
    if (elapsed) {
      setGetting(false)
    }
  }

  // get the key on settings page load
  useEffect(() => {
    getSettings()
  }, [nonce, urls])

  return (
    <Accordion title={"Settings"} open={isOpen}>
      <form onSubmit={updateSettings}>
        <p>
          <label>
            Google Cloud Vision API Key:{" "}
            <input type="text" value={apiKey} onChange={(e) => setApiKey(e.target.value)} />
          </label>
        </p>
        {isGetting && <p>Loading...</p>}

        <p>
          <a
            href="https://cloud.google.com/vision/docs/setup"
            target="_blank"
            rel="noopener noreferrer">
            Get your Google Cloud Vision API key here.
          </a>
        </p>

        <p>
          <button type="submit" className="button button-primary" disabled={isSaving}>
            Save API key
          </button>
        </p>
      </form>
      <div className="image-sizes-list">
        <h2>Cropped Image Sizes</h2>
        <p>Only these image sizes are eligible for smart cropping:</p>
        <ol>{croppedSizes && croppedSizes.map((size) => <li key={size}>{size}</li>)}</ol>
        <p>
          Other image sizes are not cropped, they are only resized, and thus do not need
          smartcropping.
        </p>
      </div>
    </Accordion>
  )
}

export default Settings
