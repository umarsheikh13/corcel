<?php

namespace Corcel\Model;

use Corcel\Model;
use Exception;

/**
 * Option class.
 *
 * @package Corcel\Model
 * @author José CI <josec89@gmail.com>
 * @author Junior Grossi <juniorgro@gmail.com>
 */
class Option extends Model
{
    /**
     * @var string
     */
    protected $table = 'options';

    /**
     * @var string
     */
    protected $primaryKey = 'option_id';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = [
        'option_name',
        'option_value',
        'autoload',
    ];

    /**
     * @var array
     */
    protected $appends = ['value'];

    /**
     * @return mixed
     */
    public function getValueAttribute()
    {
        try {
            $value = unserialize($this->option_value);

            return $value === false && $this->option_value !== false ?
                $this->option_value :
                $value;
        } catch (Exception $ex) {
            return $this->option_value;
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return Option
     */
    public static function add($key, $value)
    {
        return static::create([
            'option_name' => $key,
            'option_value' => is_array($value) ? serialize($value) : $value,
        ]);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param string $namespace
     * @return Option
     */
    public static function set($name, $value, $namespace = '')
    {
        if (!empty($namespace)) {
            $options = self::get($name, $namespace);
            $options = ($options) ? $options : [];
            $options[$name] = $value;
            self::add($namespace, $options);
        } else {
            return self::add($name, $value);
        }
    }

    /**
     * @param string $name
     * @param string $namespace
     * @return mixed
     */
    public static function get($name, $namespace = '')
    {
        if (!empty($namespace) && $option = self::where('option_name', $namespace)->first()) {
            $options = $option->value;
            if ($options && $name == 'all') {
                return $options;
            } elseif ($options && isset($options[$name])) {
                return $options[$name];
            }
        } elseif ($option = self::where('option_name', $name)->first()) {
            return $option->value;
        }

        return null;
    }

    /**
     * @return array
     * @deprecated
     */
    public static function getAll()
    {
        return static::asArray();
    }

    /**
     * @param array $keys
     * @return array
     */
    public static function asArray($keys = [])
    {
        $query = static::query();

        if (!empty($keys)) {
            $query->whereIn('option_name', $keys);
        }

        return $query->get()
            ->pluck('value', 'option_name')
            ->toArray();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        if ($this instanceof Option) {
            return [$this->option_name => $this->value];
        }

        return parent::toArray();
    }
}
