import React from "react"
import "./progressbar.css"

export const ProgressBar = ({ stats }) => {
  let { total, remaining } = stats
  let completed = parseInt(total - remaining) || 0
  let percent = parseInt(Math.round((completed / total) * 100)) || 0
  if (!percent) percent = 0
  if (!total) total = 0
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
