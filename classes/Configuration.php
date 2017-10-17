<?php namespace Phrodo\Application;

/**
 * Configuration class
 */
class Configuration
{
    /**
     * Configuration data
     *
     * @var array
     */
    protected $data;

    /**
     * Configuration constructor
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Has configuration value
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Get configuration value
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed|null
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Set configuration value
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set(string $key, $value)
    {
        $this->data[$key] = $value;
    }
}
