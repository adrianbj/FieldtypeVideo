<?php namespace ProcessWire;

/**
 * ProcessWire Video Inputfieldtype
 * by Adrian Jones
 *
 * Copyright (C) 2020 by Adrian Jones
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 *
 */

use \Char0n\FFMpegPHP\Adapters\FFMpegMovie as ffmpeg_movie;

class InputfieldVideo extends InputfieldFile {

    public static function getModuleInfo() {
        return array(
            'title' => __('Video Inputfield', __FILE__),
            'summary' => __('Inputfield for uploading video files and creating poster images.', __FILE__),
            'version' => '0.2.4',
            'author' => 'Adrian Jones',
            'href' => 'https://processwire.com/talk/topic/4580-video-fieldtype/',
            'icon'     => 'file-video-o',
            'requires' => array("FieldtypeVideo")
        );
    }


    public function init() {
        parent::init();
        $this->set('extensions', 'mp4');
        $this->set('copyToImageField', 0);
        $this->set('numPosterImages', 1);
        $this->set('adminThumbs', false);
        $this->set('imageField', '');
        $this->set('maxWidth', '');
        $this->set('maxHeight', '');
        $this->set('adminThumbHeight', 100);
        $this->set('itemClass', 'InputfieldFile InputfieldImage InputfieldVideo ui-widget');
    }


    public function renderReady($parent = null, $renderValueMode = false) {

        $config = $this->wire('config');
        $modules = $this->wire('modules');
        $moduleInfo = $this->getModuleInfo();
        $version = $moduleInfo['version'];
        $moduleUrl = $config->urls->siteModules . 'FieldtypeVideo/';

        // These must be loaded during the initial renderReady pass. Repeater
        // fields preload assets before their inputfields are rendered by AJAX.
        $config->scripts->add($moduleUrl . 'image-picker/image-picker.min.js?v=' . $version);
        $config->styles->add($moduleUrl . 'image-picker/image-picker.css?v=' . $version);

        // InputfieldVideo extends InputfieldFile, so its parent assets are also
        // required. parent::renderReady() loads InputfieldVideo.js and CSS.
        $modules->loadModuleFileAssets('InputfieldFile');

        return parent::renderReady($parent, $renderValueMode);
    }


    /**
     * Create poster image(s)
     *
     */
    protected function ___fileAdded(Pagefile $pagefile) {

        if(pathinfo($pagefile->filename, PATHINFO_EXTENSION) == 'mp4') {

            if(!extension_loaded('gd')) {
                $this->error($this->_('GD extension is required to create poster images from videos.'));
                return parent::___fileAdded($pagefile);
            }

            $this->loadPhpFfmpeg();

            try {
                $media = new ffmpeg_movie($pagefile->filename);
                $frame_count = $media->getFrameCount();
            } catch(\UnexpectedValueException $e) {
                $this->error(sprintf($this->_('Video file not found: %s'), $pagefile->basename));
                return parent::___fileAdded($pagefile);
            } catch(\RuntimeException $e) {
                if($e->getCode() === 334560) {
                    $this->error($this->_('FFmpeg is not installed on the server. Install FFmpeg to enable video poster generation.'));
                } else {
                    $this->error(sprintf($this->_('Unable to read video file (%s): %s'), $pagefile->basename, $e->getMessage()));
                }
                return parent::___fileAdded($pagefile);
            } catch(\Exception $e) {
                $this->error(sprintf($this->_('Unable to read video file (%s): %s'), $pagefile->basename, $e->getMessage()));
                return parent::___fileAdded($pagefile);
            }

            $frame_number = 0;
            $frames = array();
            $fraction = (1/$this->numPosterImages);
            $frame_number = $frame_number + $fraction;
            for ($frame_number = 1; $frame_number <= $this->numPosterImages; $frame_number++) {
                $frames[] = round($frame_count * $frame_number / 10);
            }

            $i=1;
            foreach($frames as $frame) {

                $img = $this->appendFilename(htmlspecialchars(str_replace('.mp4', '.jpg', $pagefile->filename)), '-'.$i);

                if(!file_exists($img)) {
                    try {
                        $selected_frame = $media->getFrame($frame);
                        $gd_image = $selected_frame ? $selected_frame->toGDImage() : false;

                        if($gd_image) {
                            imagejpeg($gd_image, $img, 83);
                            if(PHP_VERSION_ID < 80000) imagedestroy($gd_image);
                        }
                    } catch(\Exception $e) {
                        $this->error(sprintf($this->_('Unable to extract frame %d from %s: %s'), $frame, $pagefile->basename, $e->getMessage()));
                    }
                }
                $i++;
            }
        }

        return parent::___fileAdded($pagefile);
    }


