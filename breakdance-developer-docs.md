# Breakdance Developer Documentation

This repository contains documentation for Breakdance's public APIs.

## Table of Contents

- [Menu JavaScript API](#menu-javascript-api)
- [Animations JavaScript API](#animations-javascript-api)
- [Conditions API](#conditions-api)
- [Dynamic Data API](#dynamic-data-api)
- [Form Actions API](#form-actions-api)
- [Reusable Dependencies](#reusable-dependencies)
- [Hooks](#hooks)
    - [Other Hooks](#other-hooks)
    - [Posts List Element Hooks](#posts-list-element-hooks)
    - [Shape Dividers Hooks](#shape-dividers-hooks)
    - [Form Builder Hooks](#form-builder-hooks)

# Menu JavaScript API

After the page is loaded it is possible to access the Breakdance Menu's instance on its HTMLElement:

## Examples

```js
const menu = document.querySelector('.breakdance-menu').bdMenu;
```

```js
const dropdown = document.querySelector('.bde-menu-dropdown-72-105 .breakdance-dropdown');

menu.openDropdown(dropdown);

menu.closeDropdown(dropdown);

menu.closeAll();

menu.toggleMobileMenu();
```

## API

### openDropdown(...)

```typescript
openDropdown(node: HTMLElement)
```

Open a dropdown menu. It takes in an HTMLElement and then closes all other dropdowns except for the selected one. Works on mobile too.

| Param      | Type        |
| ---------- | ----------- |
| **`node`** | HTMLElement |

### closeDropdown(...)

```typescript
closeDropdown(node: HTMLElement)
```

Close a dropdown menu. It takes in an HTMLElement. Works on mobile too.

| Param      | Type        |
| ---------- | ----------- |
| **`node`** | HTMLElement |

### closeAll()

```typescript
closeAll();
```

Close all dropdown menus.

### getOpenDropdown()

```typescript
getOpenDropdown() => DropdownElement | null
```

Return the currently open dropdown element or null if none is open.

### refreshDropdowns()

```typescript
refreshDropdowns();
```

Refresh all dropdown menus. It recalculates the open dropdown width and aligns each dropdown element with the menu's container.

### toggleMobileMenu()

```typescript
toggleMobileMenu() => Promise<void>
```

Open or close the mobile menu. Returns as promise that is resolved once the animation has ended.

### isDesktop()

```typescript
isDesktop() => boolean
```

Determine whether the menu is showing the desktop version.

### isMobile()

```typescript
isMobile() => boolean
```

Determine whether the menu is showing the mobile version.

### isVertical()

```typescript
isVertical() => boolean
```

Determine whether the menu is set to be displayed vertically.

# Animations JavaScript API

Breakdance provides a simple way to replay entrance animations.

## Examples

Replay Animations on a Specific Element

```js
const panel = document.querySelector('.bde-accordion__panel');
const event = new Event('breakdance_play_animations', { bubbles: true });
panel.dispatchEvent(event);
```

Replay Animations on the Entire Website

```js
const event = new Event('breakdance_play_animations', { bubbles: true });
document.dispatchEvent(event);
```

Reset Entrance Animations

In addition to replaying animations, Breakdance also allows you to reset entrance animations to their initial hidden state. This is particularly useful when you want animations to play again when entering the viewport.

```js
const panel = document.querySelector('.bde-accordion__panel');
const event = new Event('breakdance_reset_animations', { bubbles: true });
panel.dispatchEvent(event);
```

**New in 2.0**: Animations are now automatically retriggered in the following elements:

- Slider elements
- Advanced Accordion element
- Tabs elements
- Popup element

Animations within these elements will automatically retrigger under the appropriate conditions (e.g., when a new slide appears, an accordion panel opens, etc.).

# Conditions API

## Table Of Contents

1. [Overview](#overview)
2. [Element Display Conditions](#element-display-conditions)
3. [Query Builder Conditions](#query-builder-conditions)
4. [Templating Conditions](#templating-conditions)

## Overview

Conditions can be created using the `breakdance_register_template_types_and_conditions` action hook and the `Breakdance\ConditionsAPI\register` function.

## Element Display Conditions

Breakdance users can show or hide elements on the frontend of their website based on conditions.

Add an element display condition as follows:

```php
add_action(
    'breakdance_register_template_types_and_conditions',
    function() {

        \Breakdance\ConditionsAPI\register(
            [
                'supports' => ['element_display'],
                'slug' => 'unique-prefix-my-condition', // MUST BE UNIQUE
                'label' => 'My Condition',
                'category' => 'My Category',
                'operands' => ['equals', 'not equals'],

                // providing a dropdown of values is optional. if 'values' is not provided, a text input will be provided instead of a dropdown
                'values' => function() { return [
                    [
                        'label' => 'Item Group Title',
                        'items' => [
                            [
                                'text' => 'Item 1',
                                'value' => 'item-1'
                            ],
                            [
                                'text' => 'Item 2',
                                'value' => 'item-2'
                            ]
                        ]
                    ],
                    [
                        'label' => 'Item Group Title 2',
                        'items' => [
                            [
                                'text' => 'Another Item',
                                'value' => 'another-item'
                            ],
                            [
                                'text' => 'Different Item',
                                'value' => 'different-item'
                            ]
                        ]
                    ]
                ]; },

                /*
                when specifying possible values for a dropdown,
                you can optionally make the dropdown a multiselect
                */
                'allowMultiselect' => true,

                /*
                this function will be called to evaluate the condition
                if it returns true, the element will be shown
                if it returns false, the element will be hidden
                */
                'callback' => function(string $operand, $value) {

                    $myVal = 'item-1'; // usually, you'd get $myVal from somewhere, i.e. global $post; $myVal = $post->ID;

                    /*
                    if allowMultiselect is false, $value will be a string.
                    use it like so:

                    if ($operand === 'equals') {
                        return $myVal === $value;
                    }

                    if ($operand === 'not equals') {
                        return $myVal !== $value;
                    }
                    */

                    /*
                    in our example, allowMultiselect is true, which means $value will be an array of strings
                    */
                    if ($operand === 'equals') {
                        return in_array($myVal, $value);
                    }

                    if ($operand === 'not equals') {
                        return !in_array($myVal, $value);
                    }

                    return false;
                },
            ]
        );

    }
);
```

## Query Builder Conditions

A public API for query builder conditions is planned for the future.

## Templating Conditions

A public API for templating conditions is planned for the future.

# Dynamic Data API

## Overview

Creating a Breakdance Dynamic Field involves two steps

1. Create a field class to represent your field
2. Register the field class with Breakdance

An example WordPress plugin that adds Dynamic Data fields can be found at https://github.com/soflyy/breakdance-sample-dynamic-data

# Creating a Field Class

To get started create a new PHP class. Your class will need to extend one of the Breakdance base classes in order for Breakdance to know how to work with your data, and which Breakdance Elements it will be compatible with.

## Base Field Classes

### StringField

`\Breakdance\DynamicData\StringField`

This is for any generic string data

**Handler Return Type**

`\Breakdance\DynamicData\StringData`

The StringData object has a `$value` property that must be assigned to a string.

**String Data Helpers**

- `StringData::fromString(string $string);` will return an instance of StringData with the value property set to $string
- `StringData::emptyString();` Will return an instance of StringData with the value set to an empty string

**Example Class**

```php
use Breakdance\DynamicData\StringField;
use Breakdance\DynamicData\StringData;

class MyDynamicField extends StringField
{
    /**
     * @return string
     */
    public function label()
    {
        return 'Dynamic String';
    }

    /**
     * @return string
     */
    public function category()
    {
        return 'My Plugin';
    }

    /**
     * @return string
     */
    public function slug()
    {
        return 'my_plugin_string';
    }

    /**
     * array $attributes
     */
    public function handler($attributes): StringData
    {
        return StringData::fromString('some string value');
    }
}
```

### ImageField

`\Breakdance\DynamicData\ImageField`

This is for fields that return an image

**Handler function return type**

`\Breakdance\DynamicData\ImageData`

**ImageData Properties**

The properties that are _required_ for dynamic images to work with Breakdance Image elements are the $url and $sizes properties.

`$url` is the URL to the source image

`$sizes` is an array with keys for each size slug, and each value is an array containing 'file', 'width', 'height', and 'mime-type'.

It is recommended to use WordPress attachments and the fromAttachmentId helper for Dynamic Images

**Image Data Helpers**

- `ImageData::fromAttachmentId($attachmentId)` accepts an attachmentId as a string or integer and returns an instance of ImageData populated with all the required data from the attachment.

**Example Class**

```php
use Breakdance\DynamicData\ImageField;
use Breakdance\DynamicData\ImageData;

class MyDynamicField extends ImageField
{
    /**
     * @return string
     */
    public function label() {
        return 'Dynamic Image';
    }

    /**
     * @return string
     */
    public function category() {
        return 'My Plugin';
    }

    /**
    * @return string
    */
    public function slug()
    {
        return 'my_plugin_image';
    }

    /**
     * @param array $attributes
     */
    public function handler($attributes): ImageData {

        // build from attachment data
        $attachmentData = wp_prepare_attachment_for_js($attachmentId);
        $imageData = new ImageData;
        $imageData->id = (string) $attachmentData['id'];
        $imageData->filename = $attachmentData['filename'];
        $imageData->alt = $attachmentData['alt'];
        $imageData->caption = $attachmentData['caption'];
        $imageData->url = $attachmentData['url'];
        $imageData->type = $attachmentData['type'];
        $imageData->mime = $attachmentData['mime'];
        $imageData->sizes = $attachmentData['sizes'];

        // or using the helper
        $imageData = ImageData::fromAttachmentId($attachmentId);

        return $imageData;
    }
}
```

### GalleryField

The Gallery field is for fields that return multiple images to be used with Slideshow or Gallery elements.

**Handler Function Return Type**

`\Breakdance\DynamicData\GalleryData`

GalleryData has a property `$images` that must be set to an array of `\Breakdance\DynamicData\ImageData` objects

**Example Class**

```php
use Breakdance\DynamicData\GalleryField;
use Breakdance\DynamicData\GalleryData;

class MyDynamicField extends GalleryField
{
    /**
     * @return string
     */
    public function label() {
        return 'Dynamic Gallery';
    }

    /**
     * @return string
     */
    public function category() {
        return 'My Plugin';
    }

    /**
    * @return string
    */
    public function slug()
    {
        return 'my_plugin_gallery';
    }

    /**
     * @param array $attributes
     */
    public function handler($attributes): GalleryData {
        // fetch IDs for images attached to the current post
    $attachedImages = get_attached_media('image', get_the_ID());

        $gallery = new GalleryData();
        // map WordPress attachments to ImageData
        $gallery->images = array_map(static function($attachment) {
            return ImageData::fromAttachmentId($attachment->ID);
        }, $attachedImages);

        return $gallery;
    }
}
```

### OembedField

The Oembed field is for fields that return a video, either by URL to an oembed provider (e.g YouTube) or directly to a video file from the media library.

**Handler Function Return Type**

`\Breakdance\DynamicData\OembedData`

**OembedData Properties**

The properties that are _required_ for Videos to work with Breakdance video elements are the $embedUrl, $type, and, if the video is from an oembed source, $provider.

`$embedUrl` is the URL to the oembed or video source

`$type` The video type, either `video` for direct video embed, or `oembed`

**Helpers**

`OembedData::fromOembedUrl($url)` - will attempt to retrieve the oembed data via the passed `$url` using an XHR request. This is useful for video providers [e.g](http://e.gh) YouTube or Vimeo

`OembedData::emptyOembed()` - this will return an OembedData object with empty properties.

**Example Class**

```php
use Breakdance\DynamicData\OembedField;
use Breakdance\DynamicData\OembedData;

class MyDynamicField extends OembedField
{
    /**
     * @return string
     */
    public function label() {
        return 'Dynamic Video';
    }

    /**
     * @return string
     */
    public function category() {
        return 'My Plugin';
    }

    /**
    * @return string
    */
    public function slug()
    {
        return 'my_plugin_video';
    }

    /**
     * @param array $attributes
     */
    public function handler($attributes): OembedData {
        // retrieve the video from your plugin or application
        $videoData = get_the_video_data();

        // create a new OembedData object
        $oembedData = new OembedData;
        $oembedData->title = $videoData['title'];
        $oembedData->provider = 'video';
        $oembedData->embedUrl = $videoData['url'];
        $oembedData->thumbnail = $videoData['thumbnail'];
        $oembedData->format = 'video/mp4';
        $oembedData->type = 'video';

        // or using the helper
        $oembedData = OembedData::fromOembedUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

        return $oembedData;
    }
}
```

## Required Methods

There are four mandatory methods that must be implemented in your field class

label - takes no argument and returns a string that will be used to identify your fields in the Dynamic Data Field selection window

```php
/**
 * @return string
 */
public function label() {
    return 'My Dynamic Field';
}
```

category - takes no arguments and returns a string for the category to group the fields in

```php
/**
 * @return string
 */
public function category() {
    return 'My Plugin';
}
```

slug - takes no arguments and returns a string to identify the field handler for output. This should be unique across all dynamic data fields, so it is recommended to prefix the slug appropriately.

```php
/**
 * @return string
*/
public function slug()
{
    return 'my_plugin_field';
}
```

handler - accepts an array of attributes and returns a data object (see below) that matches the parent field type

```php
/**
 * @param array $attributes
*/
public function handler($attributes): StringData {
    return StringData::fromString('My Dynamic Field Output');
}

```

## Optional Methods

**Subcategory**

If you wish to further group your dynamic fields within the dialog, you can optionally include a sub category.

```php
/**
 *@return string
 */
public function subcategory()
{
    return 'My dynamic field subcategory';
}
```

**Return Types**

The return types determine which elements will display in the dialog for the selected element. A default return type is configured for each base class type. More on the available return types below.

```php
/**
 *@return string
 */
public function returnTypes()
{
    return ['string'];
}
```

## Return Types

The return types determine which elements will have access to a dynamic field. For example, an Image Element will only be able to accept data from fields that return the Image return type. The returnTypes method returns an array, so a field may have multiple return types. For example, String and URL.

`string` - Generic string, default return type for StringData

`image_url` - Array of image properties, default return type for ImageData

`gallery` - Array of ImageData, default return type for GalleryData

`video` - Array of Video or Oembed data. Default return type for Oembed Fields

`url` - String that represents a URL. Compatible with StringData

`query` - String that represents a URL query. Compatible with StringData

`google_map` - String that represents an address or Latitude/Longitude location. Compatible with StringData and works with the Google Map Element

## Registering The Field Class With Breakdance

Once your class is created, registering the field is simple, just call the following registerField helper and pass in a new instance of your field class.The field will now be available as an option in the Dynamic Data modal dialog.

**Note:** To prevent file loading race conditions, it is recommended to call the register helper from inside a WordPress action, e.g init.

```php
add_action('init', function() {
    // Check if Breakdance is installed and class/function exists
    if (!function_exists('\Breakdance\DynamicData\registerField') || !class_exists('\Breakdance\DynamicData\Field')) {
        return;
    }

    \Breakdance\DynamicData\registerField(new MyField());
}
```

## Multiple Fields With The Same Handler

In some cases you may wish to register multiple fields that are of the same data type and have the same handler function. Instead of creating a class for each field, you can create a single class and register multiple instances of the same class.

```php
// MyDynamicField.php

use Breakdance\DynamicData\StringField;
use Breakdance\DynamicData\StringData;

class MyDynamicField extends \Breakdance\DynamicData\StringField
{

    protected array $fieldData;

    /**
     *@return string
     */
    public function __construct($fieldData)
  {
      $this->fieldData = $fieldData;
  }

    /**
     * @return string
     */
    public function label()
    {
        return $this->fieldData['label'];
    }

    /**
     * @return string
     */
    public function category()
    {
        return 'My Plugin';
    }

    /**
     * @return string
     */
    public function subcategory()
    {
        return $this->fieldData['group'];
    }

    /**
     * @return string
     */
    public function slug()
    {
        return 'my_plugin_field_' . $this->fieldData['slug'];
    }
    /**
     * @return string
     */
    public function handler($attributes): StringData
    {
        $value = (string) get_the_field_value($this->fieldData['slug']);
        return StringData::fromString($value);
    }
}

// register-fields.php included by your plugin

add_action('init', function() {
    // fail if Breakdance is not installed and available
    if (!function_exists('\Breakdance\DynamicData\registerField') || !class_exists('\Breakdance\DynamicData\Field')) {
        return;
    }

    require_once('my-dynamic-field.php');

    $myFields = [[
        'label' => 'My Field One',
      'slug' => 'my_field_slug_one',
      'group' => 'My Plugin',
    ],[
        'label' => 'My Field Two',
      'slug' => 'my_field_slug_two',
      'group' => 'My Plugin',
    ]];

    // loop through fields array and register fields
    foreach ($myFields as $fieldData) {
        \Breakdance\DynamicData\registerField(new MyField($fieldData));
    }
});
```

# Form Actions API

Creating a Breakdance Form Action involves two steps

1. Create an Action class to represent your field
2. Register the action class with Breakdance

# Creating an action class

To get started create a new PHP class. Your class will need to extend the Breakdance base Action class `Breakdance\Forms\Actions\Action`

## Required Methods

There are three mandatory methods that must be implemented in your action class

**name**

The name method takes no arguments and returns a string that will be used to identify your action in the Form Builder Actions dropdown menu

```php
/**
 * @return string
 */
public function name() {
    return 'My Action';
}
```

**slug**

The slug method takes no arguments and returns a string to identify the form action. This should be unique across all form actions, so it is recommended to prefix the slug appropriately.

```php
/**
 * @return string
*/
public function slug()
{
    return 'my_plugin_form_action';
}
```

**run**

The run method accepts three arguments, `$form, $settings, $extra` and is called when the form has been submitted

```php
/**
* Log the form submission to a file
*
* @param array $form
* @param array $settings
* @param array $extra
* @return array success or error message
*/
public function run($form, $settings, $extra)
{
    try {
        $this->writeToFile($extra['formId'], $extra['fields']);
    } catch(Exception $e) {
        return ['type' => 'error', 'message' => $e->getMessage()];
    }

    return ['type' => 'success', 'message' => 'Submission logged to file'];
}

```

### Run Arguments

$**form**

The form argument contains all the form fields, their builder settings and the selected values

- type: the field type
- name: the field name
- options: an array of available options for checkbox, radio or select inputs
- value: the submitted value of the field
- originalValue: the default/original value of the field

**$settings**

The settings argument contains an array of the configured form settings from the Breakdance builder

**$extra**

The extra argument contains additional data

- files: An array of uploaded files
- fields: the submitted form fields in an `$id ⇒ $value` style array
- formId: The ID of the form
- postId: The ID of the post the form was submitted from
- ip: the submitters IP address
- referer: The form submitters referrer URL
- userAgent: The form submitters user agent string
- userId: The form submitters user ID (if applicable)

### Responses

The response should be an array that contains a `type` and `message` key.

- type: either `error` or `success`
- message: a string message that will be displayed to admins with the submission

**Success**

```php
public function run($form, $settings, $extra)
{
    ...
    return ['type' => 'success', 'message' => 'Submission logged to file'];
}

```

**Error**

```php
public function run($form, $settings, $extra)
{
    ...
    return ['type' => 'error', 'message' => 'Could not write to file'];
}
```

## Register The Action

Register the action by calling the registerAction helper and passing an instance of your action class

**Note:** To prevent file loading race conditions, it is recommended to call the register helper from inside a WordPress action, e.g init.

```php

// register-actions.php included by your plugin

add_action('init', function() {
    // fail if Breakdance is not installed and available
    if (!function_exists('\Breakdance\Forms\Actions\registerAction') || !class_exists('\Breakdance\Forms\Actions\Action')) {
        return;
    }

    require_once('my-action.php');

    \Breakdance\Forms\Actions\registerAction(new MyAction());

});
```

# Reusable Dependencies

Register reusable dependencies that can be used in any element on Element Studio.

## How to register a new reusable dependencies

```php
add_action('breakdance_reusable_dependencies_urls', function ($urls) {
   $urls['bootstrap'] = 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/js/bootstrap.min.js';
   return $urls;
});
```

## How to use a reusable dependencies

1. Open Element Studio
2. Navigate to the "Dependencies" tabs
3. Add a new dependency
4. In the Script URL field, write %%BREAKDANCE_REUSABLE_BOOTSTRAP%% (replace bootstrap with the name of your dependency)

We have some predefined reusable dependencies that you can use:

- %%BREAKDANCE_REUSABLE_GSAP%%
- %%BREAKDANCE_REUSABLE_SCROLL_TRIGGER%%

## How to change GSAP version

If you want to change the version of GSAP loaded globally by Breakdance, you can do so by using the following code:

```php
add_action('breakdance_reusable_dependencies_urls', function ($urls) {
   $urls['gsap'] = 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.8.0/gsap.min.js';
   return $urls;
});
```

By doing so, you will also need to update the ScrollTrigger version to match the GSAP version.

## Notes

1. You don't need to register an reusable dependency if you don't want to reuse it in other elements in Element Studio. You can just use the URL directly in your element.
2. Variables that are defined in camelCase must be used as snake_case on Element Studio.

# Hooks

## Other Hooks

### breakdance_singular_content

```php
add_filter("breakdance_singular_content", function ($content) {
    if ($something) {
        return $content;
    } else {
        return "not authorized";
    }
});
```

### breakdance_after_save_document

```php
add_action("breakdance_after_save_document", function ($postId) {
    // the save button in Breakdance was clicked and the post was saved
});
```

### breakdance_register_font

```php
add_filter("breakdance_register_font", function ($font) {

    // disable all Google Fonts
    $isGoogleFont = !!$font['dependencies']['googleFonts'];

    if ($isGoogleFont) {
        return false;
    }

    return $font;
});
```

### breakdance_append_dependencies

```php
add_filter("breakdance_append_dependencies", function ($dependenciesToAppend) {
    // you could use this to modify or remove dependencies
    echo "<pre>";
    print_r($dependenciesToAppend);
    echo "</pre>";
    return $dependenciesToAppend;
});
```

### breakdance_element_classnames_for_html_class_attribute

```php
add_filter("breakdance_element_classnames_for_html_class_attribute", function ($classNames) {
    $classNames[] = 'another-class';
    return $classNames;
});
```

### breakdance_query_builder_input_query

The example below is copied from the Breakdance core code:

```php
add_filter('breakdance_query_builder_input_query', '\Breakdance\Integrations\FacetWp\enableFacetWpForCustomQueries');

function enableFacetWpForCustomQueries($query)
{
  if (is_string($query)) {
    return $query . "&facetwp=true";
  } else {
    $query['facetwp'] = true;
    return $query;
  }
}
```

## Posts List Element Hooks

Various hooks are available to inject HTML output into the Breakdance Posts List element

Available hooks:

- breakdance_posts_list_before_loop
- breakdance_posts_list_before_post
- breakdance_posts_list_after_image
- breakdance_posts_list_inside_wrap_start
- breakdance_posts_list_after_title
- breakdance_posts_list_after_meta
- breakdance_posts_list_after_tax
- breakdance_posts_list_after_content
- breakdance_posts_list_inside_wrap_end
- breakdance_posts_list_after_post
- breakdance_posts_list_after_loop
- breakdance_posts_list_after_pagination

## Shape Dividers Hooks

### breakdance_shape_dividers

```php
add_filter('breakdance_shape_dividers', function ($dividers) {

    $myDivider = [
        'text' => 'My Divider',
        'value' => file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "my-divider.svg")
    ];

    $dividers[] = $myDivider;

    return $dividers;
});
```

## Form Builder Hooks

### breakdance_form_start

```php
add_action('breakdance_form_start', function ($settings) {
    echo "<div class=\"breakdance-form-group\">
        <h4 style='margin: 0;'>{$settings['form']['form_name']}</h4>
    </div>";
});
```

### breakdance_form_before_field

```php
add_action('breakdance_form_before_field', function ($field, $settings) {
    echo 'The field below is ' . ($field['advanced']['required'] ? '' : 'not') . ' required.';
}, 10, 2);
```

### breakdance_form_after_field

```php
add_action('breakdance_form_after_field', function ($field, $settings) {
    echo "<span>Type: <strong>{$field['type']}</strong> / ID: {$field['advanced']['id']}</span>";
}, 10, 2);
```

### breakdance_form_before_footer

```php
add_action('breakdance_form_before_footer', function ($settings) {
    echo '
    <div class="breakdance-form-group">
        <button>AutoFill with LinkedIn</button>
    </div>
    ';
});
```

### breakdance_form_end

```php
add_action('breakdance_form_end', function ($settings) {
    echo '<div class="breakdance-form-group">By submitting this form you agree to the terms of service.</div>';
});
```
