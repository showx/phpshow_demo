<?php
namespace app\control;

use \app\model\mod_index;
use \phpshow\helper\facade\log;
use \phpshow\helper\facade\db;
/**
 * Created by PhpStorm.
 * User: showx
 * Date: 2018/8/20
 * Time: 下午4:35
 */
class ctl_index extends \control
{
    public function index()
    {
        echo 'phpshow ';
        \tpl::display("index");
    }
    public function info()
    {
        phpinfo();
    }
}