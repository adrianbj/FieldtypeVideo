FieldtypeVideo
==============

Processwire field type for storing video files and automatically creating poster images

###This new video fieldtype extends FieldtypeFile.

* Video is available via: $page->video_field->url
* Module automatically creates a poster image of the video on upload and makes this available via: $page->video_field->poster
* Shows the duration of the video on the title bar, next to the filesize
* Stores SRT files for subtitles accessed via: $page->video_field->subtitles
* Formats a transcript from the subtitles, accessed via: $page->video_field->transcript

I am using mediaelementjs to display the videos, so editing users can easily upload videos and enter SRT files. The following code is used in the template file. You can adjust this if you'd prefer using VideoJS or some other HTML5 player.

```
<video src='{$page->video_field->eq(1)->url}' poster='{$page->video_field->eq(1)->poster}' width='720' height='408' ><track kind='subtitles' src='{$page->video_field->eq(1)->subtitles}' srclang='en' /></video>
```

###Usage
Basic usage only requires setting up a field with this new video fieldtype. Simply upload a video and if desired enter subtitles in SRT format.

####Additional settings
You can additionally set a few different options in the field's Input tab:
* Number of poster images to generate - if you change from the default of 1, the editing user will be able to select which image they want to use for the poster image
* Copy poster image to dedicated image field - not necessary but gives you more options of interacting with the poster image(s)
* Field that you want poster images copied into - only relevant if the option above is checked

###Requirements
The module requires ffmpeg and ffmpeg-php, although I can make the latter optional fairly easily. I don't have any requirement checking implemented yet, so if you don't have these, you'll get php errors.


###Possible future enhancements
* Multi language versions of subtitles
* Support for uploading multiple formats of the same video (mp4, webm, etc) and/or automated video format conversion
* Integrate mediaelementjs into the module so users can enter shortcodes in RTE fields to display videos where they want


Discussion:
http://processwire.com/talk/topic/4580-video-fieldtype/
