import React, { useEffect, useState } from "react"
import { Accordion } from "./Accordion"
import { checkApiKey } from "./api"
import "./settings.css"

const Settings = ({ nonce, urls, setNotice }) => {
  const [apiKey, setApiKey] = useState("")
  const [options, setOptions] = useState({
    apiKey: "",
    onUpload: "async"
  })
  const [isSaving, setSaving] = useState(false)
  const [isGetting, setGetting] = useState(true)
  const [isOpen, setOpen] = useState(false)

  const updateOptions = async (event) => {
    event.preventDefault()
    setSaving(true)
    await fetch(urls.settings, {
      body: JSON.stringify({ options }),
      method: "POST",
      headers: new Headers({
        "Content-Type": "application/json",
        "X-WP-Nonce": nonce
      })
    })

    // check if API key is valid
    if (apiKey != options.apiKey) {
      try {
        const data = await checkApiKey(options.apiKey)
        console.log(data)
        setApiKey(options.apiKey)
        setNotice(["API key saved and validated with Google API!", "success"])
      } catch (error) {
        if (error.message == "The request is missing a valid API key.") {
          error.message = "API key not valid. Please check your Google Cloud Vision account."
        }
        setNotice([`Key saved, but there was an error: ${error.message}`, "error"])
      }
    }

    setSaving(false)
  }

  const getOptions = async () => {
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
    console.log(json)
    setOptions(json.value)
    setApiKey(json.value.apiKey)
    if (json.value.apiKey.length == 0) {
      setOpen(true)
    }
    if (elapsed) {
      setGetting(false)
    }
  }

  // get settings on page load
  useEffect(() => {
    getOptions()
  }, [nonce, urls])

  const handleInputChange = (e) => {
    const target = e.target
    const value = target.type === "checkbox" ? target.checked : target.value
    const name = target.name
    const optionValue = value === true ? 1 : value === false ? 0 : value
    setOptions((prev) => ({ ...prev, [name]: optionValue }))
    console.log(options)
  }
  return (
    <Accordion title={"Settings"} open={isOpen}>
      <form onSubmit={updateOptions}>
        <table className="sisa-options-table form-table">
          <tbody>
            <tr>
              <th scope="row">
                Google Cloud Vision <br />
                API Key
              </th>
              <td>
                <input
                  name="apiKey"
                  required
                  type="text"
                  value={options.apiKey}
                  onChange={handleInputChange}
                />
                {isGetting && <p>Loading...</p>}
                <p>
                  <a
                    href="https://cloud.google.com/vision/docs/setup"
                    target="_blank"
                    rel="noopener noreferrer">
                    Get your Google Cloud Vision API key here.
                  </a>
                </p>
              </td>
            </tr>
            <tr>
              <th scope="row">Image Upload</th>
              <td>
                <h4>When should alt text for new images be generated?</h4>
                <p>
                  <input
                    name="onUpload"
                    id="async"
                    type="radio"
                    checked={"async" === options.onUpload}
                    value={"async"}
                    onChange={handleInputChange}
                  />
                  <label htmlFor="async">Generate alt text in the background. (Recommended)</label>
                  <span className="description">
                    Alt text creation will run in the background during image upload. You may need
                    to refresh the screen after upload to see alt text.
                  </span>
                </p>
                <p>
                  <input
                    name="onUpload"
                    id="blocking"
                    type="radio"
                    checked={"blocking" === options.onUpload}
                    value={"blocking"}
                    onChange={handleInputChange}
                  />
                  <label htmlFor="blocking">Generate alt text during upload.</label>
                  <span className="description">
                    Uploads will take longer, but this may solve any compatibility issues with other
                    plugins.
                  </span>
                </p>
                <p>
                  <input
                    name="onUpload"
                    id="none"
                    type="radio"
                    checked={"none" === options.onUpload}
                    value={"none"}
                    onChange={handleInputChange}
                  />
                  <label htmlFor="none">Do not generate alt text on upload.</label>
                </p>
              </td>
            </tr>
          </tbody>
        </table>
        <p>
          <button type="submit" className="button button-primary" disabled={isSaving}>
            Save Settings
          </button>
        </p>
      </form>
    </Accordion>
  )
}

export default Settings
