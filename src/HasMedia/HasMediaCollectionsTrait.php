<?php

namespace Brackets\Media\HasMedia;

use Brackets\Media\Exceptions\Collections\ThumbsDoesNotExists;
use Illuminate\Http\Request;
use Illuminate\Http\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait as ParentHasMediaTrait;
use Spatie\MediaLibrary\Media as MediaModel;

use Spatie\MediaLibrary\Exceptions\FileCannotBeAdded;
use Spatie\MediaLibrary\Exceptions\FileCannotBeAdded\MimeTypeNotAllowed;
use Brackets\Media\Exceptions\FileCannotBeAdded\FileIsTooBig;
use Brackets\Media\Exceptions\FileCannotBeAdded\TooManyFiles;

trait HasMediaCollectionsTrait {

    use ParentHasMediaTrait;

    /** @var  Collection */
    protected $mediaCollections;

    // TODO reconsider, if we really need to work with Collection (probably yes)
    public function processMedia(Collection $files) {
        //FIXME: check no. of db queries on average request
        $mediaCollections = $this->getMediaCollections();
        
        $this->validateCollectionMediaCount($files);

        $files->each(function($file) use ($mediaCollections) {
            $collection = $mediaCollections->filter(function($collection) use ($file){
                return $collection->name == $file['collection'];
            })->first();

            if($collection) {                
                if(isset($file['id']) && $file['id']) {
                    if(isset($file['deleted']) && $file['deleted']) {
                        if($medium = app(MediaModel::class)->find($file['id'])) {
                            $medium->delete();
                        }
                    } /* else {
                        TODO: update meta data?
                    }*/
                }
                else {
                    $metaData = [];
                    if(isset($file['name'])) {
                        $metaData['name'] = $file['name'];
                    }

                    if(isset($file['file_name'])) {
                        $metaData['file_name'] = $file['file_name'];
                    }

                    if(isset($file['width'])) {
                        $metaData['width'] = $file['width'];
                    }

                    if(isset($file['height'])) {
                        $metaData['height'] = $file['height'];
                    }

                    $this->validateSizeAndTypeOfFile(storage_path('app/'.$file['path']), $collection);

                    //FIXME: upload path from config?
                    $this->addMedia(storage_path('app/'.$file['path']))
                         ->withCustomProperties($metaData)
                         ->toMediaCollection($collection->name, $collection->disk);
                }
            }
        });
    }

   

    /**
      * Validate uploaded files count in collection
      *
      * @throws FileCannotBeAdded/TooManyFiles 
      * 
      */ 

     //FIXME: ble, upratat cele
    public function validateCollectionMediaCount(Collection $files) {
        $files->groupBy('collection')->each(function($collectionMedia, $collectionName) {
            $collection = $this->getMediaCollection($collectionName);

            if($collection->maxNumberOfFiles) {
                $alreadyUploadedCollectionMedia = $this->getMedia($collectionName)->count();

                if(($collectionMedia->count() + $alreadyUploadedCollectionMedia) > $collection->maxNumberOfFiles) {
                    throw TooManyFiles::create(($collectionMedia->count() + $alreadyUploadedCollectionMedia), $collection->maxNumberOfFiles, $collection->name); 
                }
            }
        });
    }


    /**
     * Validate uploaded files mime type and size
     *
     * @throws FileCannotBeAdded/MimeTypeNotAllowed
     * @throws FileCannotBeAdded/FileIsTooBig
     *
     */
    public function validateSizeAndTypeOfFile($filePath, $mediaCollection) {
        if($mediaCollection->acceptedFileTypes) {
            //throws FileCannotBeAdded/MimeTypeNotAllowed
            $this->guardAgainstInvalidMimeType($filePath, $mediaCollection->acceptedFileTypes);
        }

        if($mediaCollection->maxFilesize) {
            $this->guardAgainstFilesizeLimit($filePath, $mediaCollection->maxFilesize, $mediaCollection->name);
        }
    }

    //FIXME: PR do spatie? guardAgainstInvalidMimeType bol takto pridany https://github.com/spatie/laravel-medialibrary/pull/648
    protected function guardAgainstFilesizeLimit($filePath, $maxFilesize, $name) {
        $validation = Validator::make(
            ['file' => new File($filePath)],
            ['file' => 'max:'.(round($maxFilesize/1024))]
        );

        if ($validation->fails()) {
            throw FileIsTooBig::create($filePath, $maxFilesize, $name);
        }
    }

    protected function bootIfNotBooted() {
        parent::bootIfNotBooted();

        $this->initMediaCollections();
    }

    public static function bootHasMediaCollectionsTrait() {
        static::saving(function($model) {
            if($model->shouldAutoProcessMedia()) {
                $request = app(Request::class); 

                if($request->has('files')) {
                    $model->processMedia(collect($request->get('files')));
                }
            }
        });
    }

    protected function shouldAutoProcessMedia() {
        // TODO implement this method. Inspire by some Laravel package.
//        if (property_exists($this, 'autoProcessMedia') && !!$this->autoProcessMedia) {
//
//        }
        return true;
    }

    protected function initMediaCollections() {
        $this->mediaCollections = collect();

        $this->registerMediaCollections();
    }

    public function addMediaCollection($name) : \Brackets\Media\HasMedia\Collection {
        $collection = \Brackets\Media\HasMedia\Collection::create($name);

        $this->mediaCollections->push($collection);

        return $collection;
    }

    public function getMediaCollections() {
        return $this->mediaCollections;
    }

    public function getMediaCollection($collectionName) {
        $foundCollections = $this->getMediaCollections()->filter(function($collection) use ($collectionName){
            return $collection->name == $collectionName;
        });

        return $foundCollections->count() > 0 ? $foundCollections->first() : false;
    }

    public function getImageMediaCollections() {
        return $this->getMediaCollections()->filter(function($collection){
            return $collection->isImage();
        });
    }

    public function getThumbsForCollection(string $collectionName) {
        $collection = $this->getMediaCollection($collectionName);
        
        //FIXME: if image and thumb_200 doesnt exist throw exception to add thumb_200
        if($this->hasMediaConversion('thumb_200')) {
            throw ThumbsDoesNotExists::thumbsConversionNotFound();
        }

        return $this->getMedia($collectionName)->map(function($medium) use ($collection) { 
            return [ 
                'id'         => $medium->id,
                'url'        => $medium->getUrl(),
                'thumb_url'  => $collection->isImage() ? $medium->getUrl('thumb_200') : $medium->getUrl(), 
                'type'       => $medium->mime_type,
                'collection' => $collection->name,
                'name'       => $medium->hasCustomProperty('name') ? $medium->getCustomProperty('name') : $medium->file_name, 
                'size'       => $medium->size
            ];
        });
    }

    //FIXME: this definitely shouldn't be here
    public function registerComponentThumbs() {
        $this->getImageMediaCollections()->each(function($collection) {
            $this->addMediaConversion('thumb_200')
                 ->width(200)
                 ->height(200)
                 ->fit('crop', 200, 200)
                 ->optimize()
                 ->performOnCollections($collection->name);
        });
    }
}