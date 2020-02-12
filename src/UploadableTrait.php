<?php

declare(strict_types=1);

namespace Jvizcaya\Uploadable;

use Exception;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Eloquent Model Trait for storage base64 encoded file
 *
 * @author Jorge Vizcaya <jorgevizcayaa@gmail.com>
 *
 * @property array $this->uploadable model storage array configuration
 * @property array $this->attributes model attributes
 *
 */
trait UploadableTrait
{
		/**
		* @var array
		*/
		protected $storage_config = [];

    /**
     * Storage the base64 file
     *
     * @param  string $base64_file 	base64 file
     * @param  string $table_column table column name (optional)
     * @param  string $file_name    save file name as (optional)
     * @param  string $folder      	folder to be saved (optional)
     * @throws Exception
     * @return null
     */
		public function storageFile(string $base64_file = null, string $table_column = null, string $file_name = null, string $folder = null)
		{
          try{

            if($base64_file)
            {
                $this->storage_config = $this->setStorageConfig($base64_file, $table_column, $file_name, $folder);

    					  $this->_storage($base64_file);
            }

          }catch (Exception $e) {
            echo $e->getMessage();
            die();
          }

    }

		/**
		 * Move File to another folder
		 *
		 * @param  string $to_folder    new folder
		 * @param  string $table_column table column name (optional)
		 * @param  string $folder       current_folder (optional)
		 * @throws Exception
		 * @return null
		 */
		public function moveFile(string $to_folder, string $table_column = null, string $folder = null)
		{
          try{

            $this->storage_config = $this->setStorageConfig(null, $table_column, null, $folder);

            if(! $to_folder){
              throw new Exception('Missing the new folder ubication.');
            }

  					Storage::move($this->storage_config['folder'].'/'.$this->attributes[$this->storage_config['table_column']], $to_folder.'/'.$this->attributes[$this->storage_config['table_column']]);

          }catch (Exception $e) {
            echo $e->getMessage();
            die();
          }
		}

		/**
		 * Delete file
		 *
		 * @param  string $table_column table column name (optional)
		 * @param  string $folder       folder(optional)
		 * @throws Exception
		 * @return null
		 */
		public function deleteFile(string $table_column = null, string $folder = null)
		{
          try{

              $this->storage_config = $this->setStorageConfig(null, $table_column, null, $folder);

    					Storage::delete($this->storage_config['folder'].'/'.$this->attributes[$this->storage_config['table_column']]);

    					if($this->storage_config['thumbnail']){
    							 $this->deleteThumbnails();
    					}

          }catch (Exception $e) {
            echo $e->getMessage();
            die();
          }
		}

		/**
		 * Delete all associates files
		 *
		 * @property array $this->uploadable model storage array configuration
		 * @throws Exception
		 * @return null
		 */
		public function deleteAllFiles()
		{
          try{

              foreach ($this->uploadable as $key => $value)
    					{
    						  $this->storage_config = $this->setStorageConfig(null, $key, null, $value['folder']);

    							Storage::delete($this->storage_config['folder'].'/'.$this->attributes[$this->storage_config['table_column']]);

    							if($this->storage_config['thumbnail']){
    									 $this->deleteThumbnails();
    							}

    					}

          }catch (Exception $e) {
            echo $e->getMessage();
            die();
          }

		}

		/**
		 * Set storage config
		 *
		 * @property array $this->uploadable model storage array configuration
		 * @param  string $base64_file 	base64 file (optional)
		 * @param  string $table_column table column name (optional)
		 * @param  string $file_name    save file name as (optional)
		 * @param  string $folder      	folder to be saved (optional)
		 * @return array
		 */
		private function setStorageConfig($base64_file = null, $table_column = null, $file_name = null, $folder = null): array
		{
        $this->checkRequiredProperty();

        $config_table_column = $table_column && $this->isValidKey($table_column) ? $table_column : array_keys($this->uploadable)[0];

				$this->setDefaultDisk($config_table_column);

				$config_folder = $folder ?: $this->isFolderKey($config_table_column);

				$file_extension = $base64_file && $this->is_base64($base64_file) ? $this->getFileExtension($base64_file) : '';

				$file_name = $base64_file && $this->is_base64($base64_file) ? $this->getFileName($config_table_column, $config_folder, $file_extension, $file_name) : '';

				$thumbnail = $this->uploadable[$config_table_column]['thumbnail'] ?? '';

				return ['table_column' => $config_table_column, 'folder' => $config_folder, 'file_name' => $file_name, 'thumbnail' => $thumbnail];

		}

