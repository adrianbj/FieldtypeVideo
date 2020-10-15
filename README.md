# FieldtypeVideo

Processwire field type for storing video files and automatically creating poster images

### This new video fieldtype extends FieldtypeFile.

* Video is available via: $page->video_field->url
* Module automatically creates a poster image of the video on upload and makes this available via: $page->video_field->poster
* Shows the duration of the video on the title bar, next to the filesize
* Stores SRT files for subtitles accessed via: $page->video_field->subtitles
* Formats a transcript from the subtitles, accessed via: $page->video_field->transcript

The video can be automatically rendered on the frontend with the `play()` method via the `<video>` tag. The exact settings used can be set in the module settings like this:

```
<video src='{$page->video_field->eq(1)->url}' poster='{$page->video_field->eq(1)->poster}' width='720' height='408' ><track kind='subtitles' src='{$page->video_field->eq(1)->subtitles}' srclang='en' /></video>
```

### Usage

Basic usage only requires setting up a field with this new video fieldtype. Simply upload a video and if desired enter subtitles in SRT format.

#### Additional settings

You can additionally set a few different options in the field's Input tab:
* Number of poster images to generate - if you change from the default of 1, the editing user will be able to select which image they want to use for the poster image
* Copy poster image to dedicated image field - not necessary but gives you more options of interacting with the poster image(s)
* Field that you want poster images copied into - only relevant if the option above is checked

### Requirements

The module requires ffmpeg and ffmpeg-php, although I can make the latter optional fairly easily. I don't have any requirement checking implemented yet, so if you don't have these, you'll get php errors.


### Possible future enhancements

* Multi language versions of subtitles
* Support for uploading multiple formats of the same video (mp4, webm, etc) and/or automated video format conversion
* Integrate mediaelementjs into the module so users can enter shortcodes in RTE fields to display videos where they want


### Discussion

http://processwire.com/talk/topic/4580-video-fieldtype/

## License

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

(See included LICENSE file for full license text.)
