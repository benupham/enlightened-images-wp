import React, { useMemo, useEffect, useState, useRef } from "react"
import "./dashboard.css"
import { collateThumbs, resetUrlParams } from "./helper"
import Thumbnail from "./Thumbnail"
import FilterBar from "./Filterbar"
import lodash, { merge } from "lodash"
import { requestSmartCrop, requestImages } from "./api"
import { getObserver } from "./hooks/infiniteScroll"

const Dashboard = ({ urls, nonce, croppedSizes, setNotice }) => {
  const [thumbs, setThumbs] = useState([])
  const [query, setQuery] = useState("")
  const [filterCropped, setCropFilter] = useState(true)
  const [errorMessage, setErrorMessage] = useState("")
  const [pageLoading, setPageLoading] = useState(true)
  const [cropsLoading, setCropsLoading] = useState(false)
  const [lastPage, setLastPage] = useState(false)
  const [allSelected, setAllSelected] = useState(false)
  let page = 1

  const handleGetImages = async (page) => {
    const queryString = window.location.search
    const urlParams = new URLSearchParams(queryString)
    const id = urlParams.get("attachmentId") ? urlParams.get("attachmentId") : ""
    const perPage = 10

    setPageLoading(true)
    const data = await requestImages(id, page, query, perPage)
    setPageLoading(false)

    if (data.length === 0 && thumbs.length === 0) {
      setErrorMessage("No image sizes found.")
      setThumbs([])
      return
    }

    if (data.length === 0 || data.length < perPage) {
      setLastPage(true)
    }

    if (data.code && data.code === "rest_post_invalid_page_number") {
      setLastPage(true)
      return
    }

    if (data.code) {
      setErrorMessage(`<b>There was an error:</b> ${data.message}`)
      return
    }

    setErrorMessage("")

    const newThumbs = collateThumbs(data, croppedSizes)

    setThumbs((_thumbs) => _thumbs.concat(newThumbs))
  }

  const handleSubmit = async (e) => {
    setCropsLoading(true)

    const preview = e.target.id === "save" ? false : true
    for (let i = 0; i < thumbs.length; i++) {
      if (thumbs[i].isChecked) {
        thumbs[i].isLoading = true
        setThumbs([...thumbs])

        const newThumb = await requestSmartCrop(preview, thumbs[i], urls, nonce)
        if (!newThumb.cropError) {
          const newThumbs = thumbs.map((t) => (newThumb.file === t.file ? (t = newThumb) : t))
          setThumbs(newThumbs)
        } else {
          setNotice([newThumb.cropError, "error"])
        }
      }
    }
    if (preview === false) resetUrlParams()

    setCropsLoading(false)
  }

  const handleCropFilter = (filterCropped) => {
    setCropsLoading(true)
    setCropFilter(filterCropped)
    setCropsLoading(false)
  }

  const handleThumbChecked = (e, i) => {
    const value = e.target.checked
    const newThumbs = thumbs.map((thumb, index) =>
      index === i ? { ...thumb, isChecked: value } : thumb
    )
    setThumbs(newThumbs)
  }

  useEffect(() => {
    const newThumbs = thumbs.map((t) => {
      t.isChecked = allSelected
      return t
    })
    setThumbs(newThumbs)
  }, [allSelected])

  const loader = useRef(null)
  const observerRef = useRef(null)

  useEffect(() => {
    const options = {
      root: null,
      rootMargin: "33%",
      threshold: 0
    }

    const observer = getObserver(observerRef, handleObserver, options)
    if (loader.current) {
      observer.observe(loader.current)
    }
    if (lastPage) {
      return observer.disconnect()
    }
  }, [lastPage])

  const handleObserver = (entities) => {
    const target = entities[0]
    if (target.isIntersecting === true) {
      // setPage((_page) => _page + 1)
      handleGetImages(page)
      page++
    }
  }

  return (
    <div className="smart_image_crop_wrapper wrap">
      <div>
        Specific images can be smartcropped by opening them in the{" "}
        <a href="/wp-admin/upload.php?mode=grid">media library</a> and clicking the SmartCrop
        Thumbnails button.
      </div>
      <FilterBar
        handleSubmit={handleSubmit}
        handleCropFilter={handleCropFilter}
        setAllSelected={setAllSelected}
        allSelected={allSelected}
        cropsLoading={cropsLoading}
      />
      <div
        className={
          cropsLoading === true
            ? "crops-loading smart_image_crop_thumbs"
            : "smart_image_crop_thumbs"
        }>
        {errorMessage && (
          <div className="error settings-error">
            <p>{errorMessage}</p>
          </div>
        )}
        {!errorMessage &&
          thumbs &&
          thumbs.map((thumb, index) => {
            if (!!filterCropped && !!thumb.smartcropped) {
              return null
            } else {
              return (
                <Thumbnail
                  thumb={thumb}
                  key={index}
                  index={index}
                  handleChange={handleThumbChecked}
                />
              )
            }
          })}
      </div>
      <div ref={loader}>
        {pageLoading === true && (
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
