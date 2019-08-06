<?php

namespace Corcel\Http\Controllers;

use Corcel\Model\Page;
use Corcel\Model\Post;
use Corcel\Model\Option;
use Corcel\Model\Taxonomy;
use Corcel\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoutingController extends Controller
{
    /**
     * Finds the page if it's set
     * @param  \Illuminate\Http\Request $request
     * @param  string                   $slug The page slug
     * @return \Illuminate\Support\Facades\View
     */
    public function init(Request $request, $slug = null)
    {
        // Variables

        $controller = '';
        $data = [];

        // Check if we're on the homepage

        if ($request->is('/')) {

            // Get the homepage details

            $showOnFront = Option::get('show_on_front');

            if ($showOnFront == 'page') {
                $pageOnFront = Option::get('page_on_front');
                if (!empty($pageOnFront)) {
                    $frontPage = Page::find($pageOnFront);
                    if ($frontPage) {

                        // Variables

                        $template = (!empty($frontPage->meta->_wp_page_template) && $frontPage->meta->_wp_page_template !== 'default') ? $frontPage->meta->_wp_page_template : '';

                        // Return the controller depending on the template

                        if (!empty($template)) {
                            $templatePage = Str::camel(preg_replace('/[^a-zA-Z0-9]/', '-', basename($template, '.php')));
                            $controller = "\\App\\Http\\Controllers\\" . ucfirst($templatePage) . "Controller";
                        } else {
                            $controller = '\App\Http\Controllers\FrontPageController';
                        }

                        $data = $frontPage;

                    }
                }
            } else {
                $controller = '\App\Http\Controllers\IndexController';
            }

        }

        // Check if we're viewing a category

        $categoryBase = Option::get('category_base');

        if (empty($categoryBase)) {
            $categoryBase = 'category';
        }

        if ($request->is("$categoryBase/*")) {

            // Get all the categories from the path

            $categorySlugs = explode('/', $request->path());
            $mainCategorySlug = $categorySlugs[count($categorySlugs) - 1];

            // Find the category in the database

            $category = Taxonomy::category()->slug($mainCategorySlug)->first();

            // Check if we've found the category

            if ($category) {
                $controller = '\App\Http\Controllers\CategoryController';
                $data = $category;
            }

        }

        // Check if we're viewing a tag

        $tagBase = Option::get('tag_base');

        if (empty($tagBase)) {
            $tagBase = 'tag';
        }

        if ($request->is("$tagBase/*")) {

            // Get all the tags from the path

            $tagSlugs = explode('/', $request->path());
            $mainTagSlug = $tagSlugs[count($tagSlugs) - 1];

            // Find the tag in the database

            $tag = Taxonomy::where('taxonomy', 'post_tag')->slug($mainTagSlug)->first();

            // Check if we've found the tag

            if ($tag) {
                $controller = '\App\Http\Controllers\TagController';
                $data = $tag;
            }

        }

        // Check if we're viewing another type of page

        if (!$request->is('/')) {

            // Variables

            $permalinkStructure = Option::get('permalink_structure');
            $permalinkStructure = preg_replace(['/^\//', '/\/$/'], '', $permalinkStructure);
            $permalinkStructureGlob = preg_replace('/%[^%]+%/', '*', $permalinkStructure);
            $permalinkStructureGlob = preg_replace(['/^\//', '/\/$/'], '', $permalinkStructureGlob);

            // Check if the permalink structure has been set and if we're viewing a post

            if (
                !empty($permalinkStructure) &&
                $request->is($permalinkStructureGlob) &&
                preg_match('/%(post_id|postname)%/', $permalinkStructure)
            ) {

                // Variables

                $post = null;
                $params = [];

                // Get the variables

                preg_match_all('/%[^%]+%/', $permalinkStructure, $matches);
                preg_match_all('/' . preg_replace('/%[^%]+%/', '([^\/]+)' , str_replace('/', '\/', $permalinkStructure)) . '/', $request->path(), $matchVars);

                if (isset($matches[0]) && count($matches[0])) {
                    foreach ($matches[0] as $matchKey => $match) {
                        $params[$match] = (isset($matchVars[$matchKey + 1]) && isset($matchVars[$matchKey + 1][0])) ? $matchVars[$matchKey + 1][0] : '';
                    }
                }

                // Check if the post name or post id is set and check if it exists

                if (isset($params['%post_id%'])) {
                    $post = Post::find($params['%post_id%'])->published()->where([
                        'post_type' => 'post'
                    ])->first();
                } elseif ($params['%postname%']) {
                    $post = Post::slug($params['%postname%'])->published()->where([
                        'post_type' => 'post'
                    ])->first();
                }

                if ($post) {
                    $controller = '\App\Http\Controllers\PostController';
                    $data = $post;
                }

            }

            // Check if the permalink structure has been set and if we're viewing another type of page

            if (!empty($permalinkStructure)) {

                // Variables

                $page = null;
                $slugs = explode('/', $request->path());

                // Check if the page exists

                if (count($slugs)) {
                    $page = Post::published()->where([
                        'post_name' => $slugs[count($slugs) - 1]
                    ])->first();
                    if ($page && class_exists('\App\Models\\' . ucfirst($page->post_type))) {
                        $model = '\App\Models\\' . ucfirst($page->post_type);
                        $page = $model::find($page->ID);
                    }
                }

                // Check if the page exists

                if ($page) {

                    // Check if it's a custom post type

                    if ($page->post_type == 'page') {

                        // Variables

                        $template = (!empty($page->meta->_wp_page_template) && $page->meta->_wp_page_template !== 'default') ? $page->meta->_wp_page_template : '';

                        // Return the controller depending on the template

                        if (!empty($template)) {
                            $templatePage = Str::camel(preg_replace('/[^a-zA-Z0-9]/', '-', basename($template, '.php')));
                            $controllerName = "\\App\\Http\\Controllers\\" . ucfirst($templatePage) . "Controller";
                        } else {
                            $controllerName = '\App\Http\Controllers\PageController';
                        }

                        $controller = $controllerName;
                        $data = $page;

                    } elseif ($page->post_type == 'post') {
                        $controller = '\App\Http\Controllers\PostController';
                        $data = $page;
                    } else {
                        $customPostType = ucfirst($page->post_type);
                        $controller = "\\App\\Http\\Controllers\\{$customPostType}Controller";
                        $data = $page;
                    }

                }

                // Check if we're viewing a custom taxonomy page

                $taxonomies = Taxonomy::all()->groupBy('taxonomy')->toArray();

                if (count($taxonomies)) {
                    unset($taxonomies['category']);
                    unset($taxonomies['post_tag']);
                    unset($taxonomies['nav_menu']);
                    $taxonomies = array_keys($taxonomies);
                    foreach ($taxonomies as $taxonomy) {
                        if ($request->is("{$taxonomy}/*")) {
                            $slugs = explode('/', $request->path());
                            $tax = Taxonomy::slug($slugs[count($slugs) - 1])->first();
                            $controllerName = ucfirst(Str::camel(preg_replace('/[^a-zA-Z0-9]/', '-', $taxonomy)));
                            if ($tax) {
                                $controller = "\\App\\Http\\Controllers\\{$controllerName}Controller";
                                $data = $tax;
                            }
                        }
                    }
                }

            }

        }

        // Return the controller or output a 404 error

        if (!empty($controller) && class_exists($controller)) {
            $controller = new $controller();
            return $controller->index($request, $data);
        }

        abort(404);
    }
}
