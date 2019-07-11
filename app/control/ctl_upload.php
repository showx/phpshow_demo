<?php
namespace app\control;
/**
 * Created by PhpStorm.
 * User: pengyongsheng
 * Date: 2018/9/29
 * Time: 10:00 AM
 */
class ctl_upload extends \control{
    public function test()
    {
        $a=<<<EOL
        <div>
        <span>本地单文件上传</span>
        <form action="/upload/save" method="post" enctype="multipart/form-data">
            <input type="file" name="file"/>
            <input type="submit" value="上传"/>
        </form>
        </div>
EOL;
        echo $a;
        //下面循环可以下载的文件

        //增加说明

    }
    public function save()
    {
        if(\request::$request_mdthod == 'POST')
        {
            $upload = new \phpshow\lib\upload();
            $data = $upload->single_save("file");
            $data = json_decode($data,true);
            if($data['code'] == '0')
            {

                //保存之后扔给server处理即可
                //进行下一步处理
                $file = $data['data'];
//                $file_data = file($file);
//                $file_data = array_slice($file_data,0,20);

            }
        }else{
            echo 'no post';
        }
    }

}