<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function form_open($action = '', $attributes = []) {
    $action = $action ?: current_url();
    $attributesString = '';
    
    foreach ($attributes as $key => $value) {
        $attributesString .= " {$key}=\"" . htmlspecialchars($value) . "\"";
    }
    
    return "<form action=\"" . htmlspecialchars($action) . "\" method=\"POST\"{$attributesString}>";
}

function form_close() {
    return '</form>';
}

function form_input($data = [], $value = '') {
    $attributes = '';
    foreach ($data as $key => $val) {
        if ($key !== 'value') {
            $attributes .= " {$key}=\"" . htmlspecialchars($val) . "\"";
        }
    }
    $value = isset($data['value']) ? $data['value'] : $value;
    return "<input value=\"" . htmlspecialchars($value) . "\"{$attributes}>";
}

function form_textarea($data = [], $value = '') {
    $attributes = '';
    foreach ($data as $key => $val) {
        if ($key !== 'value') {
            $attributes .= " {$key}=\"" . htmlspecialchars($val) . "\"";
        }
    }
    $value = isset($data['value']) ? $data['value'] : $value;
    return "<textarea{$attributes}>" . htmlspecialchars($value) . "</textarea>";
}

function form_dropdown($name, $options = [], $selected = '', $attributes = []) {
    $attributesString = '';
    foreach ($attributes as $key => $value) {
        $attributesString .= " {$key}=\"" . htmlspecialchars($value) . "\"";
    }
    
    $html = "<select name=\"" . htmlspecialchars($name) . "\"{$attributesString}>";
    
    foreach ($options as $value => $label) {
        $selectedAttr = ($value == $selected) ? ' selected' : '';
        $html .= "<option value=\"" . htmlspecialchars($value) . "\"{$selectedAttr}>" . htmlspecialchars($label) . "</option>";
    }
    
    $html .= "</select>";
    return $html;
}

