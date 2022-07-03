export const urls = window.enlightenedimages_ajax.urls
export const mediaApi = window.enlightenedimages_ajax.urls.media
export const proxyApi = window.enlightenedimages_ajax.urls.proxy
export const settingsApi = window.enlightenedimages_ajax.urls.settings
export const nonce = window.enlightenedimages_ajax.nonce

export const checkApi = async (apiKey, apiEndpoint) =>
  fetch(apiEndpoint + "/vision/v3.1/describe", {
    method: "POST",
    headers: {
      "Ocp-Apim-Subscription-Key": apiKey,
      "Content-Type": "application/json",
      Accept: "application/json"
    }
  })
    .then((response) => response.json())
    .then((data) => {
      if (!data.error) {
        return data
      } else {
        throw new Error(data.error.message)
      }
    })

export const updateAltText = async (id, altText) => {
  console.log("alt text:" + altText)
  const strippedHTML = decodeURI(altText.replace(/(<([^>]+)>)/gi, ""))
  console.log("stripped text:" + strippedHTML)
  try {
    const response = await fetch(mediaApi + "/" + id, {
      method: "POST",
      body: JSON.stringify({
        alt_text: strippedHTML
      }),
      headers: new Headers({ "X-WP-Nonce": nonce, "Content-Type": "application/json" })
    })
    const json = await response.json()
    console.log(json)
    return json.alt_text
  } catch (error) {
    throw new Error(error)
  }
}

export const updateAttachmentTitle = async (id, title) => {
  console.log("title:" + title)
  const strippedHTML = decodeURI(title.replace(/(<([^>]+)>)/gi, ""))
  console.log("stripped text:" + strippedHTML)
  try {
    const response = await fetch(mediaApi + "/" + id, {
      method: "POST",
      body: JSON.stringify({
        title: strippedHTML
      }),
      headers: new Headers({ "X-WP-Nonce": nonce, "Content-Type": "application/json" })
    })
    const json = await response.json()
    console.log(json)
    return json.alt_text
  } catch (error) {
    throw new Error(error)
  }
}
