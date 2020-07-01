<?php

namespace Corcel\Helpers;

use Corcel\Model\Post;
use Corcel\Model\User;
use Corcel\Model\Term;
use Corcel\Model\Option;
use Corcel\Model\Attachment;

class WordPress
{
    /**
     * Run a WordPress function using the cmd.php file
     * @param  string   $name The function name
     * @param  array    $args The arguments
     * @return mixed    The output
     */
    public static function fn($name, $args = [])
    {
        $output = false;
        $themeLocation = env('CORCEL_WP_THEME_LOCATION');
        if (
            !empty($themeLocation) &&
            file_exists(public_path($themeLocation) . '/cmd.php')
        ) {
            $run = exec('php ' . public_path($themeLocation) . '/cmd.php ' . $name . ((count($args)) ? ' "' . json_encode($args) . '"' : ''));
            try {
                $json = json_decode($run, true);
                $output = $json['output'];
            } catch (\Exception $e) {
                $output = false;
            }
        }
        return $output;
    }

    /**
     * Gets the post/page permalink
     * @param  integer $id The post/page ID
     * @return string      The permalink
     */
    public static function getPermalink($id)
    {
        // Variables

        $permalink = '';
        $permalinkStructure = Option::get('permalink_structure');

        // Check if the post/page exists

        if ($post = Post::find($id)) {

            // Check if it's a page or post

            if ($post->post_type == 'page') {
                $pageOnFront = Option::get('page_on_front');
                if (!empty($pageOnFront) && $pageOnFront == $post->ID) {
                    $permalink = '/';
                } else {
                    $permalink = '/' . $post->post_name;
                }
            } elseif (!in_array($post->post_type, ['post', 'page'])) {
                $pageOnFront = Option::get('page_on_front');
                if (!empty($pageOnFront) && $pageOnFront == $post->ID) {
                    $permalink = '/';
                } else {
                    $permalink = '/' . $post->post_type . '/' . $post->post_name;
                }
            } elseif (!empty($permalinkStructure)) {
                $postTime = strtotime($post->post_date);
                $categories = $post->taxonomies()->get()->toArray();
                $category = $categories[0]['term']['slug'];
                $user = User::find(1)->toArray();
                $username = $user['user_login'];
                $author = 1;
                $permalinkStructure = str_replace('%year%', date('Y', $postTime), $permalinkStructure);
                $permalinkStructure = str_replace('%monthnum%', date('m', $postTime), $permalinkStructure);
                $permalinkStructure = str_replace('%day%', date('d', $postTime), $permalinkStructure);
                $permalinkStructure = str_replace('%hour%', date('H', $postTime), $permalinkStructure);
                $permalinkStructure = str_replace('%minute%', date('i', $postTime), $permalinkStructure);
                $permalinkStructure = str_replace('%second%', date('s', $postTime), $permalinkStructure);
                $permalinkStructure = str_replace('%post_id%', $post->ID, $permalinkStructure);
                $permalinkStructure = str_replace('%postname%', $post->post_name, $permalinkStructure);
                $permalinkStructure = str_replace('%author%', $username, $permalinkStructure);
                $permalinkStructure = str_replace('%category%', $category, $permalinkStructure);
                $permalink = $permalinkStructure;
            }

        }

        // Add trailing slash

        if (!empty($permalink)) {
            $permalink = url($permalink);
        }

        return $permalink;
    }

    /**
     * Gets the term permalink
     * @param  integer $id The term ID
     * @return string      The permalink
     */
    public static function getTermLink($id)
    {
        // Variables

        $link = '';

        // Get the term

        $term = Term::where('term_id', $id)->with('taxonomy')->first();

        if ($term) {
            $data = $term->toArray();
            $link = "/{$data['taxonomy']['taxonomy']}/{$data['slug']}";
        }

        return url($link);
    }

    /**
     * Gets the image attachment urls
     * @param  integer $id The attachment ID
     * @return array       The image urls
     */
    public static function getImageAttachmentUrls($id)
    {
        $urls = [];
        $appUrl = env('APP_URL');
        $wpPath = $appUrl . '/' . env('CORCEL_WP_CORE_LOCATION', '') . '/app/uploads/';
        if ($attachment = Attachment::find($id)) {
            if (
                isset($attachment->meta->_wp_attachment_metadata) &&
                !empty($attachment->meta->_wp_attachment_metadata)
            ) {
                $metaData = $attachment->meta->_wp_attachment_metadata;
                $file = $metaData['file'];
                $filename = basename($file);
                $filepath = str_replace($filename, '', $file);
                foreach ($metaData['sizes'] as $size => $data) {
                    $urls[$size] = $wpPath . $filepath . $data['file'];
                }
            }
        }
        return $urls;
    }
}
