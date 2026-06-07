<?php
/**
 * API名称：天气查询
 * 端点：/weather
 * 方法：GET
 */
class Weather_API {
    //插件网关固定执行入口
    public function handle_request() {
        //接收GET传参
        $city = $_REQUEST['city'] ?? '';
        $type = $_REQUEST['type'] ?? 'json';
        $weatherObj = new weather(['city'=>$city, 'type'=>$type]);
        //返回数组给网关自动转json
        return $weatherObj->getData();
    }
}

// 原天气核心类（保留全部抓取逻辑，删除所有直接输出）
class weather
{
    public $info = [];
    public $array = [];
    public $message;
    public function __construct(array $array)
    {
        $this->info = $array;
        $this->parametersexception();
    }
    public function parametersException()
    {
        if(!isset($this->info['city']) || !$this->info['city'])
        {
            return $this->exec(['code'=>-1, 'text'=>'请输入地区']);
        } else {
            $this->info['city'] = preg_replace('/(省|县|区|市|(壮|回|维吾尔|延边朝鲜|恩施土家苗|湘西土家苗|阿坝藏羌|甘孜藏|凉山彝|黔东南苗侗|黔南布依苗|黔西南布依苗|楚雄彝|红河哈尼彝|文山壮苗|西双版纳傣|大理白|德宏傣景颇|怒江傈僳|迪庆藏|临夏回|甘南藏|海南藏|海北藏|海西蒙古藏|黄南藏|果洛藏|玉树藏|伊犁哈萨克|博尔塔拉蒙古|昌吉回|巴音郭楞蒙古|克孜勒苏柯尔克孜)*(族)*自治(区|州|市|县)*)*/', '', $this->info['city']);
        }
        return $this->weather();
    }
    public function weather()
    {
        if($city = $this->is_city($this->info['city']))
        {
            $url = "http://d1.weather.com.cn/weather_index/{$city}.html";
            $data = preg_split('/[;]*var .*?=/', $this->teacher_curl($url, [
                'refer'=>'http://www.weather.com.cn/'
            ]));
            if(!$data[0]) array_shift($data);
            if($data)
            {
                $weather = json_decode($data[0])->weatherinfo;
                $weather2 = json_decode($data[1]);
                $weather2 = isset($weather2->w[0]) ? $weather2->w[0] : $weather2->w;
                $weather3 = json_decode($data[2]);
                $weather4 = array_values(json_decode($data[3], true)['zs']);
                array_shift($weather4);
                $array = [
                    'code'=>1,
                    'text'=>'获取成功',
                    'data'=>[
                        'city'=>$weather->city,
                        'cityEnglish'=>$weather->cityname,
                        'temp'=>$weather->temp,
                        'tempn'=>$weather->tempn,
                        'weather'=>$weather->weather,
                        'wind'=>$weather->wd,
                        'windSpeed'=>$weather->ws,
                        'time'=>date('Y-m-d') . ' 08:00',
                        'warning'=>(Object) [],
                        'current'=>[
                            'city'=>$weather3->cityname,
                            'cityEnglish'=>$weather3->nameen,
                            'humidity'=>$weather3->sd,
                            'wind'=>$weather3->WD,
                            'windSpeed'=>$weather3->WS,
                            'visibility'=>$weather3->njd,
                            'weather'=>$weather3->weather,
                            'weatherEnglish'=>$weather3->weathere,
                            'temp'=>$weather3->temp,
                            'fahrenheit'=>$weather3->tempf,
                            'air'=>$weather3->aqi,
                            'air_pm25'=>$weather3->aqi_pm25,
                            'date'=>$weather3->date,
                            'time'=>$weather3->time,
                            'image'=>$this->get_Image($weather3->weather)
                        ],
                        'living'=>[]
                    ]
                ];
                if($weather2)
                {
                    $array['data']['warning'] = [
                        'windSpeed'=>isset($weather2->w4) ? $weather2->w4 : null,
                        'wind'=>isset($weather2->w5) ? $weather2->w5 : null,
                        'color'=>isset($weather2->w7) ? $weather2->w7 : null,
                        'warning'=>isset($weather2->w9) ? $weather2->w9 : null,
                        'time'=>isset($weather2->w15) ? $weather2->w15 : null
                    ];
                }
                foreach(range(0, 89, 3) as $k=>$v)
                {
                    $array['data']['living'][$k] = [
                        'name'=>$weather4[$v],
                        'index'=>$weather4[($v + 1)],
                        'tips'=>$weather4[($v + 2)]
                    ];
                }
                $this->array = $array;
                return $array;
            } else {
                return ['code'=>-3, 'text'=>"不支持该地区查询"];
            }
        } else {
            return ['code'=>-2, 'text'=>"`{$this->info['city']}` 可能不是一个地区或不支持该地区查询"];
        }
    }
    public function is_city($city, $switch = 'region')
    {
        $city_region = $city;
        //资源路径：同目录weather/city.json
         $data = json_decode(file_get_contents(__DIR__ . '/weather/city.json'), true);
         switch($switch) {
             case 'region':
                 if($data)
                 {
                     $keys = (array_keys($data));
                     foreach($keys as $key)
                     {
                         if(str_starts_with($city, $key))
                         {
                             $region = str_replace($key, '', $city);
                             if($region)
                             {
                                 $city = $this->is_city($region, 'city');
                                 break;
                             } else {
                                 $city_key = array_keys($data[$key]);
                                 $city = $data[$key][$city_key[0]][$city_key[0]]['AREAID'];
                                 break;
                             }
                         }
                     }
                     if(!$city || $city == $city_region) $city = $this->is_city($city, 'city');
                 }
                 break;
             case 'city':
                 foreach(array_keys($data) as $v)
                 {
                     foreach($data[$v] as $val)
                     {
                         $keys = array_keys($val);
                         foreach($keys as $key)
                         {
                             if(str_starts_with($city, $key))
                             {
                                 $region = str_replace($key, '', $city);
                                 if($region)
                                 {
                                     $city = $this->is_city($region, 'city');
                                     break;
                                 } else {
                                     $city = $val[$city]['AREAID'];
                                     break;
                                 }
                             }
                         }
                     }
                 }
                 break;
         }
         return is_numeric($city) ? $city : false;
     }
     //天气图标地址改为网站绝对URL
     public function get_Image($weather = '晴')
     {
         if($weather == '晴' && (date('H') < 5 || date('H') > 19))
         {
             $weather = '晚上晴';
         }
         $imgUrl = site_url('/wp-content/plugins/yxs-api/apimod/weather/image/'.$weather.'.png');
         if(file_exists(__DIR__ . "weather/image/{$weather}.png"))
         {
             return $imgUrl;
         } else {
             return "https://zibi.sndnn.com/dy/weather/image/云.png";
         }
     }
     //存储返回数据
     public function exec($array, $message = null)
     {
         $message = $message ? $message : $array['text'];
         $this->array = $array;
         $this->message = $message;
         return $array;
     }
     //原curl函数改名保留
     public function teacher_curl($url, $paras = [])
     {
         $ch = curl_init();
         curl_setopt($ch, CURLOPT_URL, $url);
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
         curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
         if (isset($paras['Header'])) {
             $Header = $paras['Header'];
         } else {
             $Header[] = "Accept:*/*";
             $Header[] = "Accept-Encoding:gzip,deflate,sdch";
             $Header[] = "Accept-Language:zh-CN,zh;q=0.8";
             $Header[] = "Connection:close";
         }
         curl_setopt($ch, CURLOPT_HTTPHEADER, $Header);
         if (isset($paras['ctime'])) curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $paras['ctime']);
         else curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
         if (isset($paras['rtime'])) curl_setopt($ch, CURLOPT_TIMEOUT, $paras['rtime']);
         if (isset($paras['post'])) {
             curl_setopt($ch, CURLOPT_POST, 1);
             curl_setopt($ch, CURLOPT_POSTFIELDS, $paras['post']);
         }
         if (isset($paras['refer'])) {
             $ref = $paras['refer'] ==1 ? 'http://m.qzone.com/infocenter?g_f=' : $paras['refer'];
             curl_setopt($ch, CURLOPT_REFERER, $ref);
         }
         if (!isset($paras['ua'])) curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36");
         curl_setopt($ch, CURLOPT_ENCODING, "gzip");
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
         $ret = curl_exec($ch);
         curl_close($ch);
         return $ret;
     }
     //对外输出结果
     public function getData(){
         return $this->array;
     }
 }
 //兼容低版本PHP缺少函数
 if (!function_exists('str_starts_with')) {
     function str_starts_with($haystack, $needle) {
         $len = mb_strlen($needle);
         return mb_substr($haystack, 0, $len) === $needle;
     }
 }
 if(!function_exists('is_numEric')){
 	function is_numEric($v){
 		return is_numeric($v);
 	}
 }