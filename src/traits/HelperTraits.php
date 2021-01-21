<?php
/**
 * Created By PhpStorm
 * User: LW
 * Date: 2020/12/29
 * Time: 10:30
 * Desc: 辅助函数
 */
namespace commonfunc\traits\helper;

trait HelperTraits{


    /**判断字符串是否是base64位
     * @param $str
     * @return bool
     * @time 2021/1/11 17:33
     * @author LW
     */
    public function isBase64($str)
    {
        $str = preg_replace("/ /","+",$str);
        //这里多了个纯字母和纯数字的正则判断
        if (@preg_match('/^[0-9]*$/', $str) || @preg_match('/^[a-zA-Z]*$/', $str)) {
            return false;
        } elseif (is_utf8(base64_decode($str)) && base64_decode($str) != '' && $str == base64_encode(base64_decode($str)) ) {
            return true;
        }
        return false;
    }


    /**从文本中解析出所@的用户信息 (文本中的 @格式: [@username #HJW-AT#userId] )
     * @param string $text
     * @return array
     * @time 2020/12/17 10:30
     * @author LW
     */
    public function parse_content_at_user(string $text):array{
        preg_match_all("/(?<=\[\@)[^\]]+/",trim($text),$At);
        $result = $At[0] ?? [];
        $user = [];
        foreach ($result as $value){
            $temp = explode('#HJW-AT#',$value);
            $user_name = array_shift($temp);
            $user_id = array_shift($temp);
            $user[] = ['user_name'=>$user_name,'user_id'=>$user_id];
        }
        return $user;
    }

    /**php array_column函数扩展,增加input中index_key值多个相同时将结果合并为多维数组
     * @param array $input
     * @param $column_key
     * @param null $index_key
     * @return array
     * @time 2020/12/14 13:17
     * @author LW
     */
    public function array_column_ext(array $input, $column_key, $index_key=null ):array{
        if (empty($input)) return [];
        $temp = [];
        if(empty($index_key)){
            $temp = array_column($input,$column_key,$index_key);
            goto END;
        }
        foreach ($input as $key=>$value){
            if(!is_array($value)) break;
            $temp_key = (string) $value[$index_key]??'';
            $value = empty($column_key) ? $value : ($value[$column_key]?? '');
            if(!empty($temp_key)){
                $temp[$temp_key] = key_exists($temp_key,$temp)
                    ? array_merge($temp[$temp_key],[$value])
                    : [$value];
            }
        }
        END:
        return $temp;
    }

    /**过滤数组中指定key的键值对
     * @param array $array
     * @param array $keys
     * @return array
     * @time 2020/12/2 13:43
     * @author LW
     */
    public function array_key_filter(array $array,array $keys):array{
        foreach ($array as $key=>$value){
            if(!in_array($key,$keys)) unset($array[$key]);
        }
        return $array ?: [];
    }


    /**将指定数字转换成特殊码(6位以上)
     * @param int $num
     * @return string
     * @time 2020/11/17 16:13
     * @author LW
     */
    function numSwitchCode(int $num){
        //位标识
        $bit_map = [
            'G','H','I','J','K','L','O','M','N','P','Q','R','S','T','U','V','W','X','Y','Z'
        ];
        $diff = 7- (int)strlen($num);
        if($diff > 0){
            $bit_one = $bit_map[$diff];
            $dechex_code = strtoupper(dechex($num));
            $bit_zero_len = 5 - (int)strlen($dechex_code);
            $bit_zero_string = $bit_zero_len > 0 ? $bit_map[$diff + $bit_zero_len] : '';
            if($bit_zero_len > 2){
                for ($i=2;$i<= $bit_zero_len;$i++){
                    if($i % 2 == 0){
                        $bit_zero_string .= $bit_map[$i+$diff];
                        continue;
                    }
                    $bit_zero_string .= 0;
                }
            }else if($bit_zero_len == 2){
                $bit_zero_string .= 0;
            }
            $code = $bit_one.trim($bit_zero_string).$dechex_code;
        }else{
            $code = strtoupper(dechex($num));
        }
        $bit_last = substr($code,-1,1);
        if(is_numeric($bit_last)){
            $bit_last_value =  abs((int)count($bit_map)- (int)substr($code,'-1',1));
            $code = substr($code,0,-1).$bit_map[$bit_last_value];
        }
        return $code;
    }


