import React from "react"
import "./progressbar.css"

export const ProgressBar = ({ stats }) => {
  const { total, errors, remaining } = stats
  const completed = total - remaining
  const percent = Math.round((completed / total) * 100)
  const progressCSS = `width: ${percent}%`

  return (
    <div className="progress-bar-wrap">
      <div className="progress-bar">
        <div className="progress-size" style={{ width: percent + "%" }}></div>
        <div className="progress-stats">{`${completed} of ${total} (${percent})%`}</div>
      </div>
    </div>
  )
}
