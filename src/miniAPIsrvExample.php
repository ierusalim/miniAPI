<?php

function test() {
    return ['result' => 'Test normal complete'];
}

function test1() {
    echo "bla bla bla";
    return ['result' => 'Test with unexpected output'];
}

function test2() {
    $d = 1/0; // breaking on this string and return error
    return ['result' => 'Division by zero test'];
}

function test3() {
    return ['error' => "Test error report"];
}

function test4() {
    // echo request parameters
    $ret = ['result' => print_r($GLOBALS['api_req'], true)];
    return $ret;
}

function sum() {
    $params = $GLOBALS['api_req']['params'];

    $a = isset($params['a']) ? $params['a'] : null;
    $b = isset($params['b']) ? $params['b'] : null;

    if (!is_numeric($a) || !is_numeric($b)) {
        return ['error' => 'Need parametes params[a] and params[b], both must be numeric'];
    }

    $result = $a + $b;

    return compact('result');
}

function div() {
    // read http-parameters directly from $_REQUEST (not recomended)
    $a = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;
    $b = isset($_REQUEST['b']) ? $_REQUEST['b'] : null;

    if (!is_numeric($a) || !is_numeric($b)) {
        return ['error' => 'Need a and b http-parametes, it must be numeric'];
    }

    $result = $a / $b;

    return compact('result');
}