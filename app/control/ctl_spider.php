<?php
/**
 * 采集七麦信息
 * Created by PhpStorm.
 * User: pengyongsheng
 * Date: 2018/12/12
 * Time: 3:16 PM
 */

namespace app\control;
use phpshow\helper\facade\db;

class ctl_spider
{
    public $starttime;
    public $endtime;
    public $word;
    public $where = '';
    public $word_condition = '';
    /**
     * ctl_spider constructor.
     */
    public function __construct()
    {
        $this->starttime = \request::item("starttime");
        $this->endtime = \request::item("endtime");
        $this->word = \request::item("word");

        echo "你搜索的时间范围为：".$this->starttime."    ".$this->endtime.lr;
        echo "你搜索的关键词为：".$this->word.lr;

        if(!empty($this->starttime) && !empty($this->endtime))
        {
            $this->where .= " and day >= '{$this->starttime}' and day<='{$this->endtime}'  ";
        }
        if(!empty($this->word))
        {
            //用逗号分词
            $words = explode(",",$this->word);
            $word_search = [];
            foreach($words as $word)
            {
                $word_search[] = " appName like '%{$word}%' ";
            }
            $this->word_condition = implode(" or ",$word_search);
            $this->where .= " and ({$this->word_condition}) ";
        }
//        echo $this->where;

    }

    /**
     * 返回回密字符串
     * @param $a
     * @return mixed
     */
    public function encode($a) {
        $string_n = "a12c0fa6ab9119bc90e4ac7700796a53";
        $length = strlen($a);
        $string_n_length = strlen($string_n);
        for ($s = 0;$s < $length;$s++)
        {
            $index = $s % $string_n_length;

            $cc = ord($string_n[$index]);
            $t = ord($a[$s]) ^ $cc;
            $a[$s] = chr($t);
        }
//	var_dump($a);
//	$a = implode("",$a);
        return $a;
    }

    /**
     * 排序比较
     * @param $a
     * @param $b
     * @return int
     */
    function cmp($a, $b)
    {
        $alen = strlen($a);
        $blen = strlen($b);
        $a = substr($a,0,1);
        $b = substr($b,0,1);
        if ($a == $b) {
            if($alen < $blen)
            {
                return -1;
            }else{
                return 1;
            }
            return 0;
        }
        return ($a < $b) ? -1 : 1;
    }

    /**
     * 获取日期Analysis
     * @param $date
     * @param string $genre
     * @return string
     */
    public function getAnalysisUrl($type='release',$date,$pagei='',$genre="6014")
    {
        $params = ['genre'=>"{$genre}",'date'=>"{$date}"];
        if(!empty($pagei))
        {
            $params['page'] = $pagei;
        }
        if($type=='offline')
        {
            $path = '/rank/offline';
        }elseif($type=='release')
        {
            $path = '/rank/release';
        }
        $get_timestamp = time()*1000 - 1515125653845;
        foreach($params as $key=>$val)
        {
            $new_params[] = $val;
        }
        usort($new_params,'self::cmp');
        $new_params = implode("",$new_params);
//        echo "params:".$new_params.lr;
        $new_params = base64_encode($new_params);
//        echo "b64:".$new_params.lr;
        $new_string = $new_params . "@#" . $path . "@#" . $get_timestamp . "@#1";
//        echo "newstring:".$new_string.lr;
        $new_string = $this->encode($new_string);
//        echo lr."encode:".$new_string.lr;
        $new_string = base64_encode($new_string);
        if($pagei)
        {
            $pages = "&page=".$pagei;
        }else{
            $pages = "";
        }
        $url = "https://api.qimai.cn{$path}?analysis=".$new_string."&genre=6014&date=".$params['date'].$pages;
        echo "[url]:".$url.lr;
        return $url;
    }

    /**
     * 获取指定日期段内每一天的日期
     * @param  Date  $startdate 开始日期
     * @param  Date  $enddate   结束日期
     * @return Array
     */
    function getDateFromRange($d0, $d1){


        $_time = range(strtotime($d0), strtotime($d1), 24*60*60);

        $_time = array_map(@create_function('$v', 'return date("Y-m-d", $v);'), $_time);

        return $_time;
    }
    /**
     * 插入新数据
     * @param $attrs
     */
    public function save($table,$attrs)
    {
        if(!is_array($attrs))
        {
            return false;
        }
        $arr_key = array_keys($attrs);
        $arr_value = array_values($attrs);
        $keyss = implode('`,`',$arr_key);
        $valuess = implode("','",$arr_value);
        $sql = "insert into `{$table}`(`{$keyss}`) values('{$valuess}') ";
        db::query($sql);
        return db::insert_id();
    }

