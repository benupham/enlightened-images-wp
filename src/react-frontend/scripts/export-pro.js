const fs = require("fs")
const archiver = require("archiver")
const path = require("path")

const prefix = "enlightened-images-pro" // folder name
const output = fs.createWriteStream(
  path.resolve(__dirname, "../../../../enlightened-images-pro.zip")
)
const archive = archiver("zip", { zlib: { level: 9 } }) // Sets the compression level.

// listen for all archive data to be written
// 'close' event is fired only when a file descriptor is involved
output.on("close", function () {
  console.log(archive.pointer() + " total bytes")
  console.log("archiver has been finalized and the output file descriptor has closed.")
})

// This event is fired when the data source is drained no matter what was the data source.
// It is not part of this library but rather from the NodeJS Stream API.
// @see: https://nodejs.org/api/stream.html#stream_event_end
output.on("end", function () {
  console.log("Data has been drained")
})

// good practice to catch warnings (ie stat failures and other non-blocking errors)
archive.on("warning", function (err) {
  if (err.code === "ENOENT") {
    // log warning
  } else {
    // throw error
    throw err
  }
})

// good practice to catch this error explicitly
archive.on("error", function (err) {
  throw err
})

// pipe archive data to the file
archive.pipe(output)

archive.glob("../../src/react-frontend/build/static/js/main.*.js", null)
archive.glob("../../src/react-frontend/build/static/js/main.*.js.map", null)
archive.glob("../../src/react-frontend/build/static/css/main.*.css", null)
archive.glob("../../src/class-gcv-client.php")
archive.glob("../../src/class-pro-client.php")
archive.glob("../../src/class-elim-pro-plugin.php")
archive.file(path.resolve(__dirname, "../../../enlightened-images-pro.php"), {
  name: "enlightened-images-pro.php"
})
archive.file(path.resolve(__dirname, "../../../uninstall.php"), {
  name: "uninstall.php"
})
archive.file(path.resolve(__dirname, "../build/asset-manifest.json"), {
  name: "../src/react-frontend/build/asset-manifest.json"
})
archive.file(path.resolve(__dirname, "../../../README.txt"), { name: "README.txt" })
archive.file(path.resolve(__dirname, "../../../LICENSE.txt"), { name: "LICENSE.txt" })

// finalize the archive (ie we are done appending files but streams have to finish yet)
// 'close', 'end' or 'finish' may be fired right after calling this method so register to them beforehand
archive.finalize()
