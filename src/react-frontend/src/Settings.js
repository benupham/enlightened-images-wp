import React, { useEffect, useState } from "react"
import { Accordion } from "./Accordion"
import { checkApiKey } from "./api"
import { Dashboard } from "./Dashboard"
import "./settings.css"
import { Sidebar } from "./Sidebar"

const Settings = ({ nonce, urls, setNotice, estimate, count }) => {
  const [options, setOptions] = useState({
    apiKey: "",
    proApiKey: "",
    onUpload: "async",
    isPro: null,
    hasPro: null,
    text: 0,
    labels: 0,
    landmarks: 0,
    logos: 0,
    altText: 0
  })
  const [isSaving, setSaving] = useState(false)
  const [isGetting, setGetting] = useState(true)
  const [isOpen, setOpen] = useState(false)

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
    } else if (json.options.isPro === 0) {
      setNotice(["EnlightenedImages API key is invalid. Please check your account.", "error"])
    } else if (json.options.isPro === 1) {
      setNotice([`Options saved, using EnlightenedImages API key.`, "success"])
    } else {
      setNotice([`Options saved, using Google API key.`, "success"])
    }

    setOpen(json.options.isPro == 0 ? true : false)
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
    setOptions(json.options)

    setOpen(json.options.isPro == 0 ? true : false)

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
    <div className="settings">
      <div className="dashboard">
        <Accordion title={"Settings"} setOpen={setOpen} open={isOpen}>
          <form onSubmit={updateOptions}>
            {options.isPro === 0 && (
              <div>
                <h2>Enter your EnlightenedImages API key or Google API key</h2>
              </div>
            )}
            <table className="sisa-options-table form-table">
              <tbody>
                <tr>
                  <th scope="row">
                    EnlightenedImages
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
                    <p>
                      <a
                        href="https://smart-image-ai.lndo.site/"
                        target="_blank"
                        rel="noopener noreferrer">
                        Get your EnlightenedImages API key here.
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
                  <>
                    <tr>
                      <th colSpan={2}>
                        <h2>Enlightened Images Pro Settings</h2>
                      </th>
                    </tr>
                    <tr>
                      <th scope="row">Annotation on Image Upload</th>
                      <td>
                        <h4>When should analysis of new images be performed?</h4>
                        <p>
                          Specify when to automatically create alt text, and other image analysis
                          features, on newly uploaded images.
                        </p>
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
                            Alt text creation will run in the background during image upload. You
                            may need to refresh the screen after upload to see alt text.
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
                            Uploads will take longer, but this may solve any compatibility issues
                            with other plugins.
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
                    <tr>
                      <th scope="row">Annotation Features</th>
                      <td>
                        <h4>Select the image analyzation features you want to perform.</h4>
                        <p>
                          <input
                            name="altText"
                            id="altText"
                            type="checkbox"
                            checked={1 === options.altText}
                            value={"altText"}
                            onChange={handleInputChange}
                          />
                          <label htmlFor="async">Alt text</label>
                          <span className="description">
                            Automatically generate image alt text for every image that is missing
                            it. Also identifies specific objects in images, and similar images on
                            the web.
                          </span>
                        </p>
                        <p>
                          <input
                            name="labels"
                            id="labels"
                            type="checkbox"
                            checked={1 === options.labels}
                            value={"labels"}
                            onChange={handleInputChange}
                          />
                          <label htmlFor="labels">Labels</label>
                          <span className="description">
                            Identify general objects, locations, activities, animal species,
                            products, and more
                          </span>
                        </p>
                        <p>
                          <input
                            name="text"
                            id="text"
                            type="checkbox"
                            checked={1 === options.text}
                            value={"text"}
                            onChange={handleInputChange}
                          />
                          <label htmlFor="text">Text recognition</label>
                          <span className="description">
                            Use optical character recognition (OCR) to extract text from images and
                            save to image metadata.
                          </span>
                        </p>
                        <p>
                          <input
                            name="logos"
                            id="logos"
                            type="checkbox"
                            checked={1 === options.logos}
                            value={"logos"}
                            onChange={handleInputChange}
                          />
                          <label htmlFor="logos">Logos</label>
                          <span className="description">
                            Identify logos from brands, organizations — anything and add it to image
                            metadata.
                          </span>
                        </p>
                        <p>
                          <input
                            name="landmarks"
                            id="landmarks"
                            type="checkbox"
                            checked={1 === options.landmarks}
                            value={"landmarks"}
                            onChange={handleInputChange}
                          />
                          <label htmlFor="landmarks">Landmarks</label>
                          <span className="description">
                            Identify landmarks and add that information to image metadata.
                          </span>
                        </p>
                      </td>
                    </tr>
                  </>
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
      </div>
      {(options.isPro !== 1 || options.hasPro !== 1) && (
        <Sidebar estimate={estimate} count={count} isPro={options.isPro} hasPro={options.hasPro} />
      )}
    </div>
  )
}

export default Settings
