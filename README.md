Prerequisites
==============
* **php7.1 + (curl, json, dom) extensions**
* [**Composer**][1]

Installation
--------------

Please follow below steps for installation:

  * **Dependencies installation**

        composer install

### Launching app:
    php -S localhost:{port} src/movingimage/bootstrap.php
    
### URL    
    http://localhost:{port}/video_id={VIDEO_ID}&offset={OFFSET}

**`Params`**:
   * VIDEO_ID the id of the video
   * OFFSET time stamp from the video beginning

### Tests

    bin/phpunit --debug -c .

Enjoy!

[1]:  https://getcomposer.org/download/
