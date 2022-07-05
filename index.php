<?php

error_reporting(E_ALL);
ini_set('memory_limit', '-1');

include_once('simple_html_dom.php');
require_once (__DIR__ . '/vendor/autoload.php');
use Rct567\DomQuery\DomQuery;
use HeadlessChromium\BrowserFactory;


class Scraper
{
    public $recursive_count;

    function __construct() {
        $this->recursive_count = 0;
    }

    public function get_web_page( $url )
    {
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

        $options = array(
    
            CURLOPT_CUSTOMREQUEST  => "GET",        //set request type post or get
            CURLOPT_POST           => false,        //set to GET
            CURLOPT_USERAGENT      => $user_agent, //set user agent
            CURLOPT_COOKIEFILE     => "cookie.txt", //set cookie file
            CURLOPT_COOKIEJAR      => "cookie.txt", //set cookie jar
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
            CURLOPT_PROXY          => 'zproxy.lum-superproxy.io',
            CURLOPT_PROXYPORT      => '22225',
            CURLOPT_PROXYUSERPWD   => 'lum-customer-hl_fa848026-zone-daniel_sahlin_zone-country-se:0xwx5ytxlfcc',
            CURLOPT_HTTPPROXYTUNNEL=> 1,
        );
        
        $ch      = curl_init( $url );
        curl_setopt_array( $ch, $options );
        $content = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );

        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $content;
        return $header;

    }

    public function getDataWithAPI( $url )
    {
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

        $options = array(
    
            CURLOPT_CUSTOMREQUEST  => "GET",        //set request type post or get
            CURLOPT_POST           => true,        //set to GET
            CURLOPT_USERAGENT      => $user_agent, //set user agent
            CURLOPT_COOKIEFILE     =>"cookie.txt", //set cookie file
            CURLOPT_COOKIEJAR      =>"cookie.txt", //set cookie jar
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
            // CURLOPT_PROXY          => 'zproxy.lum-superproxy.io',
            // CURLOPT_PROXYPORT      => '22225',
            // CURLOPT_PROXYUSERPWD   => 'lum-customer-hl_fa848026-zone-daniel_sahlin_zone:0xwx5ytxlfcc',
            // CURLOPT_HTTPPROXYTUNNEL=> 1,
            // CURLOPT_HTTPHEADER     => array(
            //                             'origin: https://www.ratsit.se',
            //                             'Content-Type: application/json',
            //                         ),

        );
        
        $ch = curl_init( $url );
        curl_setopt_array( $ch, $options );

        $result = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );

        $data = json_decode($result, true);


        if(is_array($data)){
            if(array_key_exists('person', $data))
                if(array_key_exists('list', $data['person']))
                    if(!empty($data['person']['list']))
                        return $data['person']['list'][0];
            else
                return false;
        }
        else{
            return false;
        }
        
    }

    public function putTestHtml($html = '')
    {
        file_put_contents("uploads/html.txt", "");

        $myfile = fopen('./uploads/'.'html'.'.txt', "a") or die("Unable to open file!");
        $txt = $html;
        fwrite($myfile, $txt);
        fclose($myfile);
        
    }

    public function findAge($birthDate = null)
    {
        $birthDate = explode("-", $birthDate);
        
        $age = (date("md", date("U", mktime(0, 0, 0, $birthDate[2], $birthDate[0], $birthDate[1]))) > date("md")
        ? ((date("Y") - $birthDate[0]) - 1)
        : (date("Y") - $birthDate[0]));
        
        return $age;
        
    }

    public function getData($address, $key, $file_name, $next_page = null)
    {
        if($this->recursive_count > 5)
            return;

        $input = $address;
        
        $original_address = $address = trim(preg_replace('/\s\s+/', ' ', $address));

        $address = str_replace(' ', '+', urlencode($address));

        if($next_page){

            $next_page = preg_replace('/\s+/', ' ', $next_page);
            $next_page = str_replace('&amp;', '&', $next_page);
            $url = 'https://www.hitta.se' . $next_page;            

        }

        else
            $url = 'https://www.hitta.se/s%C3%B6k?vad='.$address.'&typ=prv&sida=1&changedTab=1';


        $result = $this->get_web_page($url);
        $html   = $result['content'];
        $dom1    = str_get_html($html);
        
        $page_links   = [];
        $page_link    = '';
        $living_type  = '';


        if(gettype($dom1) !== 'boolean'){

            if(!is_numeric(substr($original_address, 0, 1)))
                $original_address = preg_replace('/(\d+)/', '${1} ', $original_address);

            else{

                $add       = explode(' ', $original_address);
                $last_item = preg_replace('/(\d+)/', '${1} ', end($add));
                array_pop($add);
                $original_address = implode(" ",$add) . ' ' . $last_item;
            
            }

            foreach($dom1->find('.style_searchResult__KcJ6E') as $key => $element){

                $page_link = $element->find('.style_searchResultLink__2i2BY', 0)->href;
                $s_address = $element->find('.style_displayLocation__BN9e_', 0)->plaintext;

                
                if (strpos(trim($s_address), trim($original_address)) !== false){

                    // echo '   >>>>   ' . $s_address . ' == ' . $original_address . '    <<<<   ';
                    $result = $this->get_web_page('https://www.hitta.se/'.$page_link);
                    $html   = $result['content'];
                    $dom    = str_get_html($html);


                    $name            = !is_null($dom->find('.heading--1', 0)) ? $dom->find('.heading--1', 0)->plaintext : '';
                    $address_details = !is_null($dom->find('address', 0)) ? $dom->find('address', 0)->plaintext : '';
                    
                    $pos = preg_split("/\r\n|\n|\r/", $address_details);
                    
                    $address = $pos[0] ?? '';
                    
                    $city_postal = $pos[1] ?? '';
                    
                    if($city_postal){
                        $city = preg_replace('/[0-9]+/', '', $city_postal);
                        $postal = filter_var($city_postal, FILTER_SANITIZE_NUMBER_INT);
                    }


                    $address_type = ($dom->find('.styleManual_addressBox__qXjxb .mb-2', 0)) ? $dom->find('.styleManual_addressBox__qXjxb .mb-2', 0)->plaintext : '';

                    if (strpos($address_type, 'Lägenhetsnummer') !== false){
                        $address = $address . ' lgh ' . filter_var($address_type, FILTER_SANITIZE_NUMBER_INT);
                        $address_type = 'lgh';
                    }
                    if(trim($address_type) == 'Vägbeskrivning')
                        $address_type = '';

                    
                    $floor_area = '';

                    if (!is_null($dom->find('#floor_area h3', 0))){

                        $floor_area = $dom->find('#floor_area h3', 0)->plaintext;

                        $floor_area = filter_var(str_replace("m2", "", $floor_area), FILTER_SANITIZE_NUMBER_INT);

                    }


                    $user_json = strip_tags($dom->find('script[type=application/ld+json]', 0));

                    $user_data = json_decode($user_json, true);

                    $ssn    = str_replace('-', '', $user_data['birthDate'] ?? '');
                    $f_name = $user_data['givenName'] ?? '';
                    $l_name = $user_data['familyName'] ?? '';
                    $name   = $user_data['alternateName'] ?? '';
                    $phone  = $user_data['telephone'] ?? '';
                    $gender = $user_data['gender'] ?? '';
                    $dob    = $user_data['birthDate'] ?? '';
                    $age = '';
                    if($dob)
                        $age = $this->findAge($user_data['birthDate'] ?? '');

                    $search_url    = explode("/", $page_link);
                    $search_string = end($search_url);

                    
                    // Store data
                    if($name == ''){
                        $this->createLog($key,$input,'Scraper issue');
                    }
                    else{

                        $txt = trim($input)            . "\t" .
                               trim($search_string)    . "\t" .
                               trim($f_name)           . "\t" .
                               trim($l_name)           . "\t" .
                               trim($name)             . "\t" .
                               trim($ssn)              . "\t" .
                               trim($gender)           . "\t" .
                               trim($age)              . "\t" .
                               trim($address)          . "\t" .
                               trim($address_type)     . "\t" .
                               trim($postal)           . "\t" .
                               trim($city)             . "\t" .
                               trim($floor_area)       . "\t" .
                               trim($phone)            . "\t";

                        $myfile = fopen('./uploads/'.$file_name.'.txt', "a") or die("Unable to open file!");
                        
                        fwrite($myfile, $txt);
                        fwrite($myfile, "\n");
                        fclose($myfile);
                    }

                }
                else{
                    // echo ' Failing due to unmatch address  >>>>   ' . $s_address . ' == ' . $original_address . '    <<<<   ';;
                }
            }

            if(!is_null($dom1->find('div[data-trackcat="search-result-pagination"] a'))){

                $next_page = 0;
                
                foreach ($dom1->find('div[data-trackcat="search-result-pagination"] a') as $key => $elem) {

                    if(trim($elem->plaintext) !== 'Föregående')
                        $next_page = $elem->href;
                
                }

                // echo $next_page . '  =>  ' . $this->recursive_count . '                            ';

                if(trim($next_page)){

                    $this->recursive_count++;
                    $this->getData($input, $key, $file_name, trim($next_page));
                }
            }


        }
        else{
            
            $this->createLog($key,$original_address,'Proxy or Scraper not working');
            // sleep(10);
            // return;
        
        }

        return;        

    }

    public function createLog($key,$address,$page_link, $address_found = false)
    {
        
        $myfile = fopen('./logs/log.txt', "a") or die("Unable to open file!");

        $txt = $key . ' - ' . $address . ' - ' .  $page_link;

        fwrite($myfile, $txt);
        fwrite($myfile, "\n");
        fclose($myfile);

        // End Log

        if(!$address_found){

            $myfile  = fopen('./logs/failed.txt', "a") or die("Unable to open file!");

            fwrite($myfile, urldecode($address));
            fwrite($myfile, "\n");
            fclose($myfile);

            $myfile  = fopen('./logs/failed-log.txt', "a") or die("Unable to open file!");

            $address = $address . ',';
            fwrite($myfile, $address);
            fwrite($myfile, "\n");
            fclose($myfile);            

        }
        
    }

    public function getLastAddress()
    {
        
        $file_addresses = fopen('uploads/final.txt', "r") or die("Unable to open file!");

        $last_line = '';

        while (($line = fgets($file_addresses)) !== false)
            $last_line = $line;

        $last_address = [];
        $last_address = preg_split("/\t+/", $last_line);

        return $last_address[0] ?? '';

    }

}



    if(1){

        $input_file_name = php_uname('n');
        
        $file_name = "final";
        
        // $file = fopen('uploads/'.$file_name.'.txt', "w");
        
        // fclose($file);


        if($input_file_name == 'DESKTOP-AJFT9FC')
        
            $file_addresses = fopen("source/ubuntu-s-1vcpu-1gb-amd-fra1-01.txt", "r") or die("Unable to open file!");
        
        else{
        
            $input_file_name = str_replace("scraper", "input", $input_file_name);
            $input_file_name = 'source/' . $input_file_name . '.txt';
            $file_addresses = fopen($input_file_name, "r") or die("Unable to open file!");
        
        }


        $addresses   = [];
        $unique_addresses = [];


        while (($line = fgets($file_addresses)) !== false)
            $addresses[] = $line;


        foreach(array_unique($addresses) as $key => $address){

            $input         = trim($address);

            $result        = explode(' lgh', $address);

            if(strlen(trim($result[0])) <= 2)
                continue;

            $unique_addresses[] = $result[0];

        }

        // get the last line from final.txt
        $obj = new Scraper();
        $last_address = $obj->getLastAddress();
        $obj = NULL;

        $found = false;

        foreach(array_unique($unique_addresses) as $key => $address){

            if($last_address && !$found){
                
                if(trim($last_address) !== trim($address))
                    continue;
                
                else
                    $found = true;
            
            }
            
            $obj = new Scraper();

            $obj->getData($address, $key, $file_name);
            
            $obj = NULL;
            
        }

    }