<?php

namespace Corcel\Model\Collection;

use Illuminate\Database\Eloquent\Collection;

/**
 * Class MetaCollection
 *
 * @package Corcel\Model\Collection
 * @author Junior Grossi <juniorgro@gmail.com>
 */
class MetaCollection extends Collection
{
    /**
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        if (isset($this->items) && count($this->items)) {
            $meta = $this->first(function ($meta) use ($key) {
                return $meta->meta_key === $key;
            });

            if ($meta) {
                try {
                    $value = unserialize($this->meta_value);

                    return $value === false && $this->meta_value !== false ?
                        $this->meta_value :
                        $value;
                } catch (Exception $ex) {
                    return $this->meta_value;
                }
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return !is_null($this->__get($name));
    }
}
