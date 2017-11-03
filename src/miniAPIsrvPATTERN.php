<?php

// This file contains PATTERN for building simple API-server listener.

// See below this sections:
// 1) config
// 2) read request (script depends on your http-server)
// 3) authorization script
// 4) API-methods router
// 5) response output

// ---- CONFIG BEGIN ----

// response format ( see function api_return )
$GLOBALS['api_ret_mode'] = 'http'; // or 'html', 'jsonrpc1', 'jsonrpc2'

// Who can access API? Array of enabled ip
$api_enabled_ip_arr = [
    '127.0.0.1',
    '192.168.1.10',
    // ...
];

// What methods API can do?

// For each enabled method set one file, where defined function with methods name
$api_methods_file = __DIR__ . DIRECTORY_SEPARATOR . 'miniAPIsrvExample.php';
$api_methods_enabled = [
    'test'  => $api_methods_file,
    'test1' => $api_methods_file,
    'test2' => $api_methods_file,
    'test3' => $api_methods_file,
    'test4' => $api_methods_file,
    'sum' => $api_methods_file,
    'div' => $api_methods_file,
    // ...
];

// ---- CONFIG END -----

// ---- READ REQUEST MAIN-PARAMETERS ----

// Read requests parameters required for authorization.
// Using $api_req array in global namespace. See also readAPIparams below
$GLOBALS['api_req'] = [
    // Reading depends on your server:
    'ip' => $_SERVER['REMOTE_ADDR'],
    'uri' => $_SERVER['REQUEST_URI'],
    'ua' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
    'host' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '',
    // ...
];

// ---- AUTHORIZATION BEGIN ----

// Place your authorization script here. For example, check ip whitelist.
if (!\in_array($GLOBALS['api_req']['ip'], $api_enabled_ip_arr))
    die(api_error(403, "Access denied"));

// ---- AUTHORIZATION END ----

// ---- read other parameters of request ----

/**
 * This function is for reading request parameters from server envelopment
 *
 * Returns: empty array if okay, or array with names of missing parameters names
 *
 * @param string $required_str Required-parameters names list in string by divider
 * @param string $optional_str Optional-parameters names list, divider see below
 * @param string $div Divider string to divide names in previous strings
 * @return array
 */
function readAPIparams($required_str, $optional_str = '', $div = ',') {
    $required_arr = explode($div, $required_str);
    $not_found_arr = [];
    foreach($required_arr as $name) {
        if (!isset($_REQUEST[$name])) {
            $not_found_arr[] = $name;
        }
    }
    if (count($not_found_arr)) {
        return $not_found_arr;
    }
    foreach(array_merge($required_arr, explode($div, $optional_str)) as $name) {
        $GLOBALS['api_req'][$name] = isset($_REQUEST[$name]) ? $_REQUEST[$name] : null;
    }
    return [];
}

$method_par_name = 'method';

// reading request parameters: method,params,id ('method'-parameter is required)
if (count(readAPIparams($method_par_name, 'params,id'))) {
    // if 'method'-parameter is missing, returns error
    die(api_error(-32600, "Invalid Request"));
}

$method = $GLOBALS['api_req'][$method_par_name];

if (!isset($api_methods_enabled[$method])) {
    die(api_error(-32601, "Method not found"));
}

// handler for catch unexpected errors
ob_start('api_err_handler');
ini_set('html_errors', 0); // stop html-style errors output, use plain text

// include functions-file defined for current method
include_once $api_methods_enabled[$method];

// call method by calling function with methods name
$ret_arr = \call_user_func($method);

// set flag to disactivate err_handler
$GLOBALS['api_fn_complete'] = true;

// get data from output buffer and clear
$buffer = ob_get_flush();

// Results routing

if (!empty($ret_arr['error'])) {
    // if the called API function returned an error
    $err_message = $ret_arr['error'];
    $err_code = isset($ret_arr['code']) ? $ret_arr['code'] : 500;
    $err_data = isset($ret_arr['data']) ? $ret_arr['data'] : null;
    die(api_error($err_code, $err_message, $err_data, $GLOBALS['api_req']['id']));
}

if(!isset($ret_arr['result'])) {
    // if the called API function retruns invalid (no error and no result)
    die(api_error(-32001, "Incorrect result", $ret_arr));
}

if (!empty($buffer)) {
    // if output-buffer contains unexpected output, reports error
    die(api_error(500, "Unexpected output", $buffer, $GLOBALS['api_req']['id']));
}

// if called API function returned 'result' return it and exit
die(api_result($ret_arr['result'], $GLOBALS['api_req']['id']));

// function for return methods result
function api_result($result, $id = null) {
    return api_return($result, $id);
}

// function for return error
function api_error($err_code, $err_message, $err_data = null, $id = null) {
    $err = ['code'=>$err_code, 'message'=>$err_message];
    if (!is_null($err_data)) $err['data'] = $err_data;
    return api_return($err, $id, 'error');
}

// function for return result by specified protocol (jsonrpc2 recomended)
function api_return($data, $id = null, $ret_mode = 'result') {
    switch ($GLOBALS['api_ret_mode']) {
        case 'jsonrpc2':
            // jsonrpc v2.0 format
            return json_encode([
                'jsonrpc' => '2.0',
                $ret_mode => $data,
                'id' => $id
            ]);
        case 'jsonrpc1':
            return json_encode([
                'result' => ($ret_mode === 'result') ? $data : null,
                'error' => ($ret_mode === 'error') ? $data : null,
                'id' => $id
            ]);
        case 'http':
            if($ret_mode === 'error') {
                $code = $data['code'] . ' ' . $data['message'];
                $body = isset($data['data']) ? $data['data'] : '';
                $body = "<H1>$code</H1><pre>$body";
            } else {
                $code = '200 OK';
                $body = '<pre>' . $data;
            }
            header("HTTP/1.1 $code");
            die($body);
        default:
            // plain html for browser debugging
            return '<pre>' . print_r([$ret_mode => $data, 'id' => $id], true);
    }
}

// handler for catching unexpected errors
function api_err_handler($buffer) {
    if (!empty($GLOBALS['api_fn_complete'])) return '';
    return api_error(-32000, "Unexpected error", $buffer, $GLOBALS['api_req']['id']);
}
