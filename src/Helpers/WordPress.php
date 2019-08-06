<?php

namespace Corcel\Helpers;

use Corcel\Model\Post;
use Corcel\Model\User;
use Corcel\Model\Term;
use Corcel\Model\Option;

class WordPress
{
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
}
