<?php
namespace App\Helpers\Pasargad;
use Illuminate\Http\Request;

class Parser
{

    public static function makeXMLTree($data , Request $request)
    {
        $ret = [];
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, $data, $values, $tags);
        xml_parser_free($parser);
        $hash_stack = [];
        $temp = [
            "invoiceNumber" => $request->get('iN'),
            "invoiceDate" => $request->get('iD')
        ];

        foreach ($values as $key => $val) {
            switch ($val['type']) {
                case 'open':
                    array_push($hash_stack, $val['tag']);
                    break;
                case 'close':
                    array_pop($hash_stack);
                    break;
                case 'complete':
                    array_push($hash_stack, $val['tag']);
                    if(!isset($val['value'])){
                        $val['value'] = $temp[$val['tag']];
                    }

                    eval("\$ret['" . implode($hash_stack, "']['") . "'] = '{$val['value']}';");
                    array_pop($hash_stack);
                    break;
            }
        }

        return $ret ;
    }


    public static function post2https($fields_arr, $url)
    {
        $fields_string = '';
        foreach ($fields_arr as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        $fields_string = substr($fields_string, 0, -1);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields_arr));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); //php 7.1
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


        $res = curl_exec($ch);

        curl_close($ch);
        return $res;
    }
}
