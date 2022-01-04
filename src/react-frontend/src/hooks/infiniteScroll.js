export function getObserver(ref, handleObserver, options) {
  let observer = ref.current
  if (observer !== null) {
    return observer
  }

  let newObserver = new IntersectionObserver(handleObserver, options)
  ref.current = newObserver
  return newObserver
}
