# Uploadable (Laravel Eloquent trait for storage base64 encoded data)

**Uploadable** Es un trait para Laravel 5.7+ que agrega funcionalidades de almacenamiento de archivos a nuestros modelos, cuando trabajamos con datos previamente **codificados en base64**.

Al estar relacionado con el modelo, solo debemos especificar la columna en la tabla (base de dato) en el cual deseamos que se guarde el nombre del archivo almacenado en disco.

**La columna donde se guarda el nombre del archivo debe permitir contener valores nulos ya que la funciones del trait actúan luego de los métodos save() o update() del modelo.**

## Instalación

```
composer require jvizcaya/uploadable
```

## Modo de uso

Agrega el **Trait** `Jvizcaya\Uploadable\UploadableTrait`al modelo y configura la variable `protected $uploadable` con las reglas de almacenamiento.

```php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Jvizcaya\Uploadable\UploadableTrait;

class Post extends Model
{
    use UploadableTrait;
    /**
     * Uploadable rules.
     *
     * @var array
     */
     protected $uploadable = [
       'image' => ['folder' => 'posts']
     ];
}
```
En el ejemplo anterior hemos definido que el archivo se almacenara en la carpeta `posts` y el nombre del archivo en disco se guardara en tabla en la columna `image`.

Luego de agregar el trait al modelo y configurar correctamente la variable `protected $uploadable` podemos hacer uso de las funcion de almacenamiento `storageFile()`.

```php
namespace App\Http\Controllers;

use App\Post;

class PostController extends Controller
{
    public function store(Request $request)
    {
        $post = new Post($request->all());
        $post->save();

        $post->storageFile($request->image);
    }
}
```
En el ejemplo anterior `$request->image` es un archivo de imagen codificado en base64 que hemos pasado al controlador desde la vista.