    /**
     * 设置数据
     * @param $type
     * @param $result
     */
    public function setData($type,$result,$date)
    {
        foreach($result['rankInfo'] as $kk=>$vv)
        {
            $tmp = [];
            $tmp['type'] = $type;
            $tmp['appId'] = $vv['appInfo']['appId'];
            $tmp['appName'] = addslashes($vv['appInfo']['appName']);
            $tmp['icon'] = $vv['appInfo']['icon'];
            $tmp['publisher'] = addslashes($vv['appInfo']['publisher']);
            $tmp['country'] = $vv['appInfo']['country'];
            if(!isset($vv['appInfo']['price']))
            {
                //免费的为0
                $tmp['price'] = 0;
            }else{
                $tmp['price'] = $vv['appInfo']['price'];
            }
            $tmp['genre'] = $vv['genre'];
            if(isset($vv['price']))
            {
                $tmp['price_type'] = $vv['price'];
            }else{
                $tmp['price_type'] = "";
            }
            $tmp['releaseTime'] = $vv['releaseTime'];
            $time = strtotime($date);
            $tmp['day'] = strftime("%Y%m%d",$time);
            $tmp['month'] = substr($tmp['day'],0,6);
            $this->save("app",$tmp);
        }
    }

    /**
     * 获取代理列表
     * 每次取一个ip
     * @return array|bool|string
     */
    public function getProxy()
    {
        $url = "";  //这里修改自己定义的动态代理列表
        $proxy_url = file_get_contents($url);
        $proxy_url = "http://".trim($proxy_url);
        echo "proxy_url:".$proxy_url.lr;
        return $proxy_url;
    }


    /**
     * 跑取七麦数
     */
    public function run()
    {
        $datestart = "2019-02-01";  //2018-12-23 page:21 error  2019-01-20  //page21就是有问题
        $dateend = "2019-02-15";
        $daterange = $this->getDateFromRange($datestart,$dateend);
        $proxy_url = $this->getProxy();

//        exit();
        //上架release 下架offline
        foreach($daterange as $key=>$date)
        {
            echo $date.lr;
            $type_arr = [
                'release',
                'offline'
            ];
            foreach($type_arr as $type)
            {
                $url = $this->getAnalysisUrl($type,$date,"");
                $result = \http::getProxy($url,$proxy_url);
                if($result==false)
                {
                    $proxy_url = $this->getProxy();
                    $result = \http::getProxy($url,$proxy_url);
                }

                preg_match("/{(.*?)+}/isU",$result,$t);
                if(!isset($t['0']))
                {
                    echo "error_url:".$url.lr;
                    var_dump($result);
                }
                $result = $t['0'];
                $result = json_decode($result,true);
                if($result['code'] != '10000')
                {
                    die('失败:'.$url);
                    break;
                }
                if(isset($result['rankInfo']))
                {
                    $this->setData($type,$result,$date);
                }else{
                    continue;
                }
                //这里判断页数
                //其实都要传cookie
                $maxPage = $result['maxPage'];
//                $maxPage = 2;
                $pagei = 2;
                if($maxPage>1)
                {
                    //入库之后再判断
                    while($pagei<=$maxPage)
                    {
                        echo "page:{$pagei}-----max:{$maxPage}".lr;
                        $tmpi = strval($pagei);
                        $url = $this->getAnalysisUrl($type,$date,$tmpi);
                        $result = \http::getProxy($url,$proxy_url);
                        $result = preg_match("/{(.*?)+}/isU",$result,$t);
                        $result = $t['0'];
                        $result = json_decode($result,true);
                        $pagei++;
                        if($result['code'] != '10000')
                        {
                            var_dump($result);
                            die('失败:'.$url);
                            break;
                        }
                        if(isset($result['rankInfo']))
                        {
                            $this->setData($type,$result,$date);
                        }

                    }


                }


            }
//            exit();
        }

    }

    /**
     * 内容分组
     * @param $group
     * @return array
     */
    public function getGroupData($group,$order_control='1')
    {
        $sql = "select type,count(type) as quantity,{$group} from app where 1 {$this->where} group by {$group},type ";
        if($order_control == '1')
        {
            $sql = "{$sql} order by {$group}";
        }
        $daydata = db::get_all($sql);
        foreach($daydata as $key=>$val)
        {
            if($val['type'] == 'offline')
            {
                $offlineday[$val[$group]]  = $val['quantity'];
            }elseif($val['type'] == 'release')
            {
                $releaseday[$val[$group]]  = $val['quantity'];
            }
        }
        $category_release = $category_offline = $release = $offline = [];
        foreach($releaseday as $key=>$val)
        {
            $category_release[] = $key;
            $release[] = $val;
        }
        foreach($offlineday as $key=>$val)
        {
            $category_offline[] = $key;
            $offline[] = $val;
        }

        return [$category_release,$category_offline,$release,$offline];
    }