    /**
     * Check for a valid $uploadable var
     *
     *  @property array $this->uploadable model storage array configuration
     *  @throws Exception
     */
    private function checkRequiredProperty()
    {
        if(! isset($this->uploadable)){
            throw new Exception('Missing the $uploadable variable.');
        }elseif(! is_array($this->uploadable)){
            throw new Exception('The $uploadable variable must be a array.');
        }elseif(empty($this->uploadable)){
            throw new Exception('The $uploadable variable must contain at least one storage configuration.');
        }
    }

    /**
		 *  Return true if is present in the $uploadable var or throw an exception
		 *
		 * @param  string $key the key name
		 * @throws Exception
		 * @return bool
		 */
		private function isValidKey($key): bool
		{
        if(array_key_exists($key, $this->uploadable)){
          return true;
        }else{
          throw new Exception("Missing configuration for $key.");
        }
		}

    /**
		 *  Return the folder name if is present in the $uploadable var or throw an exception
		 *
		 * @param  string $key the key name
		 * @throws Exception
		 * @return bool
		 */
		private function isFolderKey($key): string
		{
        if(isset($this->uploadable[$key]['folder']) && $this->uploadable[$key]['folder'])
        {
          return $this->uploadable[$key]['folder'];
        }else{
          throw new Exception("Missing value for folder");
        }
		}

		/**
		 * Set default disk driver for fylesystems
		 *
		 * @property array $this->uploadable model storage array configuration
		 * @param  string $table_column table column name
		 * @return null
		 */
		 private function setDefaultDisk($table_column)
		 {
					$default_filesystem_disk = isset($this->uploadable[$table_column]['disk']) ? $this->uploadable[$table_column]['disk'] : 'public';

					config(['filesystems.default' => $default_filesystem_disk]);
		 }

		/**
		 * Save file on disk
		 *
		 * @param  string $base64_file the base64 data
		 * @return null
		 */
		private function _storage($base64_file)
		{
				$this->deleteOldfile();

				$file_format = $this->fileFormat($base64_file);

				if($this->is_image($base64_file))
				{
						Storage::put($this->storage_config['folder'].'/'.$this->storage_config['file_name'], $file_format);

						if($this->storage_config['thumbnail']){
								$this->createThumbnails($file_format);
						}

				}else{
						Storage::put($this->storage_config['folder'].'/'.$this->storage_config['file_name'], $file_format);
				}

				$this->updateModel();
		}

		/**
		 *	Create image thumbnails
		 *
		 * @param string $file_format base64 formatted data
		 * @return null
		 */
		private function createThumbnails($file_format)
		{

				$thumbnails_config = $this->is_multiarray($this->storage_config['thumbnail']) ?: $this->getMultiarray($this->storage_config['thumbnail']);

				foreach ($thumbnails_config as $thumbnail_config)
				{
						$width  = isset($thumbnail_config['size'][0]) ? $thumbnail_config['size'][0] : 150;
						$height = isset($thumbnail_config['size'][1]) ? $thumbnail_config['size'][1] : 150;

						Image::make($file_format)->resize($width, $height)->save(Storage::path($thumbnail_config['folder'].'/'.$this->storage_config['file_name']), 60);
				}

		}

		/**
		 * Delete old file if is different current file name
		 *
		 * @property array $this->storage_config
		 * @property array $this->attributes model attributes
		 *
		 **/
		private function deleteOldFile()
		{
					if(isset($this->attributes[$this->storage_config['table_column']]) && $this->attributes[$this->storage_config['table_column']] !== $this->storage_config['file_name'])
					{
							 Storage::delete($this->storage_config['folder'].'/'.$this->attributes[$this->storage_config['table_column']]);

							 if($this->storage_config['thumbnail']){
								 		$this->deleteThumbnails();
							 }

					}

		}

		/**
		 * Delete thumbnails
		 *
		 * @property array $this->storage_config
		 *
		 **/
		private function deleteThumbnails()
		{
					$thumbnails_config = $this->is_multiarray($this->storage_config['thumbnail']) ?: $this->getMultiarray($this->storage_config['thumbnail']);

					foreach ($thumbnails_config as $thumbnail_config){
							Storage::delete($thumbnail_config['folder'].'/'.$this->attributes[$this->storage_config['table_column']]);
					}
		}