> **storageFile()** Hace uso del paquete [intervention/image](https://github.com/Intervention/image) para el almacenamiento de archivos de imágenes, asegúrese que el sistema cumpla con los requerimientos de este paquete.

### Columna de la tabla

Por defecto se tomara el primer elemento del array de la variable `$uploadable` como el criterio sobre aplicar las reglas de almacenamiento, podemos especificar la columna de manera dinamica, pasándola como el segundo parámetro (**Útil si tenemos varios campos de almacenamiento en la misma tabla y por tal motivo diferentes reglas**).

```php
$post->storageFile($request->image, 'image');
```


### Nombre de archivo

Podemos hacer que el nombre del archivo sea tomado automaticamente desde otra de las columnas del modelo, configurando la regla `name_column`.

```php

  use UploadableTrait;
  /**
   * Uploadable rules.
   *
   * @var array
   */
   protected $uploadable = [
     'image' => [
         'folder' => 'posts',
         'name_column' => 'title'
       ]
   ];

```
 En el ejemplo anterior el archivo se almacenara con el valor contenido en la columna `title` del modelo.

 También podemos especificar el nombre del archivo, enviándolo como el tercer parámetro de la función **storageFile()**.

 ```php
 $post->storageFile($request->image, 'image', 'file name');
 ```

 En ambos casos el nombre especificado sera formateado, por ejemplo `nombre de archivo` sera cambiado a `nombre_de_archivo`.

 Si no se especifica el nombre del archivo empleando algunos de los métodos anteriores tomara el valor por defecto el cual es la fecha de almacenamiento en formato `YmdHi`.

 ### Nombre de carpeta

 El nombre de la carpeta en el cual se almacenara el archivo en disco se debe especificar en la regla `folder` (ver ejemplos), pero si se desea pasar el nombre de la carpeta de almacenamiento de manera dinámica, podemos pasarla como el cuarto parámetro de la función `storageFile()`.

 ```php
 $post->storageFile($request->image, 'image', 'file name', 'folder_name');
 ```

### Thumbnails

Cuando el archivo que deseamos almacenar **es una imagen** podemos generar miniaturas o replicas en varios tamaños, para esto debemos simplemente configurar la regla `thumbnail` en la variable `$uploadable`.

```php

  use UploadableTrait;
  /**
   * Uploadable rules.
   *
   * @var array
   */
   protected $uploadable = [
     'image' => [
         'folder' => 'posts',
         'name_column' => 'title',
         'thumbnail' => ['folder' => 'posts/thumbnails', 'size' => [150, 100]],
        ]
   ];

```

En el ejemplo anterior hemos especificado, que se deberá generar una replica (miniatura) de nuestro archivo de imagen al momento de ser almacenado.

Esta imagen deberá ser guardada en la carpeta `thumbnails` dentro de `posts`, y se guardara con una dimensión de 150px de ancho y 100px de altura.

Si no se especifica la regla `size` para `thumbnails`, la imagen se guardara por defecto con las dimensiones de 150px de ancho y 150px de altura.

### Multiples Thumbnails

En ocasiones deseamos generar múltiples archivos de diferentes tamaños, para este caso basta con agrupar cada regla para cada imagen que deseamos generar como un elemento dentro del array para la regla `thumbnail` de la siguiente manera:

```php

  use UploadableTrait;  
  /**
   * Uploadable rules.
   *
   * @var array
   */    
   protected $uploadable = [
     'image' => [
         'folder' => 'posts',
         'name_column' => 'title',
         'thumbnail' => [
           ['folder' => 'posts/150', 'size' => [150, 100]],
           ['folder' => 'posts/400', 'size' => [400, 300]]
         ],
        ]
   ];

```

En el ejemplo anterior se crearan dos imágenes, en los directorios `posts/150` y `posts/400` respectivamente.

### Nombre de disco (Fylesystem)

**Uploadable** hace uso del sistema de almacenamiento de Laravel ([File Storage](https://laravel.com/docs/5.8/filesystem)), por lo tanto se espera que los directorios esten ubicados en `storage/app`.

Por defecto el disco utilizado es `public`, si deseamos configurar el uso de otro disco podemos hacerlo especificando la regla `disk` de esta manera:

```php

  use UploadableTrait;
  /**
   * Uploadable rules.
   *
   * @var array
   */
   protected $uploadable = [
      'image' => [
         'folder' => 'posts',
         'name_column' => 'title',
         'thumbnail' => [
           ['folder' => 'posts/150', 'size' => [150, 100]],
           ['folder' => 'posts/400', 'size' => [400, 300]]
         ],
         'disk' => 'public'
      ],
      'photo' => [
          'folder' => 'photos',
          'name_column' => 'title',
          'disk' => 'local'
      ]
   ];

```

Actualmente solo se garantiza compatibilidad con el uso de discos locales, no se ha probado haciendo uso de `s3` u otros métodos en la nube.


## Borrar archivos

Para borrar los archivos almacenados asociados al modelo, podemos emplear la función `deleteAllFiles()`, esta función recorrerá cada una de las entradas en la variable `$uploadable` y eliminara cada archivo del disco.

Para que funcione correctamente cada regla deberá estar especificada correctamente, sobretodo la regla para el valor `folder`.

```php
$post->deleteAllFiles();
```
Si no se puede emplear el método anterior o si desea eliminar un archivo en especifico podemos hacer uso del metodo `deleteFile()`, este método acepta como parámetro el nombre de la columna a borrar.

```php
$post->deleteFile('photo');
```
`deleteFile()`, También acepta de manera opcional como segundo parámetro el nombre de la carpeta donde esta ubicado el archivo.

```php
$post->deleteFile('photo', 'photos');
```
**Nota:** Cuando se emplea `deleteAllFiles()`, o `deleteFile()`, Si el archivo borrado es una imagen, y este tiene configurado la regla `thumbnail`, se eliminaran de igual manera de forma automáticamente las imágenes generadas como miniaturas.

## Mover Archivos (Beta)

Si se desea cambiar la ubicación de un archivo almacenado dentro del sistema de almacenamiento del Framework (FyleSystem), podemos hacer uso de la función `moveFile()`, esta opción deberá tener como parámetro el nombre del directorio de la nueva ubicación.

```php
$post->moveFile('images');
```
También podemos pasar como segundo parámetro el nombre de la columna, y como tercer parámetro el nombre de la carpeta donde esta ubicado actualmente el archivo que sera trasladado de ubicación.

```php
$post->moveFile('images', 'image', 'posts');
```

### Notas

Actualmente la función `storageFile()` tiene soporte para los siguientes tipos de archivos según su **MIME**, los cuales ya han sido probados, se agregaran mas tipos de archivos según se vayan probando su estabilidad al momento de usarlos.

|Tipo MIME        |
|-----------------|
|application/pdf  |
|image/gif        |
|image/jpeg       |
|image/png        |
|image/webp       |
|image/x-icon     |
|video/mp4        |
|video/mpeg       |

---

## License

[MIT](LICENSE) © Jorge Vizcaya | jorgevizcayaa@gmail.com
