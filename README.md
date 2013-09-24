FieldtypeVideo
==============

Processwire field type for storing video files and automatically creating poster images

##This is a very rough draft of this module - it works, but needs lots of cleaning up and enhancing!

I have made a very basic/rough start on a video fieldtype. It extends FieldtypeFile.

The reason I think it is a useful addition is that it automatically creates a poster image of the video on upload and makes this available via:
$page->video_field->poster

It also shows the duration of the video on the title bar, next to the filesize.

I had to put this together quickly for an existing site as they wanted to start adding videos that needed to be private, so I am using mediaelementjs.

With this module, they can simply upload the video and the template makes use of:
```
<video src="{$page->video_field->url}" poster="{$page->video_field->poster}" width="720" height="408" ></video>
```

###Requirements
The module requires ffmpeg and ffmpeg-php, although I can make the latter optional fairly easily.


###Possible future enhancements
* Ability to specify what frame is used for the poster - either by number, and/or by offering several options to choose from
* Push poster image to a dedicated image field
* Field for pasting in or uploading closed captions
* Support for uploading multiple formats of the same video (mp4, webm, etc) and/or automated video format conversion
* Integrate mediaelementjs into the module so users can enter shortcodes in RTE fields to display videos where they want

My biggest concern, is how useful this will be to people - how many hosts actually have ffmpeg setup? Do any have ffmpeg-php?

Anyone have any ideas for features they'd like to see?
