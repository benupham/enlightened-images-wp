=== Smart Image Crop AI ===
Contributors: bcupham
Tags: resize images, crop images, images
Requires at least: 5.1.0
Tested up to: 5.8.2
Requires PHP: 5.4
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Use the power of machine learning to crop images automatically. 

== Description ==

If your WordPress website uses cropped image sizes, also known as image thumbnails, you may find this plugin useful. The problem with cropped image sizes is that they may not be the part of the image you want. 

Smart Crop Image AI plugin solves all that -- automagically! Using the power of artificial intelligence, easily re-crop badly cropped images to get the "perfect" crop. 


== Features ==

* Automagically crop not only faces, but whatever the "main focus" of an image is, based on Google's kabillion years of machine learning
* Preview smart crops before saving them
* Search for images to crop by file name
* Adds a smart crop button to individual media files as well
* Compatible with thumbnail regeneration and image optimization plugins (see below)

== How Does It Work? ==

This plugin uses the Google Cloud Vision API to guess the perfect crop for an image. It requires a Google Cloud Vision API key, which is free and can be acquired [here](https://cloud.google.com/vision/docs/setup). As of 2022, the first 1000 requests per month are free. If you need more that is between you and our Google overlords (i.e. you'll need to give them your credit card).

== Is it Compatible With Image Optimization Plugins? ==

Yes, but you may need to recompress any smart cropped images. If a smart cropped image is derived from an image that is already compressed, it is likely to be fairly small anyway. TinyPNG will keep track of what images have changed after compression so you can recompress them. Smush and Shortpixel do not. 

== Is it Compatible with WebP? ==

Not yet. I plan to include automatic smart cropping of webp images in a future version. 

== Is it Compatible with Retina? ==

Not yet. I plan to include automatic smart cropping of retina images in a future version. 