    protected function ___renderItem($pagefile, $id, $n) {

        $this->loadPhpFfmpeg();

        $sanitizer = $this->wire('sanitizer');
        $video = $pagefile;

        $duration = '';
        try {
            $media = new ffmpeg_movie($video->filename);
            $duration = gmdate("H:i:s", intval($media->getDuration()));
        } catch(\UnexpectedValueException $e) {
            $duration = '—';
            $this->warning(sprintf($this->_('Video file not found: %s'), $video->basename));
        } catch(\RuntimeException $e) {
            $duration = '—';
            if($e->getCode() === 334560) {
                $this->error($this->_('FFmpeg is not installed on the server. Video poster generation and duration display are unavailable.'));
            } else {
                $this->warning(sprintf($this->_('Unable to read video duration for %s: %s'), $video->basename, $e->getMessage()));
            }
        } catch(\Exception $e) {
            $duration = '—';
            $this->warning(sprintf($this->_('Unable to read video duration for %s: %s'), $video->basename, $e->getMessage()));
        }

        $basenameAttr = $sanitizer->entities($pagefile->basename);
        $urlAttr = $sanitizer->entities($pagefile->url);
        $displayName = $this->getDisplayBasename($pagefile); // already safe (PW-sanitized basename, plus &hellip;)
        $deleteLabel = $sanitizer->entities($this->labels['delete']);

        $out =
            "<p class='InputfieldFileInfo InputfieldItemHeader ui-state-default ui-widget-header'>" .
            wireIconMarkupFile($pagefile->basename, "fa-fw HideIfEmpty") . '&nbsp;' .
            "<a class='InputfieldFileName' title='$basenameAttr' target='_blank' href='$urlAttr'>$displayName</a> " .
            "<span class='InputfieldFileStats'>&bull; " . str_replace(' ', '&nbsp;', $sanitizer->entities($pagefile->filesizeStr)) . " &bull; " . $sanitizer->entities($duration) . "</span>
            <label class='InputfieldFileDelete'>" .
                "<input type='checkbox' name='delete_$id' value='1' title='$deleteLabel' />" .
                "<i class='fa fa-fw fa-trash'></i>" .
            "</label>";


        $out .= "\n\t\t\n\t\t<p class='InputfieldFileData ui-widget ui-widget-content'>" . ($this->numPosterImages > 1 ? "Select the image you want to use for the video poster / cover" : "") .
        "\n\t\t" . ($this->wire('user')->isSuperuser() ? 'In templates, you can access ' . ($this->numPosterImages > 1 ? "the selected" : "this") . ' poster image ('.$sanitizer->entities(pathinfo(str_replace('mp4', 'jpg', $pagefile->filename), PATHINFO_BASENAME)).') using: <code>$page->'.$sanitizer->entities($this->name).'->'.($this->maxFiles == 1 ? '' : 'eq('.(int)$n.')->').'poster</code>' : '') . '<br /><br />';

        $multiplePosters = $this->numPosterImages > 1;
        if($multiplePosters) $out .= '<select id="poster_'.$id.'" name="poster_'.$id.'" class="required image-picker show-labels show-html">';

        for ($i = 1; $i <= $this->numPosterImages; $i++) {

            $poster_path = $this->appendFilename(str_replace('mp4', 'jpg', $video->filename), '-'.$i);
            $poster_url = $this->appendFilename(str_replace('mp4', 'jpg', $video->url),  '-'.$i);

            $thumb_path = $this->appendFilename($poster_path, '-thumb');
            $thumb_url = $this->appendFilename($poster_url, '-thumb');

            $imgInfo = file_exists($poster_path) ? @getimagesize($poster_path) : false;
            if($imgInfo === false) {
                // poster image not yet generated or unreadable - skip
                continue;
            }
            list($width, $height) = $imgInfo;

            if($this->adminThumbs && $height > $this->adminThumbHeight && !file_exists($thumb_path)) {
                // create a variation for display with this inputfield
                $this->make_thumb($poster_path, $thumb_path, $this->adminThumbHeight);
            }

            if($this->copyToImageField == 1 && $this->imageField != '') { //if checked and images field supplied in field's Input tab settings, add this image to the defined images field
                $original_image = $this->appendFilename(str_replace('mp4', 'jpg', $video->filename),  '-'.$i);
                $image_field_version = $this->appendFilename($this->appendFilename(str_replace('mp4', 'jpg', $video->filename),  '-'.$i), '-imagefield');
                copy($original_image, $image_field_version);
                $pageId = $this->wire('input')->get->int('id');
                $current_page = $this->wire('pages')->get($pageId);
                if($current_page->id) {
                    $current_page->{$this->imageField}->add($image_field_version);
                    $current_page->save();
                }
            }

            $displayUrl = $sanitizer->entities(($this->adminThumbs && ($height > $this->adminThumbHeight)) ? $thumb_url : $poster_url);
            $posterBase = $sanitizer->entities(pathinfo($poster_path, PATHINFO_BASENAME));

            if($multiplePosters) {
                $selected = ($pagefile->poster == pathinfo($poster_path, PATHINFO_BASENAME)) ? " selected" : "";
                $out .= "<option data-img-src='$displayUrl' id='poster_$id' value='$posterBase'$selected></option>";
            } else {
                $out .= "<input type='hidden' name='poster_$id' id='poster_$id' value='$posterBase' />"
                     . "<img class='image_picker_image' src='$displayUrl' alt='$basenameAttr' />";
            }

        }

        if($multiplePosters) $out .= '</select>';

        $subtitles = $sanitizer->entities($pagefile->subtitles);
        $vttBase = $sanitizer->entities(pathinfo(str_replace('mp4', 'vtt', $pagefile->filename), PATHINFO_BASENAME));
        $fieldName = $sanitizer->entities($this->name);
        $eqSegment = $this->maxFiles == 1 ? '' : 'eq('.(int)$n.')->';

        $out .= "\n\t\t\t" . $this->renderItemDescriptionField($pagefile, $id, $n) .
        "\n\t\t<br /><label class='InputfieldFileDescription'><span class='detail'>Subtitles</span>" .
        "\n\t\t" . ($this->wire('user')->isSuperuser() ? '<br /><br />In templates, you can access this subtitles file ('.$vttBase.') using: <code>$page->'.$fieldName.'->'.$eqSegment.'subtitles</code>' : '') .
        '<br />In templates you can access a formatted transcript (converted from subtitles entered in VTT format), by using: <code>$page->'.$fieldName.'->'.$eqSegment.'transcript</code>' .
        "\n\t\t<br /><br /><textarea rows='10' name='subtitles_$id'>$subtitles</textarea>" .
        "\n\t\t\t<input class='InputfieldFileSort' type='text' name='sort_$id' value='".(int)$n."' />" .
        "\n\t\t</p>";

        return $out;
    }


