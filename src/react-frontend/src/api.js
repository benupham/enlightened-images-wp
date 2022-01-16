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
