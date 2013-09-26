FieldtypeVideo
==============

Processwire field type for storing video files and automatically creating poster images

##This is a very rough draft of this module - it works, but needs lots of cleaning up and enhancing!

I have made a very basic/rough start on a video fieldtype. It extends FieldtypeFile.

It automatically creates a poster image of the video on upload and makes this available via: $page->video_field->poster

It shows the duration of the video on the title bar, next to the filesize.

It also handles SRT subtitles and conversion to a formatted transcript.

I had to put this together quickly for an existing site as they wanted to start adding videos that needed to be private, so I am using mediaelementjs.

With this module, they can simply upload the video and the template makes use of:
```
<video src="{$page->video_field->url}" poster="{$page->video_field->poster}" width="720" height="408" ></video>
```

###Requirements
The module requires ffmpeg and ffmpeg-php, although I can make the latter optional fairly easily. I don't have any requirement checking implemented yet, so if you don't have these, you'll get php errors.


###Possible future enhancements
* Multi language versions of subtitles
* Support for uploading multiple formats of the same video (mp4, webm, etc) and/or automated video format conversion
* Integrate mediaelementjs into the module so users can enter shortcodes in RTE fields to display videos where they want

My biggest concern, is how useful this will be to people - how many hosts actually have ffmpeg setup? Do any have ffmpeg-php?

Discussion:
http://processwire.com/talk/topic/4580-video-fieldtype/