	/**
	 * Get a basename for the file, possibly shortened, suitable for display in InputfieldFileList
	 *
	 * @param Pagefile $pagefile
	 * @param int $maxLength
	 * @return string
	 *
	 */
	public function getDisplayBasename(Pagefile $pagefile, $maxLength = 25) {
		$displayName = $pagefile->basename;
		if($this->noShortName) return $displayName;
		if(strlen($displayName) > $maxLength) {
			$ext = ".$pagefile->ext";
			$maxLength -= (strlen($ext) + 1);
			$displayName = basename($displayName, $ext);
			$displayName = substr($displayName, 0, $maxLength);
			$displayName .= "&hellip;" . ltrim($ext, '.');
		}
		return $displayName;
	}


    protected function ___processInputFile(WireInputData $input, Pagefile $pagefile, $n) {

        $id = $this->name . '_' . $pagefile->hash;
        $changed = false;

        foreach(array('description', 'tags', 'poster', 'subtitles') as $key) {
            if(isset($input[$key . '_' . $id])) {
                $value = trim($input[$key . '_' . $id]);
                if($value != $pagefile->$key) {
                    $pagefile->$key = $value;
                    $changed = true;
                }
            }
        }

        // write subtitles to a file that can be used by video player - currently only supporting vtt. Should make format optional, as well as add multi-language files
        if($pagefile->subtitles != '') {
            $vttPath = str_replace('mp4', 'vtt', $pagefile->filename);
            if(@file_put_contents($vttPath, $pagefile->subtitles) === false) {
                $this->error(sprintf($this->_('Unable to write subtitles file: %s'), basename($vttPath)));
            }
        }

        if(isset($input['delete_' . $id])) {
            $this->processInputDeleteFile($pagefile);
            $changed = true;
        }

        $key = "sort_$id";
        $val = (int) $input->$key;
        if($val !== NULL) {
            $pagefile->sort = $val;
            if($n !== $val) $changed = true;
        }

        return $changed;
    }


    private function loadPhpFfmpeg() {
        static $registered = false;
        if($registered) return;

        $baseDir = __DIR__ . '/ffmpeg-php/';
        spl_autoload_register(function($class) use ($baseDir) {
            $prefix = 'Char0n\\FFMpegPHP\\';
            if(strpos($class, $prefix) !== 0) return;
            $relative = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
            if(is_file($file)) require_once $file;
        });

        $registered = true;
    }


