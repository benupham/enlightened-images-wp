import React, { useEffect, useState } from "react"
import { Accordion } from "./Accordion"
import { checkApi } from "./api"
import { Dashboard } from "./Dashboard"
import "./settings.css"
import { Sidebar } from "./Sidebar"
import { nonce, urls } from "./api"

const Settings = ({ setNotice, estimate, count }) => {
  const [options, setOptions] = useState({
    apiKey: "",
    apiEndpoint: "",
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

  // TODO: Move to separate file. Custom hook? 
  const updateOptions = async (event) => {
    setNotice("")
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

    // check if Azure API key is valid
    if (
      options.proApiKey.length === 0 &&
      options.apiEndpoint.length > 0 &&
      options.apiKey.length > 0
    ) {
      try {
        console.log("checking azure")
        let data = await checkApi(options.apiKey, options.apiEndpoint)
        console.log(data)

        setNotice(["API key and endpoint saved and validated with Azure API!", "success"])
      } catch (error) {
        let endpointError = ""
        if (error.message === "Failed to fetch") endpointError = ": Azure endpoint incorrect."
        setNotice([
          `There was a problem validating with Azure: ${error.message}${endpointError} Please check your Azure account, this is not a problem with this plugin.`,
          "error"
        ])
      }
    }
    if (json.options.isPro !== options.isPro) {
      if (json.options.isPro === 0) {
        setNotice(["Enlightened Images API key is invalid. Please check your account.", "error"])
      } else {
        setNotice([`Options saved, using Enlightened Images API key.`, "success"])
      }
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
  // TODO: Move hasPro settings to separate file. 
  return (
    <div className="settings">
      <div className="dashboard">
        <Accordion title={"Settings"} setOpen={setOpen} open={isOpen}>
          <form onSubmit={updateOptions}>
            {options.isPro === 0 && (
              <div>
                <h2>Setup A: Enter your Enlightened Images API key</h2>
                <p>
                  Purchase an Enlightened Images API key on the Enlightened Images website, then
                  paste the key here and get started immediately.
                </p>
              </div>
            )}
            <table className="elim-options-table form-table">
              <tbody>
                <tr>
                  <th scope="row">
                    Enlightened Images
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
                        href={`https://enlightenedimageswp.com/checkout/?count=${count}`}
                        target="_blank"
                        rel="noopener noreferrer">
                        Get your Enlightened Images API key here.
                      </a>
                    </p>
                  </td>
                </tr>
                {options.isPro === 0 && (
                  <>
                    <tr>
                      <th colSpan={2}>
                        <h2>Setup B: Enter your Microsoft Azure Computer Vision account details</h2>
                        <p>
                          If you want to use your own Microsoft Azure account instead of purchasing
                          an Enlightened Images key, you will need an Azure Computer Vision API key
                          and endpoint. You can learn how to get these by going to the{" "}
                          <a
                            href="https://azure.microsoft.com/en-us/services/cognitive-services/computer-vision/#overview"
                            target="_blank"
                            rel="noopener noreferrer">
                            Azure Computer Vision website
                          </a>
                          . The correct endpoint will look something like
                          <code>
                            https://[your-custom-azure-domain].cognitiveservices.azure.com
                          </code>
                          . Do not include the final backslash on the URL.
                        </p>
                        <p>
                          As with all Microsoft services, it is a nightmare to set up, which is why
                          we offer the paid service. Good luck!
                        </p>
                      </th>
                    </tr>
                    <tr>
                      <th scope="row">
                        Azure Computer Vision <br />
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
                      </td>
                    </tr>
                    <tr>
                      <th scope="row">
                        Azure Computer Vision <br />
                        Endpoint
                      </th>
                      <td>
                        <input
                          name="apiEndpoint"
                          type="url"
                          value={options.apiEndpoint}
                          onChange={handleInputChange}
                          placeholder={
                            isGetting
                              ? "Loading..."
                              : "https://[some endpoint].cognitiveservices.azure.com/"
                          }
                        />
                      </td>
                    </tr>
                  </>
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
                    {/* <tr>
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
                    </tr> */}
                  </>
                )}
              </tbody>
            </table>
            <div>
              <button type="submit" className="button" disabled={isSaving}>
                Save Settings
              </button>
              <div className="filler"></div>
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
