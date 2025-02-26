<?php
/**
 * Media handling functionality for Mesmeric Commerce
 *
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/includes
 */

declare(strict_types=1);

namespace MesmericCommerce\Includes;

use WP_Error;
use WP_Post;

/**
 * Class MC_Media
 *
 * Handles media operations like file uploads, attachments, and media library integration
 * for Mesmeric Commerce plugin.
 *
 * @since      1.0.0
 * @package    Mesmeric_Commerce
 * @subpackage Mesmeric_Commerce/includes
 */
class MC_Media
{

    /**
     * Initialize the class
     */
    public function __construct()
    {
        add_filter('upload_mimes', [$this, 'allowed_mime_types']);
        add_filter('wp_check_filetype_and_ext', [$this, 'check_filetype'], 10, 5);
    }

    /**
     * Upload a file to the WordPress media library
     *
     * @param array  $file     $_FILES array element
     * @param string $title    Optional. Title for the media item
     * @param string $caption  Optional. Caption for the media item
     * @param int    $parent   Optional. Parent post ID
     * @return int|WP_Error Media item ID or WP_Error on failure
     */
    public function upload_file(array $file, string $title = '', string $caption = '', int $parent = 0): int|WP_Error
    {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        }
        if (!function_exists('media_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/media.php');
        }

        // Setup upload overrides
        $upload_overrides = [
            'test_form' => false,
            'test_size' => true,
        ];

        // Handle the upload
        $uploaded_file = wp_handle_upload($file, $upload_overrides);

        if (isset($uploaded_file['error'])) {
            return new WP_Error('upload_error', $uploaded_file['error']);
        }

        // Prepare attachment data
        $attachment = [
            'post_mime_type' => $uploaded_file['type'],
            'post_title' => !empty($title) ? $title : preg_replace('/\.[^.]+$/', '', basename($file['name'])),
            'post_content' => $caption,
            'post_status' => 'inherit',
        ];

        // Insert attachment into the WordPress Media Library
        $attachment_id = wp_insert_attachment($attachment, $uploaded_file['file'], $parent);

        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        // Generate metadata for the attachment
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $uploaded_file['file']);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        return $attachment_id;
    }

    /**
     * Get media item details
     *
     * @param int $attachment_id Attachment ID
     * @return array|false Media details or false on failure
     */
    public function get_media_details(int $attachment_id): array|false
    {
        $attachment = get_post($attachment_id);

        if (!$attachment instanceof WP_Post) {
            return false;
        }

        return [
            'id' => $attachment->ID,
            'title' => $attachment->post_title,
            'caption' => $attachment->post_excerpt,
            'description' => $attachment->post_content,
            'alt' => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true),
            'url' => wp_get_attachment_url($attachment->ID),
            'type' => $attachment->post_mime_type,
            'metadata' => wp_get_attachment_metadata($attachment->ID),
            'sizes' => $this->get_image_sizes($attachment->ID),
        ];
    }

    /**
     * Get available image sizes for an attachment
     *
     * @param int $attachment_id Attachment ID
     * @return array<string, array{url: string, width: int, height: int}> Image sizes with their URLs
     */
    protected function get_image_sizes(int $attachment_id): array
    {
        $sizes = [];
        $available_sizes = get_intermediate_image_sizes();

        foreach ($available_sizes as $size) {
            $image = wp_get_attachment_image_src($attachment_id, $size);
            if ($image) {
                $sizes[$size] = [
                    'url' => $image[0],
                    'width' => $image[1],
                    'height' => $image[2],
                ];
            }
        }

        return $sizes;
    }

    /**
     * Filter allowed mime types
     *
     * @param array $mimes Mime types keyed by their file extension
     * @return array Modified mime types
     */
    public function allowed_mime_types(array $mimes): array
    {
        // Add or remove mime types as needed
        return $mimes;
    }

    /**
     * Additional filetype and extension validation
     *
     * @param array  $data     Values for the extension, mime type, and corrected filename
     * @param string $file     Full path to the file
     * @param string $filename The name of the file
     * @param array  $mimes    Array of mime types keyed by their file extension regex
     * @param string $real_mime Real mime type of the uploaded file
     * @return array Modified data
     */
    public function check_filetype(array $data, string $file, string $filename, array $mimes, string $real_mime): array
    {
        // Perform additional filetype validation if needed
        return $data;
    }

    /**
     * Delete a media item
     *
     * @param int  $attachment_id Attachment ID
     * @param bool $force_delete  Whether to bypass trash and force deletion
     * @return bool|WP_Error True on success, false or WP_Error on failure
     */
    public function delete_media(int $attachment_id, bool $force_delete = false): bool|WP_Error
    {
        if (!current_user_can('delete_post', $attachment_id)) {
            return new WP_Error('permission_denied', __('You do not have permission to delete this media item.', 'mesmeric-commerce'));
        }

        return wp_delete_attachment($attachment_id, $force_delete);
    }

    /**
     * Update media item details
     *
     * @param int   $attachment_id Attachment ID
     * @param array $data         Array of data to update
     * @return int|WP_Error The attachment ID if successful, WP_Error object otherwise
     */
    public function update_media(int $attachment_id, array $data): int|WP_Error
    {
        if (!current_user_can('edit_post', $attachment_id)) {
            return new WP_Error('permission_denied', __('You do not have permission to edit this media item.', 'mesmeric-commerce'));
        }

        $data['ID'] = $attachment_id;
        return wp_update_post($data, true);
    }
}
