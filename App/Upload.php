<?php

	namespace App;

	/**
	 * Uploader Class
	 */
	class Upload
	{
		/**
		 * Path for uploaded images
		 */
		const PATH_IMAGES = '/public/media/images/';

		/**
		 * Directory mode for new created folders
		 */
		const DIR_ACCESS_MODE = 0770;

		/**
		 * Max file size in bytes
		 * Default 10MB
		 */
		const MAX_FILE_SIZE = 10485760;

		/**
		 * Allowed file formats
		 * Default JPG/JPEG, PNG, GIF
		 */
		const ALLOWED_MIME_TYPES = [
	        'image/jpeg',
	        'image/png',
	        'image/gif',
	    ];

	    /* Subfolder in images folder */
	    public $subfolder = '';

	    public $name = '';

	    public $type = '';

	    public $tmp_name = '';

	    public $error = '';

	    public $size = '';


	    /**
         * Class constructor
         *
         * @param array $data - Initial property values
         *
         * @return void
         */
        public function __construct(array $data = [])
        {
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }


	    /**
	     * Upload a file function
	     *
	     * @return bool|string true on success, or an error string on failure.
	     */
	    public function upload()
	    {
	        /*
	         * Validate the file error. The actual upload takes place when the file
	         * error is UPLOAD_ERR_OK (the first case in this switch statement).
	         *
	         * @link https://secure.php.net/manual/en/features.file-upload.errors.php Error Messages Explained.
	         */
	        switch ($this->error)
	        {
	            case UPLOAD_ERR_OK:

	            	/* There is no error, the file can be uploaded. */
	                if ($this->size > self::MAX_FILE_SIZE) {
	                    return sprintf('The size of the file "%s" exceeds the maximal allowed size (%s Byte).', $this->name, self::MAX_FILE_SIZE);
	                }

	                if (! in_array($this->type, self::ALLOWED_MIME_TYPES)) {
	                    return sprintf('The file "%s" is not of a valid MIME type. Allowed MIME types: %s.', $this->name, implode(', ', self::ALLOWED_MIME_TYPES));
	                }

	                $uploadDirPath = $_SERVER['DOCUMENT_ROOT'].rtrim(self::PATH_IMAGES, '/');
	                $uploadPath = $uploadDirPath . '/' . $this->subfolder . $this->name;

	                $this->createDirectory($uploadDirPath);

	                if (! move_uploaded_file($this->tmp_name, $uploadPath)) {
	                    return sprintf('The file "%s" could not be moved to the specified location.', $this->name);
	                }

	                return true;

	                break;

	            case UPLOAD_ERR_INI_SIZE:
	            case UPLOAD_ERR_FORM_SIZE:

	                return sprintf('The provided file "%s" exceeds the allowed file size.', $this->name);
	                break;

	            case UPLOAD_ERR_PARTIAL:

	                return sprintf('The provided file "%s" was only partially uploaded.', $this->name);
	                break;

	            case UPLOAD_ERR_NO_FILE:

	                return 'No file provided. Please select at least one file.';
	                break;

	            default:

	                return 'There was a problem with the upload. Please try again.';
	                break;
	        }

	        return true;
	    }


	    /**
	     * Create a directory at the specified path.
	     *
	     * @param string $path - Directory path.
	     *
	     * @return $this
	     */
	    private function createDirectory(string $path)
	    {
	        try {

	            if (file_exists($path) && ! is_dir($path)) {
	                throw new UnexpectedValueException(
	                	'The upload directory can not be created because '
	                	. 'a file having the same name already exists!'
	                );
	            }

	        } catch (Exception $exc) {
	            echo $exc->getMessage();
	            exit();
	        }

	        if (! is_dir($path)) {
	            mkdir($path, self::DIR_ACCESS_MODE, true);
	        }

	        return $this;
	    }


	    /**
	     * Function which set timestamp as image name
	     *
	     * @param string $title - Image title
	     *
	     * @return void
	     */
	    public function setImageTitle(string $title)
	    {
	    	$this->setTitle = $title;
	    }


	    /**
	     * Function which return image extension
	     * Works only if one file is uploaded (e.g. Topic image, User profile image)
	     *
	     * @return string - Image extension
	     */
	    public function getImageExtension()
	    {
	    	return pathinfo($_FILES['tmp_name'], PATHINFO_EXTENSION);
	    }


	    /**
	     * Function which return random title for file
	     * based on timestamp with microseconds
	     * 
	     * @param string $ext = file extension
	     *
	     * @return string - random file name with extension
	     */
	    public function getRandomFilename(string $ext)
	    {
	    	$m = microtime(true);
			return sprintf("%8x%05x",floor($m),($m-floor($m))*1000000).'.'.$ext;
	    }
	}