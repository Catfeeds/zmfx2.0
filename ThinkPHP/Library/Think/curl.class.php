<?php
/*
    //cookie curl
    //使用方法：
            $cc = new curl();
            $document = $cc->get('http://www.aibhsc.com/tiku.php?fid=41&id=52311');         //GET方式
            $document = $cc->post('http://www.aibhsc.com/tiku.php','fid=41&id=52311');      //POST方式
            echo $document;
*/
namespace Think;
class curl{
        public $headers;
        public $user_agent;
        public $compression;
        public $cookie_file;
        public $proxy;

        function curl($cookies=TRUE,$cookie='cookies.txt',$compression='gzip',$proxy=''){
                $this->headers[] = 'Accept: text/html,application/xhtml+xml,application/xml,image/gif,image/x-bitmap,image/jpeg,image/pjpeg,*/*';
                $this->headers[] = 'Connection: Keep-Alive';
//              $this->headers[] = 'Content-type: application/x-www-form-urlencoded;charset=UTF-8';
//		$this->headers[] = 'Content-type: multipart/form-data;charset=utf-8';
                $this->user_agent = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.94 Safari/537.36';
                $this->compression=$compression;
                $this->proxy=$proxy;
                $this->cookies=$cookies;
                if ($this->cookies == TRUE){$this->cookie($cookie);}
        }

        function cookie($cookie_file){
                if (file_exists($cookie_file)){
                        $this->cookie_file=$cookie_file;
                }else{
                        fopen($cookie_file,'w') or $this->error('The cookie file could not be opened. Make sure this directory has the correct permissions');
                        $this->cookie_file=$cookie_file;
                        @fclose($this->cookie_file);
                }
        }

        function get($url){
                $process = curl_init($url);
                curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
                curl_setopt($process, CURLOPT_HEADER, 0);
                curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
                if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
                if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
                curl_setopt($process,CURLOPT_ENCODING , $this->compression);
                curl_setopt($process, CURLOPT_TIMEOUT, 3600);
                if ($this->proxy) curl_setopt($process, CURLOPT_PROXY, $this->proxy);
                curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
                $return = curl_exec($process);
                curl_close($process);
                $return = $this->_strReplace($return);
                return $this->autoToutf8($return);
        }

		function arr2str($data){	//将post数组转字符串
    			return http_build_query($data);
		}
		
		function _strReplace($str){	//字符替换,根据需要进行扩展
			$arr1 = array("'");
			$arr2 = array('&#39;');
			return str_ireplace($arr1,$arr2,$str);
		}
		
        function post($url,$data,$referer=''){
                $process = curl_init($url);
                curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
                curl_setopt($process, CURLOPT_HEADER, 0);
                if($referer){
                	curl_setopt($process,CURLOPT_REFERER,$referer);
                }else{
                	curl_setopt($process,CURLOPT_REFERER,$url);
                }
                curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
                if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
                if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
                curl_setopt($process, CURLOPT_ENCODING , $this->compression);
                curl_setopt($process, CURLOPT_TIMEOUT, 120);
                if ($this->proxy) curl_setopt($process, CURLOPT_PROXY, $this->proxy);
                curl_setopt($process, CURLOPT_POSTFIELDS, $this->arr2str($data));
                curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($process, CURLOPT_POST, 1);
                $return = curl_exec($process);
                curl_close($process);
                $return = $this->_strReplace($return);
                return $this->autoToutf8($return);
        }

		function autoToutf8($data,$to='utf-8'){
			if(is_array($data)) {
				foreach($data as $key => $val) {
					$data[$key] = phpcharset($val, $to);
				}
			} else {
				$encode_array = array('ASCII', 'UTF-8', 'GBK', 'GB2312', 'BIG5');
				$encoded = mb_detect_encoding($data, $encode_array);
				$to = strtoupper($to);
				if($encoded != $to) {
					$data = mb_convert_encoding($data, $to, $encoded);
				}
			}
			return $data;
		}

        function error($error){
                //echo "<center><div style='width:500px;border: 3px solid #FFEEFF; padding: 3px; background-color: #FFDDFF;font-family: verdana; font-size: 10px'><b>cURL Error</b><br>$error</div></center>";
                error_log($error);
        }
}
?>
