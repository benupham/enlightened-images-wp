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

export const requestSmartCrop = async (preview = true, thumb, urls, nonce) => {
  const isPreview = preview === true ? 1 : 0
  const { size, attachment } = thumb
  const sizeURI = encodeURIComponent(size)
  const reqUrl = `${urls.proxy}?attachment=${attachment.id}&size=${sizeURI}&pre=${isPreview}`
  console.log("request smart crop", reqUrl)
  const response = await fetch(reqUrl, {
    headers: new Headers({ "X-WP-Nonce": nonce, "Cache-Control": "no-cache" })
  })

  const data = await response.json()

  if (response.ok === false || response.status !== 200) {
    console.log("Request smart crop error", data)
    const errorString = `Error: ${data.code}. Message: ${data.message}`
    data.cropError = errorString
    return data
  }

  if (data.success !== true) {
    console.log("data parse errors", data)
    data.cropError = data.body
    return data
  }

  if (data.success === true) {
    console.log("Smart crop returned", data)
    thumb.smartcrop = true
    thumb.isLoading = false
    if (preview === true) {
      thumb.url = data.body.smartcropai.image_url
    } else {
      thumb.isChecked = false
      thumb.cacheId = Date.now()
      thumb.url = thumb.source_url
      thumb.previewFile = ""
    }

    return thumb
  }
}

export const requestImages = async (attachmentId, page, query, perPage) => {
  if (!window.smart_image_crop_ajax || !window.smart_image_crop_ajax.urls) {
    return { code: "Can't find WordPress REST API endpoints. Is the API restricted or turned off?" }
  }
  const mediaApi = window.smart_image_crop_ajax.urls.media
  const nonce = window.smart_image_crop_ajax.nonce

  const id = query.length > 0 || page > 1 ? "" : attachmentId

  const conn = mediaApi.indexOf("?") > -1 ? "&" : "?"
  const url = `${mediaApi}${conn}include=${id}&search=${query}&page=${page}&per_page=${perPage}&mime_type=image/png,image/jpg,image/webp`
  console.log("request images url", url)

  const response = await fetch(url, {
    headers: new Headers({ "X-WP-Nonce": nonce, "Cache-Control": "no-cache" })
  })
  const data = await response.json()
  return data
}
