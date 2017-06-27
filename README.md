# Yii2 component and models for storage uploaded files
## Install by composer
composer require sem-soft/yii2-filestorage
## Or add this code into require section of your composer.json and then call composer update in console
"sem-soft/yii2-filestorage": "*"
## Execute migration
```bash
$ ./yii migrate/up --migrationPath=@vendor/sem-soft/yii2-filestorage/migrations
```
## Usage
In configuration file do
```php
<?php
...
  'components'  =>  [
    ...
    'filestorage'	=>  [
        'class'             => \sem\filestorage\FileStorage::className(),
        'storageBaseUrl'    =>  false,
        'storagePath'       =>  '@webroot',
        'storageDir'        =>  'upload'
    ]
    ...
  ],
...
 ?>
 ```
Example of Controller action for file uploading
```php
    public function actionIndex()
    {
	$model = new \backend\models\FileForm();
	
	if (Yii::$app->request->isPost) {
	    
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
	    
            $file = $model->upload();
	    
        }
	
        return $this->render('index', [
	    'model'	=>  $model
	]);
    }
 ```
Example of Upload From Model
```php
<?php

namespace backend\models;

class FileForm extends \yii\base\Model
{
    /**
     * @var UploadedFile
     */
    public $imageFile;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['imageFile'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png, jpg'],
        ];
    }
    
    /**
     * @return boolean
     */
    public function upload()
    {
        if ($this->validate()) {
	    $file = new \sem\filestorage\models\File($this->imageFile,[
		'group_code'	=>  'banners',
		'object_id'	=>  '345',
		'allowedExtensions' =>	[
		    'png',
		    'jpeg',
		    'jpg'
		]
	    ]);
	    if ($file->save()) {
		return $file;
	    }
        }
	
	return false;
    }
}
```
Example of Form View
```php
    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]) ?>

        <?= $form->field($model, 'imageFile')->fileInput() ?>

        <button type="submit">Submit</button>
    <?php ActiveForm::end() ?>
```
Example of displaying files data
```php
    public function actionTest()
    {
	foreach (\sem\filestorage\models\File::find()->all() as $f) {
	    echo $f->getUrl(true) . "<br>";
	    echo $f->url . "<br>";
	    echo $f->name . "<br>";
	    echo $f->path . "<br>";
	    echo $f->size . "<br>";
	    echo \sem\helpers\FileHelper::formatSize($f->size) . "<br>";
	    echo "<br>";
	    echo "<br>";
	}
    }
```
