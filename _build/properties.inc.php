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
        'value' => true,
    ),
    array(
        'name' => 'cacheTV',
        'desc' => 'Cache all Resources that have a non-empty value in the Template Variable specified by name. This overrides all other options; leave empty to use other options.',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
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
        'value' => true,
    ),
    array(
        'name' => 'mimeTypes',
        'desc' => 'If specified and non-empty, only cache Resources with the specified mime-types. Accepts a comma-delimited list of mime-types.',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
    ),
    array(
        'name' => 'contentTypes',
        'desc' => 'If specified and non-empty, only cache Resources with the specified ContentType id\'s. Accepts a comma-delimited list of ContentType id\'s.',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
    ),
    array(
        'name' => 'regenerate',
        'desc' => 'Regenerate static files instead of removing them when clearing site cache.',
        'type' => 'combo-boolean',
        'options' => '',
        'value' => false,
    ),
);

return $properties;
