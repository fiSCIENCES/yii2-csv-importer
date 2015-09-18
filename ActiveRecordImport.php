<?php

/**
 * @copyright Copyright Victor Demin, 2015
 * @license https://github.com/ruskid/yii2-excel-importer/LICENSE
 * @link https://github.com/ruskid/yii2-excel-importer#README
 */

namespace ruskid\csvimporter;

use ruskid\csvimporter\ImportInterface;
use ruskid\csvimporter\CSVImporter;

/**
 * Import from CSV. This will create/validate/save an ActiveRecord object per excel line. 
 * This is the slowest way to insert, but most reliable. Use it with small amounts of data.
 * 
 * @author Victor Demin <demin@trabeja.com>
 */
class ActiveRecordImport extends CSVImporter implements ImportInterface {

    /**
     * ActiveRecord class name
     * @var string
     */
    public $className;
    
    /**
     * Attribute configs on how to import data.
     * @var array
     */
    public $configs;
    
    /**
     * Will multiple import data into table
     * @return integer number of rows affected
     */
    public function import() {
        $rows = $this->getRows();
        $countInserted = 0;
        foreach ($rows as $row) {
            /* @var $model \yii\db\ActiveRecord */
            $model = new $this->className;
            $uniqueAttributes = [];
            foreach ($this->configs as $config) {
                if (isset($config['attribute']) && $model->hasAttribute($config['attribute'])) {
                    $value = call_user_func($config['value'], $row);

                    //Create array of unique attributes
                    if (isset($config['unique']) && $config['unique']) {
                        $uniqueAttributes[$config['attribute']] = $value;
                    }

                    //Set value to the model
                    $model->setAttribute($config['attribute'], $value);
                }
            }
            //Check if model is unique, searching by attributes
            if ($this->isActiveRecordUnique($uniqueAttributes)) {
                $countInserted = $countInserted + $model->save();
            }
        }
        return $countInserted;
    }

    /**
     * Will check if Active Record is unique by exists query.
     * @param array $attributes
     * @return boolean
     */
    private function isActiveRecordUnique($attributes) {
        /* @var $class \yii\db\ActiveRecord */
        $class = $this->className;
        return empty($attributes) ? true :
                !$class::find()->where($attributes)->exists();
    }

}