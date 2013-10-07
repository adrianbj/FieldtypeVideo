FieldtypeVideo
==============

Processwire field type for storing video files and automatically creating poster images

##It works, but I still want to clean up and enhance it!

This new video fieldtype extends FieldtypeFile.
* the video is available via: $page->video_field->url
* automatically creates a poster image of the video on upload and makes this available via: $page->video_field->poster
* shows the duration of the video on the title bar, next to the filesize
* Stores SRT files for subtitles accessed via: $page->video_field->subtitles
* Formats a transcript from the subtitles, accessed via: $page->video_field->transcript

I am using mediaelementjs to display the videos, so editing users can easily upload videos and enter SRT files. The following code is used in the template file. You can adjust this if you'd prefer using VideoJS or some other HTML5 player.

```
<video src='{$page->video_field->eq(1)->url}' poster='{$page->video_field->eq(1)->poster}' width='720' height='408' ><track kind='subtitles' src='{$page->video_field->eq(1)->subtitles}' srclang='en' /></video>
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
