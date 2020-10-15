<?php

/**
 * ProcessWire Video Fieldtype
 * by Adrian Jones
 *
 * Fieldtype for uploading video files and creating poster images.
 *
 * Copyright (C) 2020 by Adrian Jones
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 *
 */

class FieldtypeVideo extends FieldtypeFile implements Module, ConfigurableModule {

    public static function getModuleInfo() {
        return array(
            'title' => __('Video', __FILE__),
            'summary' => __('Fieldtype for uploading video files and creating poster images.', __FILE__),
            'version' => '0.2.0',
            'author' => 'Adrian Jones',
            'href' => 'https://processwire.com/talk/topic/4580-video-fieldtype/',
            'installs' => 'InputfieldVideo',
            'icon'     => 'file-video-o'
        );
    }


    protected static $configDefaults = array(
        'player_code' => '
<video width="{width}" height="{height}" poster="{poster}" controls="controls" preload="none">
    <source type="video/mp4" src="{url}" />
    <track kind="subtitles" src="{subtitles}" srclang="en" />
</video>
'
    );


    protected $data = array();


    /**
     * Given a raw value (value as stored in DB), return the value as it would appear in a Page object
     *
     * @param Page $page
     * @param Field $field
     * @param string|int|array $value
     * @return string|int|array|object $value
     *
     */
    public function ___wakeupValue(Page $page, Field $field, $value) {

        if($value instanceof Pagefiles) return $value;
        $pagefiles = $this->getBlankValue($page, $field);
        if(empty($value)) return $pagefiles;

        if(!is_array($value) || array_key_exists('data', $value)) $value = array($value);
        foreach($value as $v) {
            if(empty($v['data'])) continue;
            $pagefile = $this->getBlankPagefile($pagefiles, $v['data']);
            $pagefile->description = $v['description'];
            if(isset($v['modified'])) $pagefile->modified = $v['modified'];
            // $v['created'] was blank, so setting to modified to ensure video is "published"
            // this might be a bit of a hack so should revisit later
            if(isset($v['created'])) $pagefile->created = $v['modified'];
            if(isset($v['tags'])) $pagefile->tags = $v['tags'];
            if(isset($v['poster'])) $pagefile->poster = $v['poster'];
            if(isset($v['subtitles'])) $pagefile->subtitles = $v['subtitles'];
            $pagefile->setTrackChanges(true);
            $pagefiles->add($pagefile);
        }

        $pagefiles->resetTrackChanges(true);
        return $pagefiles;
    }

    /**
     * Given an 'awake' value, as set by wakeupValue, convert the value back to a basic type for storage in DB.
     *
     * @param Page $page
     * @param Field $field
     * @param string|int|array|object $value
     * @return string|int
     *
     */
    public function ___sleepValue(Page $page, Field $field, $value) {

        $sleepValue = array();
        if(!$value instanceof Pagefiles) return $sleepValue;

        foreach($value as $pagefile) {
            $item = array(
                'data' => $pagefile->basename,
                'description' => $pagefile->description,
                'poster' => $pagefile->poster,
                'subtitles' => $pagefile->subtitles,
            );

            if($field->fileSchema & self::fileSchemaDate) {
                $item['modified'] = date('Y-m-d H:i:s', $pagefile->modified);
                $item['created'] = date('Y-m-d H:i:s', $pagefile->created);
            }

            if($field->fileSchema & self::fileSchemaTags) {
                $item['tags'] = $pagefile->tags;
            }

            $sleepValue[] = $item;
        }
        return $sleepValue;
    }


    public function getBlankValue(Page $page, Field $field) {
        $pageimages = new Pageimages($page);
        $pageimages->setTrackChanges(true);
        return $pageimages;
    }


    protected function getBlankPagefile(Pagefiles $pagefiles, $filename) {
        return new Pageimage($pagefiles, $filename);
    }


