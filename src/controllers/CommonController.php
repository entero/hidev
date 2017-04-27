<?php
/**
 * Automation tool mixed with code generator for easier continuous development
 *
 * @link      https://github.com/hiqdev/hidev
 * @package   hidev
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2015-2017, HiQDev (http://hiqdev.com/)
 */

namespace hidev\controllers;

use Yii;

/**
 * Common controller.
 */
class CommonController extends \yii\console\Controller
{
    public $before = [];
    public $after = [];

    public function actionIndex()
    {
        Yii::trace("Started: '$this->id'");
    }

    public function behaviors()
    {
        return [
            CommonBehavior::class,
        ];
    }
}
