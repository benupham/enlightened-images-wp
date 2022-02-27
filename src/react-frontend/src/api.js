export const urls = window.smartimagesearch_ajax.urls
export const mediaApi = window.smartimagesearch_ajax.urls.media
export const proxyApi = window.smartimagesearch_ajax.urls.proxy
export const settingsApi = window.smartimagesearch_ajax.urls.settings
export const nonce = window.smartimagesearch_ajax.nonce

export const checkApiKey = async (apiKey) =>
  fetch(`https://vision.googleapis.com/v1/images:annotate?key=${apiKey}`, { method: "POST" })
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