		/**
		 * Return file extension
		 *
		 * @param  string $base64_file the base64 data
		 * @throws Exception
		 * @return string file extension
		 */
		private function getFileExtension($base64_file): string
	 	{
			 $mime_type = explode(';', $base64_file)[0];

			 switch ($mime_type) {
				 case 'data:application/pdf':
					 return ".pdf";
				 break;
				 case 'data:image/gif':
					 return ".gif";
				 break;
				 case 'data:image/jpeg':
					 return ".jpg";
				 break;
				 case 'data:image/png':
					 return ".png";
				 break;
				 case 'data:image/webp':
					 return ".webp";
				 break;
				 case 'data:image/x-icon':
					 return ".ico";
				 break;
				 case 'data:video/mp4':
					 return ".mp4";
				 break;
				 case 'data:video/mpeg':
					 return ".mpeg";
				 break;
         default:
            throw new Exception("The MIME type is not supported!");
         break;
			}
	 	}

		/**
		 * Return the file name
		 *
		 * @param  string $table_column 	table column name
		 * @param  string $file_extension the file extension
		 * @param  string $folder         folder to be saved
		 * @param  string $file_name      the file name (optionl)
		 * @return string
		 */
		private function getFileName($table_column, $folder, $file_extension, $file_name = null): string
		{
				$name = isset($this->uploadable[$table_column]['name_column']) && ! $file_name ? $this->attributes[$this->uploadable[$table_column]['name_column']] : $file_name;

				$file_name =  $name ? Str::slug($name, '_') : now()->format('YmdHi');

				$file_name = $file_name.$file_extension;

				return isset($this->attributes[$table_column]) && $this->attributes[$table_column] == $file_name ? $file_name : $this->uniqueFileName($file_name, $folder);

		}

		/**
		 * Return unique file name to avoid overwrite on disk
		 * this function adds a number to the name of the file if it exists on disk
		 *
		 * @param  string $file_name the file_name
		 * @param  string $folder 	folder to be saved
		 * @return string
		 */
		private function uniqueFileName($file_name, $folder): string
		{
					if(! $this->getFileExist($file_name, $folder)){
						return $file_name;
						exit;
					}else{

						for($i = 1; $i <= 10000; $i ++)
						{
							if(! $this->getFileExist($i.'_'.$file_name, $folder)){
								return $i.'_'.$file_name;
								exit;
							}
						}

					}
		}

		/**
		 * Return if file exist on disk
		 *
		 * @param  string $file_name the file name
		 * @param  string $folder    folder to be saved
		 * @return bool
		 */
		private function getFileExist($file_name, $folder): bool
		{
				return Storage::exists($folder.'/'.$file_name);
		}

		/**
		 * Return the base64 valid format to be saved
		 *
		 * @param  [type] $file [description]
		 * @return string
		 */
		private function fileFormat($base64_file): string
		{
				return base64_decode(explode(',', $base64_file)[1]);
		}

		/**
		 * Return true if file is a valid base64 data
		 *
		 * @param  string $base64_file 	base64 file
		 * @throws Exception
		 * @return boolean
		 */
		private function is_base64($base64_file): bool
		{
        if(strpos($base64_file, "base64") !== false){
          return true;
        }else{
          throw new Exception("File is not a valid base64 encoded data");
        }

		}

		/**
		 * Return if is image the base64
		 *
		 * @param  string  $base64_file the base64 data
		 * @return boolean
		 */
		private function is_image($base64_file): bool
		{
				return strpos($base64_file, "data:image") !== false ? true : false;
		}

		/**
		 * Return if array is multiarray
		 *
		 * @param  array  $array
		 * @return array|boolean
		 */
		private function is_multiarray($array)
		{
				return key($array) === 0 ? $array : false;

		}

		/**
		 * Return array as multiarray
		 *
		 * @param  array 	$array;
		 * @return array 	multiarray
		 */
		private function getMultiarray($array): array
		{
				$multiarray = [];

				array_push($multiarray, $array);

				return $multiarray;
		}

		/**
		 * Update table column with $this->storage_config['file_name'] attribute
		 *
		 * @property array $this->storage_config
		 * @property array $this->attributes model attributes
		 */
		private function updateModel()
		{
        $this->attributes[$this->storage_config['table_column']] = $this->storage_config['file_name'];

				$this->timestamps = false;

				$this->update();
		}


}
