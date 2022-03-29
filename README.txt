=== EnlightenedImages Alt Text Generator ===
Contributors: bcupham
Tags: images, alt text, accessibility
Requires at least: 5.1.0
Tested up to: 5.9.2
Requires PHP: 7.4
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Generate image alt text automatically with machine learning. 

== Description ==

Image alternative text, or "alt text" is both a requirement for accessible websites and a key part of any website SEO strategy. Google and other search engines use image alt text to help determine the content of your webpages. It is strongly recommended every image on your website have an alt text description.  

However, all too often images are uploaded to the Media Library without alt text being added. And then before you know it you've got a website with hundreds of images missing alt text. Argh! The prospect of having to update them all manually is depressing. 

The EnlightenedImages plugin solves this problem by generating alt text automatically using a bulk tool. Run the Bulk Alt Text tool and generate alt text for all images missing it. 

== Features ==

* Bulk generate alt text for every image in the Media Library missing it. 
* Use your own Microsoft Azure account credentials to generate alt text, or optionally purchase an API key from EnlightenedImages.

== Installation ==

1. Install this plugin from the directory or by downloading it directly from here and uploading it.
2. Activate the plugin. 
3. Go to Media -> Bulk Alt Text. 
4. Either enter in your EnlightenedImages API key, or your Azure API key and endpoint and save.
5. Run the bulk annotation tool and watch your alt text problems melt away... 

== Screenshots == 

1. The settings page. 
2. The alt text tool in action.  

== How Does It Work? ==

The plugin sends your images to the Gnome Kingdom, far beneath the Mountains of Mist, where little gnome poets carefully craft alt text descriptions of each image by listening to the whispering of the Demon God while in a trance state. 

Just kidding! We send it to Microsoft, where a computer makes a guess about what is the image and spits out a one-sentence description of it based on that guess. 

== Do I Have to Buy an EnlightenedImages API Key? ==

No. You just have to get your own Microsoft Azure API key and "endpoint". It is a real pain, which is why we suggest buying a key from us. Time = money and the time you spend trying to figure out how Azure works is very likely to be far more than purchasing a key from us.  

== Is there a Pro Version of the Plugin? ==

We're so glad you asked. Yes, there is a pro version. The pro version allows you to generate alt text automatically when new images are uploaded. It also allows for editing the machine-generated alt text in the bulk tool, rather than having to go to the image attachment page. Plus, the pro version will support any new features we add, like text recognition, NSFW classification, and other cool stuff. 

The pro version does not include an EnlightenedImages API key. So you can have an API key, or you can have the pro plugin, or both. Or neither! It's all up to you. 

== Is it Compatible With Image Optimization Plugins? ==

Yes. 

== What Image File Types are Supported? ==

Definitely png and jpg. As for webp, it's really not clear. We are using one Azure endpoint that appears to process webp. But another endpoint does not, and the documentation makes no mention of webp. So our recommendation is to send only jpg and png. If you use a plugin that generates webp for your images, the jpg/png version should still exist and be the one sent to Azure automatically. 