    /**
     * Perform output formatting on the value delivered to the API
     *
     * Entity encode the file's description field.
     *
     * If the maxFiles setting is 1, then we format the value to dereference as single Pagefile rather than a PagefilesArray
     *
     * This method is only used when $page->outputFormatting is true.
     *
     */
    public function ___formatValue(Page $page, Field $field, $value) {

        if(!$value instanceof Pagefiles) return $value;

        foreach($value as $k => $v) {
            if($v->formatted()) continue;
            $v->description = htmlspecialchars($v->description, ENT_QUOTES, "UTF-8");
            $v->tags = htmlspecialchars($v->tags, ENT_QUOTES, "UTF-8");
            $v->poster = pathinfo($v->url, PATHINFO_DIRNAME) . '/' . $v->poster;
            $subtitles_file = str_replace('mp4', 'vtt', $v->url);

            $file_as_array = file($this->wire('config')->paths->root . $subtitles_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $v->transcript = '';
            foreach ($file_as_array as $f) {

                // Find lines containing "-->"
                $start_time = false;
                if(preg_match("/^(\d{2}:\d{2}:[\d\.]+) --> \d{2}:\d{2}:[\d\.]+$/", $f, $match)) {
                    $start_time = explode('-->', $f);
                    $start_time = $start_time[0];
                }

                // It's a line of the file that doesn't include a timestamp, so it's caption text. Ignore header of file which includes the word 'WEBVTT'
                if (!$start_time && (!strpos($f, 'WEBVTT')) ) {
                    $v->transcript .= ' ' . $f . ' ';
                }

            }

            $posterPath = $this->page->filesManager()->path() . basename($v->poster);
            if(file_exists($posterPath)) {
                list($width, $height) = getimagesize($posterPath);
            }
            else {
                $width = 480;
                $height = 320;
            }

            $search  = array('{url}', '{poster}', '{width}', '{height}', '{description}', '{subtitles}');
            $replace = array($v->url, $v->poster, $width, $height, $v->description, $subtitles_file);
            $play_code = str_replace($search, $replace, $this->player_code);
            $v->play = $play_code;
            $v->formatted = true;
        }

        if($field->maxFiles == 1) {
            if(count($value)) $value = $value->first();
                else $value = null;
        }

        return $value;
    }


    public function getDatabaseSchema(Field $field) {

        $schema = parent::getDatabaseSchema($field);

        $schema['data'] = 'varchar(255) NOT NULL';
        $schema['description'] = "text NOT NULL";
        $schema['poster'] = 'varchar(255) NOT NULL';
        $schema['subtitles'] = 'text NOT NULL';
        $schema['modified'] = "datetime";
        $schema['created'] = "datetime";
        $schema['keys']['description'] = 'FULLTEXT KEY description (description)';
        $schema['keys']['poster'] = 'index (poster)';
        $schema['keys']['modified'] = 'index (modified)';
        $schema['keys']['created'] = 'index (created)';

        $tagsAction = null; // null=no change; 1=add tags, 0=remove tags
        $schemaTags = 'varchar(255) NOT NULL';
        $schemaTagsIndex = 'FULLTEXT KEY tags (tags)';

        if($field->useTags && !($field->fileSchema & self::fileSchemaTags)) $tagsAction = 'add';
            else if(!$field->useTags && ($field->fileSchema & self::fileSchemaTags)) $tagsAction = 'remove';

        if($tagsAction === 'add') {
            // add tags field
            try {
                $this->db->query("ALTER TABLE `{$field->table}` ADD tags $schemaTags");
                $this->db->query("ALTER TABLE `{$field->table}` ADD $schemaTagsIndex");
                $field->fileSchema = $field->fileSchema | self::fileSchemaTags;
                $field->save();
                $this->message("Added tags to DB schema for '{$field->name}'");
            } catch(Exception $e) {
                $this->error("Error adding tags to '{$field->name}' schema");
            }

        } else if($tagsAction === 'remove') {
            // remove tags field
            try {
                $this->db->query("ALTER TABLE `{$field->table}` DROP INDEX tags");
                $this->db->query("ALTER TABLE `{$field->table}` DROP tags");
                $field->fileSchema = $field->fileSchema & ~self::fileSchemaTags;
                $field->save();
                $this->message("Dropped tags from DB schema for '{$field->name}'");
            } catch(Exception $e) {
                $this->error("Error dropping tags from '{$field->name}' schema");
            }
        }

        if($field->fileSchema & self::fileSchemaTags) {
            $schema['tags'] = $schemaTags;
            $schema['keys']['tags'] = $schemaTagsIndex;
        }

        return $schema;
    }


    public function getInputfield(Page $page, Field $field) {

        // even though we don't want this input field, call it anyway
        parent::getInputfield($page, $field);

        $inputfield = $this->wire('modules')->get("InputfieldVideo");
        $inputfield->class = $this->className();

        $this->setupHooks($page, $field, $inputfield);

        return $inputfield;
    }


    protected function getDefaultFileExtensions() {
        return "mp4";
    }


    /**
     * Get any inputfields used for configuration of this Fieldtype.
     *
     * This is in addition to any configuration fields supplied by the parent Inputfield.
     *
     * @param Field $field
     * @return InputfieldWrapper
     *
     */
    public function getModuleConfigInputfields(array $data) {

        foreach(self::$configDefaults as $key => $value) {
            if(!isset($data[$key]) || $data[$key]=='' || $data[$key]=='~') $data[$key] = $value;
        }

        $inputfields = new InputfieldWrapper();

        $f = $this->wire('modules')->get("InputfieldTextarea");
        $f->attr('name', 'player_code');
        $f->attr('value', $data["player_code"]);
        $f->label = __('Player code');
        $f->notes = __('Use {width}, {height}, {poster}, {url}, {subtitles} as required within the code.');
        $inputfields->add($f);

        return $inputfields;
    }

    public function ___install() {
        // save default config data on install
        $this->wire('modules')->saveModuleConfigData($this, self::$configDefaults);
    }

}