    /**邀请码转换成 userid
     * @param string $code
     * @return false|float|int|string
     * @time 2020/11/17 16:12
     * @author LW
     */
    function codeSwitchNum(string $code){
        $bit_map = [
            'G','H','I','J','K','L','O','M','N','P','Q','R','S','T','U','V','W','X','Y','Z'
        ];
        $bit_one = substr($code,0,1);
        $bit_zero_string = substr($code,1,1);
        $bit_last = strtoupper(substr($code,-1,1));
        if(is_numeric($bit_last)) return 0;									//特殊处理邀请码末尾不可能为数字

        $last_index = array_search($bit_last,$bit_map);

        if($last_index !== false) {
            $bit_last_original = abs((int)count($bit_map) - (int)$last_index); //末尾原始16进制值
            $code = substr($code, 0, -1) . $bit_last_original;
        }
        if(!is_numeric($bit_one)){
            $bit_diff = array_search($bit_one,$bit_map);					//位差,与7作比较
            $zero_len_index = array_search($bit_zero_string,$bit_map);
            $bit_zero_len = ($zero_len_index == false) ? 0 : (int)$zero_len_index - (int) $bit_diff;
            $dechex_code = substr($code,$bit_zero_len + 1);
            $userId = hexdec($dechex_code);
        }else{
            $userId = hexdec($code);
        }
        return $userId;
    }


    /**判断两个日期相差几天(天数)
     * @param string $date1
     * @param string $date2
     * @return int
     * @time 2020/11/13 10:44
     * @author LW
     */
    public function diffBetweenTwoDays(string $date1,string $date2):int {
        $second1 = strtotime($date1);
        $second2 = strtotime($date2);
        return abs(($second1 - $second2) / 86400);
    }

    /**将在有效范围(-2147483649 ~ 2147483648)的数字字符串转成数字(仅限连续的整数字符串) 超出范围的数字字符串直接返回原字符串
     * @param string $string
     * @return int|string
     * @time 2020/10/29 15:10
     * @author LW
     */
    public function stringToNumber(string $string){
        if(strpos($string,'.')){
            return $string;
        }else if(strlen($string) <10){
            return (int) $string;
        }else if($string >= ~(1 << 31) && $string <= (1 << 31)){
            return (int) $string;
        }
        return $string;
    }

    /**处理城市的市,去掉市字
     * @param array $data
     * @return array
     * @time 2020/10/14 11:09
     * @author LW
     */
    public function dealCity(array $data):array{
        if(!empty($data)){
            foreach ($data as $key=>&$city){
                if(!is_array($city)) continue;
                if(!isset($city['name'])){
                    $city = dealCity($city);
                }
                if (mb_substr($city['name'], -1, 1, 'UTF-8') == '市') {
                    $city['name'] = mb_substr($city['name'], 0, mb_strlen($city['name'], 'UTF-8') - 1, 'UTF-8');
                }
            }
        }
        return $data;
    }

    /**只取整数部分
     * @param string $data
     * @return int
     * @time 2020/9/9 17:35
     * @author LW
     */
    public function getInt(string $data):int{
        return $data == intval($data) ? $data : substr($data,0,strpos($data,'.'));
    }


    /**
     * 隐藏手机号码中间4位
     *
     * @param string $mobile
     * @return string $mobile
     */
    public function hideMobile($mobile)
    {
        $mobile = substr_replace($mobile, '****', 3, 4);

        return $mobile;
    }
}
