<?php

namespace Corcel\Model;

use Corcel\Helpers\WordPress as WP;
use Corcel\Model\Term;
use Corcel\Model\Option;
use Illuminate\Support\Arr;

/**
 * Class Menu
 *
 * @package Corcel\Model
 * @author Yoram de Langen <yoramdelangen@gmail.com>
 * @author Junior Grossi <juniorgro@gmail.com>
 */
class Menu extends Taxonomy
{
    /**
     * @var string
     */
    protected $taxonomy = 'nav_menu';

    /**
     * @var array
     */
    protected $with = ['term', 'items'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function items()
    {
        return $this->belongsToMany(
            MenuItem::class, 'term_relationships', 'term_taxonomy_id', 'object_id'
        )->orderBy('menu_order');
    }

    /**
     * Gets the menu based on the slug
     * @param  string  $slug  The menu slug
     * @return array          The menu
     */
    public static function get($slug)
    {
        // Variables

        $menu = [];
        $template = Option::get('stylesheet');
        $themeMods = Option::get('theme_mods_' . $template);

        // Check if data is set then parse JSON

        if (!empty($themeMods) && isset($themeMods['nav_menu_locations'][$slug])) {

            // Get the menu term

            $menuId = $themeMods['nav_menu_locations'][$slug];
            $menuTerm = Term::find($menuId)->toArray();

            if ($menuTerm) {

                // Get menu using menu model

                $menuData = Menu::slug($menuTerm['slug'])->first();

                if ($menuData) {

                    // Go through the data and create a simple array structure

                    $menuStructure = [];

                    foreach ($menuData->items as $item) {

                        // Variables

                        $instance = $item->instance();
                        $menuItemData = $item->toArray();
                        $menuItemData['meta'] = Arr::pluck($menuItemData['meta'], 'value', 'meta_key');

                        // Get the title

                        $title = $item->title;

                        if (empty($title)) {
                            $title = $instance->title;
                        }

                        if (empty($title)) {
                            $title = $instance->name;
                        }

                        if (empty($title)) {
                            $title = $instance->link_text;
                        }

                        // Get the link

                        $link = '';

                        if (in_array($menuItemData['meta']['_menu_item_object'], ['post', 'page'])) {
                            $link = WP::getPermalink($instance->ID);
                        } elseif ($menuItemData['meta']['_menu_item_object'] == 'custom') {
                            $link = $menuItemData['meta']['_menu_item_url'];
                        } else {
                            $link = WP::getTermLink($menuItemData['meta']['_menu_item_object_id']);
                        }

                        // Get the parent

                        $parentId = 0;

                        if ($parent = $item->parent()) {
                            $parentId = $parent->ID;
                        }

                        // Setup menu item

                        $menuStructure[$item->ID] = [
                            'id' => $item->ID,
                            'title' => $title,
                            'link' => $link,
                            'target' => $menuItemData['meta']['_menu_item_target'],
                            'rel' => $menuItemData['meta']['_menu_item_xfn'],
                            'classes' => $menuItemData['meta']['_menu_item_classes'],
                            'parent' => $parentId
                        ];

                    }

                    // Let's order the menu based on hierarchy

                    if (count($menuStructure)) {
                        $menu = self::sortHierarchicaly($menuStructure);
                    }

                }

            }

        }

        return $menu;
    }

    /**
     * Sort items out into a hierarchy
     * @param  array   $items    The menu items
     * @param  integer $parentId The parent ID
     * @return array             The sorted items
     */
    public static function sortHierarchicaly($items, $parentId = 0)
    {
        $into = [];
        foreach ($items as $i => $item) {
            if ($item['parent'] == $parentId) {
                $item['children'] = self::sortHierarchicaly($items, $item['id']);
                $into[$item['id']] = $item;
            }
        }
        return $into;
    }
}
