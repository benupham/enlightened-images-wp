import { isInteger } from "lodash"

/**
 * Filter and sort WP image data from media API
 * @param {json} imagesJson All images returned from the API
 * @param {json} croppedSizes The hard-cropped image sizes in WP
 * @param {boolean} filterCropped Whether to filter out already smartcropped images
 * @returns {array}
 */
export const collateThumbs = (imagesJson, croppedSizes) => {
  const allThumbs = imagesJson.reduce((allacc, image) => {
    const thumbs = Object.entries(image.media_details.sizes).reduce((acc, [size, details]) => {
      if (croppedSizes.includes(size)) {
        const thumb = details
        thumb.size = size
        thumb.isChecked = false
        thumb.isLoading = false
        thumb.previewFile = ""
        thumb.attachment = {}
        thumb.attachment.id = image.id
        thumb.attachment.width = image.media_details.width
        thumb.attachment.height = image.media_details.height
        thumb.attachment.file = image.media_details.file
        thumb.attachment.source_url = image.guid.rendered
        thumb.url = thumb.source_url
        thumb.cacheId = Date.now()
        thumb.loading = false
        if (
          image.smartcropai &&
          Object.prototype.hasOwnProperty.call(image.smartcropai, size) &&
          isInteger(image.smartcropai[size])
        ) {
          thumb.smartcropped = true
        } else {
          thumb.smartcropped = false
        }
        acc.push(thumb)
      }
      return acc
    }, [])

    allacc.push(...thumbs)
    return allacc
  }, [])

  return allThumbs
}

/**
 * Filter WP image sizes for those that are hard-cropped.
 * @param {object} sizes
 * @returns {array}
 */
export const getCroppedSizes = (sizes) => {
  const croppedSizes = Object.entries(sizes).reduce((acc, [size, details]) => {
    if (details.crop !== false) {
      const croppedSize = size
      acc.push(croppedSize)
    }
    return acc
  }, [])

  return croppedSizes
}

/**
 * Reset the search params in the URL (when coming from an individual media file)
 */
export const resetUrlParams = () => {
  if ("URLSearchParams" in window) {
    const search = window.location.search
    if (search) {
      const params = new URLSearchParams(search)
      params.delete("attachmentId")
      window.history.pushState({}, document.title, `${window.location.pathname}?${params}`)
    }
  }
}
