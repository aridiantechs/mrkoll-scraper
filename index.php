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

        $result  = curl_exec( $ch );
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
        $input = $address;

        $url = 'https://mrkoll.se/resultat?n='.$address.'&c=&min=16&max=120&sex=a&c_stat=all&company=';

        $result = $this->get_web_page($url);
        $html   = $result['content'];
        $dom    = str_get_html($html);
        
        $page_links   = [];

        if(gettype($dom) !== 'boolean'){

            $check_empty = $dom->find('.name_head1.name_fix span.f_line1');
            
            if(empty($check_empty))
                return;


            $tokens = strip_tags($dom->find('script[type=application/javascript]', 2));

            $tokens = explode('\'', $tokens);

            $p = $tokens[1];

            $k = $tokens[3];

            $url    = 'https://mrkoll.se/ajax/lastDigits/?p=.'.$p.'.&k='.$k;
            $result = $this->get_web_page($url);
            
            $ssn_remaining = $result['content'];

            $name = $dom->find('.name_head1.name_fix span.f_line1', 0)->plaintext;
            $dob  = $dom->find('.personInfo.pBlock1 strong', 0)->plaintext;

            foreach($dom->find('.block_col1 .f_head1') as $key => $element){

                $address = ($element->plaintext == 'Folkbokföringsadress') ? ($dom->find('.f_line2', $key)->plaintext . ' ' . $dom->find('.f_line2', $key+1)->plaintext) : '';
                break;

            }


            foreach($dom->find('.f_head1') as $key => $element){

                if($element->plaintext == 'Kommun')
                    $municipality = $dom->find('.f_line2', $key + 1)->plaintext;

                if($element->plaintext == 'Län')
                    $county       = $dom->find('.f_line2', $key + 1)->plaintext;

                if($element->plaintext == 'Personnummer')
                    $ssn          = $dom->find('.f_line2', $key + 1)->plaintext;

                if($element->plaintext == 'Kön')
                    $gender       = $dom->find('.f_line2', $key + 1)->plaintext;

                if($element->plaintext == 'Flyttdatum')
                    $moving_date  = $dom->find('.f_line2', $key + 1)->plaintext;
                
            }

            $ssn = str_replace('XXXX', $ssn_remaining, $ssn);
            
            if (1){
                // Store data
                if($name == ''){
                    $this->createLog($key,$input,'Scraper issue');
                }
                else{

                    $txt = trim($input ?? '')         . "\t" .
                           trim($name ?? '')          . "\t" .
                           trim($dob ?? '')           . "\t" .
                           trim($address ?? '')       . "\t" .
                           trim($municipality ?? '')  . "\t" .
                           trim($county ?? '')        . "\t" .
                           trim($ssn ?? '')           . "\t" .
                           trim($gender ?? '')        . "\t" .
                           trim($moving_date ?? '')   . "\t" ;

                    $myfile = fopen('./uploads/'.$file_name.'.txt', "a") or die("Unable to open file!");
                    
                    fwrite($myfile, $txt);
                    fwrite($myfile, "\n");
                    fclose($myfile);

                    return;
                
                }
            }

        }
        else{
            // echo ' Failing due to unmatch address  >>>>   ' . $s_address . ' == ' . $original_address . '    <<<<   ';;
        }
            


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
        $file = fopen('uploads/'.$file_name.'.txt', "w");
        fclose($file);


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


        

        // get the last line from final.txt
        $obj = new Scraper();
        $last_address = $obj->getLastAddress();
        $obj = NULL;

        $found = false;

        foreach(array_unique($addresses) as $key => $address){

            if($last_address && !$found){
                
                if(trim($last_address) !== trim($address))
                    continue;
                
                else
                    $found = true;
            
            }
            
            $obj = new Scraper();
   
            $obj->getData(trim($address), $key, $file_name);
            
            $obj = NULL;
            
        }

    }