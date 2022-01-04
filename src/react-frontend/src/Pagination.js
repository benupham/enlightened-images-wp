import React, { useEffect, useState, useCallback } from "react"

// Ununsed draft component for adding pagination.

const Pagination = (props) => {
  const pageSpread = 2

  const { page, lastPage, handlePageChange } = props

  const startPage = Math.max(1, page - pageSpread)
  const endPage = Math.min(lastPage - 1, page + pageSpread)

  let pages = range(startPage, endPage)

  console.log("page", page)

  const handleClick = (e) => {
    const newPage = parseInt(e.target.value)
    console.log(newPage)
    props.setPage(newPage)
  }

  return (
    <div className="pagination">
      <button onClick={handleClick} value={page - 1} disabled={page === 1 ? true : false}>
        Previous Page
      </button>
      <span onClick={handleClick} value={page - 2}>
        {page - 2}
      </span>
      <span onClick={handleClick} value={page - 1}>
        {page - 1}
      </span>
      <span onClick={handleClick} value={page}>
        {page}
      </span>
      <span onClick={handleClick} value={page + 1}>
        {page + 1}
      </span>
      <span onClick={handleClick} value={page + 2}>
        {page + 2}
      </span>
      <button onClick={handleClick} value={page + 1}>
        Next Page
      </button>
    </div>
  )
}

const range = (from, to, step = 1) => {
  let i = from
  const range = []

  while (i <= to) {
    range.push(i)
    i += step
  }

  return range
}

export default Pagination