    /**
     * 下架应用按时间相隔排序
     */
    public function getOfflineDayByYear()
    {
        $sql = "select releaseTime,day,count(type) as quantity,TIMESTAMPDIFF(YEAR,releaseTime,date_format(day,'%Y-%m-%d %H:%i:%s')) as ylong from app where type='offline' and releaseTime!='0000-00-00 00:00:00' {$this->where} group by ylong;";
        $result = db::get_all($sql);
        return $result;
    }

    /**
     * 获取相关app
     * @return mixed
     */
    public function getAppName()
    {
        $sql = "select type,appName,releaseTime,day,genre from app where 1 {$this->where}";
        $result = db::get_all($sql);
        return $result;
    }

    /**
     * 图形的展示
     * 按天
     * 按分类
     * 上架与下架
     * 按标题长度
     * todo 下架应用时长占比
     */
    public function index()
    {
        $day = $this->getGroupData('day');
        $genre = $this->getGroupData('genre');
        $length = $this->getGroupData('length(appName)');
        $yearlong = $this->getOfflineDayByYear();

        if(!empty($this->word_condition))
        {
            $appName = $this->getAppName();
            foreach($appName as $key=>$val)
            {
                $appNameData[$val['type']][] = $val;
            }
        }else{
            $appNameData = "";
        }

        foreach($yearlong as $key=>$val)
        {
            $yearlong_category[] = $val['ylong']."年";
            $yearlong_value[] = $val['quantity'];
        }

        $cmpquantity = [
            '1-5' => [1,5],
            '6-10' => [6,10],
            '11-15' => [11,15],
            '16-20' => [16,20],
            '21-25' => [21,25],
            '26-30' => [26,30],
            '31-100' => [31,100],
        ];
        $i=0;
        $release_data = [];
        foreach($length['0'] as $key=>$val)
        {
            $release_data[$i]['name'] = $val;
            $release_data[$i]['value'] = $length['2'][$key];
            $i++;
        }
        $i=0;
        $offline_data = [];
        foreach($length['1'] as $key=>$val)
        {
            $offline_data[$i]['name'] = $val;
            $offline_data[$i]['value'] = $length['3'][$key];
            $i++;
        }
        $release_length = [];
        foreach($release_data as $key=>$val)
        {
            foreach($cmpquantity as $ckey=>$cval)
            {
                if($val['name'] > $cval['0'] && $cval['1'] < $val['name'])
                {
                    if(!isset($release_length[$ckey])) $release_length[$ckey] = 0;
                    $release_length[$ckey] += $val['value'];
                }
            }
        }
        $offline_length = [];
        foreach($offline_data as $key=>$val)
        {
            foreach($cmpquantity as $ckey=>$cval)
            {
                if($val['name'] > $cval['0'] && $cval['1'] < $val['name'])
                {
                    if(!isset($offline_length[$ckey])) $offline_length[$ckey] = 0;
                    $offline_length[$ckey] += $val['value'];
                }
            }
        }
        $release_length_category = array_keys($release_length);
        $release_length_value = array_values($release_length);

        $offline_length_category = array_keys($offline_length);
        $offline_length_value = array_values($offline_length);

        $i=0;
        $release_data = $offline_data = [];
        foreach($genre['0'] as $key=>$val)
        {
            $release_data[$i]['name'] = $val;
            $release_data[$i]['value'] = $genre['2'][$key];
            $i++;
        }
        $i=0;
        foreach($genre['1'] as $key=>$val)
        {
            $offline_data[$i]['name'] = $val;
            $offline_data[$i]['value'] = $genre['3'][$key];
            $i++;
        }
        $release_data = json_encode($release_data);
        $offline_data = json_encode($offline_data);

        \tpl::assign("appNameData",$appNameData);

        \tpl::assign("release_data",$release_data);
        \tpl::assign("offline_data",$offline_data);

        \tpl::assign("yearlong_category",json_encode($yearlong_category));
        \tpl::assign("yearlong_value",json_encode($yearlong_value));

        \tpl::assign("category",json_encode($day['0']));
        \tpl::assign("release",json_encode($day['2']));
        \tpl::assign("offline",json_encode($day['3']));

        \tpl::assign("genre_category",json_encode($genre['0']));
        \tpl::assign("genre_release",json_encode($genre['2']));
        \tpl::assign("genre_offline",json_encode($genre['3']));

        \tpl::assign("length_release_category",json_encode($release_length_category));
        \tpl::assign("length_offline_category",json_encode($offline_length_category));
        \tpl::assign("length_release",json_encode($release_length_value));
        \tpl::assign("length_offline",json_encode($offline_length_value));

        \tpl::display("spider");
    }

}