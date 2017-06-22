# Yii2 component and models for storage uploaded files
## Install by composer
composer require sem-soft/yii2-file-storage
## Or add this code into require section of your composer.json and then call composer update in console
"sem-soft/yii2-file-storage": "*"
## Usage
In configuration file do
```php
<?php
...
  'components'  =>  [
    ...
    'filestorage'	=>  [
        'class' => \sem\components\filestorage\FileStorage::className(),
    ],
    ...
  ],
...
 ?>
 ```
 Use as simple component
