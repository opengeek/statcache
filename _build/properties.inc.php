<?php
/**
 * Default properties for the StaticCache Plugin
 */
$properties = array(
    array(
        'name' => 'skipIfTagsRemain',
        'desc' => 'Skip Resources that have tags remaining in the content that is being cached for the Resource.',
        'type' => 'combo-boolean',
        'options' => '',
        'value' => false,
    ),
    array(
        'name' => 'skipTV',
        'desc' => 'Skip Resources that have a non-empty value in the Template Variable specified by name. Leave empty to only skip non-cacheable Resources.',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
    ),
    array(
        'name' => 'skipBinaryContentTypes',
        'desc' => 'Skip Resources that have a binary Content Type.',
        'type' => 'combo-boolean',
        'options' => '',
        'value' => false,
    ),
);

return $properties;