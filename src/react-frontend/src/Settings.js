import React, { useEffect, useState } from "react"
import { Accordion } from "./Accordion"
import { checkApiKey } from "./api"
import { Dashboard } from "./Dashboard"
import "./settings.css"

const Settings = ({ nonce, urls, setNotice, estimate }) => {
  const [apiKey, setApiKey] = useState("")
  const [proApiKey, setProApiKey] = useState("")
  const [options, setOptions] = useState({
    apiKey: "",
    proApiKey: "",
    onUpload: "async",
    isPro: 0,
    hasPro: 0,
    text: 0,
    labels: 0,
    landmarks: 0,
    logos: 0,
    altText: 1
  })
  const [isSaving, setSaving] = useState(false)
  const [isGetting, setGetting] = useState(true)
  const [isOpen, setOpen] = useState(false)
  const [isSavable, setSavable] = useState(false)

  const updateOptions = async (event) => {
    event.preventDefault()
    setSaving(true)
    console.log("sending options")
    console.log(options)
    let response = await fetch(urls.settings, {
      body: JSON.stringify({ options }),
      method: "POST",
      headers: new Headers({
        "Content-Type": "application/json",
        "X-WP-Nonce": nonce
      })
    })
    let json = await response.json()
    setOptions(json.options)
    console.log(json)

    // check if Google API key is valid
    if (options.proApiKey.length === 0) {
      try {
        let data = await checkApiKey(options.apiKey)
        console.log(data)

        setNotice(["API key saved and validated with Google API!", "success"])
      } catch (error) {
        // if (error.message == "The request is missing a valid API key.") {
        //   error.message = "Google API key not valid. Please check your Google Cloud Vision account."
        // }
        setNotice([
          `Google has a problem with your API key: ${error.message} Please check your Google account, this is not a problem with this plugin.`,
          "error"
        ])
      }
      setApiKey(options.apiKey)
    } else if (json.options.isPro === 0) {
      setNotice(["Pro API key is invalid. Please check your account.", "error"])
    } else if (json.options.isPro === 1) {
      setNotice([`Options saved, using Smart Image Pro API key.`, "success"])
    } else {
      setNotice([`Options saved, using Google API key.`, "success"])
    }

    setSaving(false)
    setSavable(false)
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
    setOptions(json.options)

    if (json.options.proApiKey.length == 0) {
      setOpen(true)
    }
    if (elapsed) {
      setGetting(false)
    }
  }

  // get settings on page load
  useEffect(() => {
    getOptions()
  }, [nonce, urls, estimate])

  const handleInputChange = (e) => {
    const target = e.target
    const value = target.type === "checkbox" ? target.checked : target.value
    const name = target.name
    const optionValue = value === true ? 1 : value === false ? 0 : value
    setOptions((prev) => ({ ...prev, [name]: optionValue }))
  }

  return (
    <>
      <Accordion title={"Settings"} open={isOpen}>
        {options.isPro === 0 && (
          <div>
            <h2>Enter your SmartImage API key or Google API key</h2>
            <div className="estimate alertbar">
              <div>
                <span className="title">
                  Generate missing alt text for all images for only: ${estimate}
                </span>
              </div>
              <a
                href="https://dev-smart-image-ai.pantheonsite.io/checkout/"
                target="_blank"
                rel="noreferrer"
                className="button-primary">
                Buy Now
              </a>
            </div>
          </div>
        )}
        <form onSubmit={updateOptions} onChange={() => setSavable(true)}>
          <table className="sisa-options-table form-table">
            <tbody>
              <tr>
                <th scope="row">
                  SmartImage Pro
                  <br />
                  API Key
                </th>
                <td>
                  <input
                    name="proApiKey"
                    type="text"
                    value={options.proApiKey}
                    onChange={handleInputChange}
                    placeholder={isGetting ? "Loading..." : "Enter key"}
                  />
                  {/* {isGetting && <p>Loading...</p>} */}
                  <p>
                    <a
                      href="https://smart-image-ai.lndo.site/"
                      target="_blank"
                      rel="noopener noreferrer">
                      Get your Smart Image Pro API key here.
                    </a>
                  </p>
                </td>
              </tr>
              {options.isPro === 0 && (
                <tr>
                  <th scope="row">
                    Google Cloud Vision <br />
                    API Key
                  </th>
                  <td>
                    <input
                      name="apiKey"
                      type="text"
                      value={options.apiKey}
                      onChange={handleInputChange}
                      placeholder={isGetting ? "Loading..." : "Enter key"}
                    />
                    {/* {isGetting && <p>Loading...</p>} */}
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
              )}
              {options.hasPro === 1 && (
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
                      <label htmlFor="async">
                        Generate alt text in the background. (Recommended)
                      </label>
                      <span className="description">
                        Alt text creation will run in the background during image upload. You may
                        need to refresh the screen after upload to see alt text.
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
                        Uploads will take longer, but this may solve any compatibility issues with
                        other plugins.
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
              )}
            </tbody>
          </table>
          <div>
            <button type="submit" className="button" disabled={isSaving}>
              Save Settings
            </button>
          </div>
        </form>
      </Accordion>
      <Dashboard urls={urls} nonce={nonce} options={options} />
    </>
  )
}

export default Settings