    public function ___getConfigInputfields() {

        $inputfields = parent::___getConfigInputfields();

        $field = $this->wire('modules')->get('InputfieldCheckbox');
        $field->attr('name', 'adminThumbs');
        $field->attr('value', 1);
        $field->attr('checked', $this->adminThumbs ? 'checked' : '');
        $field->label = $this->_('Display thumbnails in page editor?');
        $field->description = $this->_('Thumbnails take up less space and make it easier to sort multiple images. If unchecked, the full (original) size image will be shown in the page editor.'); // Display thumbnails description
        $inputfields->add($field);

        $field = $this->wire('modules')->get("InputfieldText");
        $field->attr('name', 'numPosterImages');
        $field->attr('value', $this->numPosterImages);
        $field->label = $this->_("Number of poster images to generate");
        $field->description = $this->_('Images will be captured from throughout the video. This determines how many will be created. The user can choose which one is available to templates via $page->'.$this->name.'->poster');
        $field->notes = $this->_("It is not recommended to make more than 10 poster images.");
        $inputfields->add($field);

        $field = $this->wire('modules')->get('InputfieldCheckbox');
        $field->attr('name', 'copyToImageField');
        $field->attr('value', 0);
        $field->attr('checked', $this->copyToImageField ? 'checked' : '');
        $field->label = $this->_('Copy poster image to dedicated image field?');
        $field->description = $this->_('This will create a copy of the poster images in an image field of your choice. NB This is not necessary for accessing the image.');
        $inputfields->add($field);

        $field = $this->wire('modules')->get("InputfieldSelect");
        $field->attr('name', 'imageField');
        $field->attr('value', $this->imageField);
        $field->required = true;
        $field->showIf="copyToImageField=1";
        $field->requiredIf="copyToImageField=1";
        $field->label = $this->_("Field that you want to have the poster image copied into");
        $field->addOption('');
        foreach($this->wire('fields') as $fieldoption) {
            if($fieldoption->type == "FieldtypeImage") $field->addOption($fieldoption->name);
        }
        $inputfields->add($field);

        $fieldset = $this->wire('modules')->get('InputfieldFieldset');
        $fieldset->label = $this->_("Max Image Dimensions");
        $fieldset->collapsed = $this->maxWidth || $this->maxHeight ? Inputfield::collapsedNo : Inputfield::collapsedYes;
        $fieldset->description = $this->_("Optionally enter the max width and/or height of uploaded images. If specified, images will be resized at upload time when they exceed either the max width or height. The resize is performed at upload time, and thus does not affect any images in the system, or images added via the API."); // Max image dimensions description

        $field = $this->wire('modules')->get("InputfieldInteger");
        $field->attr('name', 'maxWidth');
        $field->attr('value', $this->maxWidth ? (int) $this->maxWidth : '');
        $field->label = $this->_("Max width for uploaded images");
        $field->description = $this->_("Enter the value in number of pixels or leave blank for no max.");
        $fieldset->add($field);

        $field = $this->wire('modules')->get("InputfieldInteger");
        $field->attr('name', 'maxHeight');
        $field->attr('value', $this->maxHeight ? (int) $this->maxHeight : '');
        $field->label = $this->_("Max height for uploaded images");
        $field->description = $this->_("Enter the value in number of pixels or leave blank for no max.");
        $fieldset->add($field);

        $inputfields->add($fieldset);

        return $inputfields;
    }


    public function make_thumb($src, $dest, $desired_height) {

        if(!extension_loaded('gd')) return false;

        $source_image = @imagecreatefromjpeg($src);
        if(!$source_image) return false;

        $width = imagesx($source_image);
        $height = imagesy($source_image);

        $desired_width = floor($width * ($desired_height / $height));

        $virtual_image = imagecreatetruecolor($desired_width, $desired_height);
        if(!$virtual_image) {
            if(PHP_VERSION_ID < 80000) imagedestroy($source_image);
            return false;
        }

        imagecopyresampled($virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height);
        $result = imagejpeg($virtual_image, $dest, 83);

        if(PHP_VERSION_ID < 80000) {
            imagedestroy($source_image);
            imagedestroy($virtual_image);
        }

        return $result;
    }


    public function appendFilename($file, $suffix) {
        $path_parts = pathinfo($file);
        return $path_parts['dirname'] . '/' . $path_parts['filename'] . $suffix . '.' . $path_parts['extension'];
    }

}
