=== Enlightened Images Alt Text Generator ===
Contributors: bcupham
Tags: image alt text, alt text, seo, accessibility
Requires at least: 5.1.0
Tested up to: 6.0
Requires PHP: 7.0
Stable tag: 1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Generate image alt text automatically with machine learning. 

== Description ==

Image alt text (alternative text or alt tag) is an accessibility requirement and important for website SEO (search engine optimization). It is strongly recommended every image on your website have an alt text description. However, most websites have dozens or hundreds of images that are missing alt text. Adding it manually is a huge pain and website users may forget to do so when they upload images to the Media Library. 

The Enlightened Images plugin is a bulk alt text generation tool. It solves the problem of missing alt text by generating alt text automatically. Run the Bulk Alt Text tool and generate image alt text using artificial intelligence for all images missing it. 

== Features ==

* Bulk generate alt text for every image in the Media Library missing it. 
* Use your own Microsoft Azure account credentials to generate alt text, or optionally purchase an API key from Enlightened Images.

== Installation ==

1. Install this plugin from the directory or by downloading it directly from here and uploading it.
2. Activate the plugin. 
3. Go to Media -> Bulk Alt Text. 
4. Either enter in your Enlightened Images API key, or your Azure API key and endpoint and save.
5. Run the bulk annotation tool and watch your alt text problems melt away... 

== Screenshots == 

1. The settings page. 
2. The alt text tool in action.  

== How Does It Work? ==

The plugin sends your images to the Gnome Kingdom, far beneath the Mountains of Mist, where little gnome poets carefully craft alt text descriptions of each image by listening to the whispering of the Demon God while in a trance state. 

Just kidding! We send it to Microsoft, where a computer makes a guess about what is the image and spits out a one-sentence description of it based on that guess. 

== Do I Have to Buy an Enlightened Images API Key? ==

No. You just have to get your own Microsoft Azure API key and "endpoint". It is a real pain, which is why we suggest [buying a key from us](https://enlightenedimageswp.com). Time = money and the time you spend trying to figure out how Azure works is very likely to cost far more than purchasing a key from us.  

== Is there a Pro Version of the Plugin? ==

We're so glad you asked. Yes, [there is a pro version](https://enlightenedimageswp.com). The pro version allows you to generate alt text automatically when new images are uploaded. It also allows for editing the machine-generated alt text in the bulk tool, rather than having to go to the image attachment page. Plus, the pro version will support any new features we add, like text recognition, NSFW classification, and other cool stuff. 

The pro version does not include an Enlightened Images API key. So you can have an API key, or you can have the pro plugin, or both. Or neither! It's all up to you. 

== Is it Compatible With Image Optimization Plugins? ==

Yes. 

== What Image File Types are Supported? ==

Definitely png and jpg. As for webp, it's really not clear. We are using one Azure endpoint that appears to process webp. But another endpoint does not, and the (terrible) Microsoft documentation makes no mention of webp. So our recommendation is to send only jpg and png. But don't worry if you use a plugin that generates webp for your images, the jpg/png version should still exist and be the one sent to Azure automatically. 

== Changelog == 

= 1.3 =
* Handles Azure free-tier rate-limit error.
* Handles WP_DEBUG mode messing with API endpoint. 

= 1.2 =
* Tested up to WordPress 6.0
* Checks for non-existing alt text, not just empty. 