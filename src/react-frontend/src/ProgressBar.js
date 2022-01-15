export const ProgressBar = ({ stats }) => {
  const { total, errors, remaining } = stats
  const completed = total - remaining
  const percent = (completed / total).toFixed(2)
  const progressCSS = `width: ${percent}%`

  return (
    <div className="progress-bar-wrap">
      <div className="progress-bar">
        <div className="progress-size" style={progressCSS}></div>
        <div className="progress-stats">{`${completed} of ${total} (${percent})%`}</div>
      </div>
    </div>
  )
}
