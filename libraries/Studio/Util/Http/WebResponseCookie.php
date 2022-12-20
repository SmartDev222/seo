<?php

namespace Studio\Util\Http;

class WebResponseCookie {

    protected $definition;
    protected $name;
    protected $value;
    protected $settings = [];

    public function __construct($definition) {
        parse_str(strtr($definition, ['&' => '%26', '+' => '%2B', ';' => '&']), $cookie);

        foreach ($cookie as $key => $value) {
            $this->definition = $definition;
            $this->name = $key;
            $this->value = urldecode($value);
            break;
        }

        foreach ($cookie as $key => $value) {
            if ($key !== $this->name) {
                $this->settings[strtolower($key)] = $value;
            }
        }
    }

    /**
     * Returns the maximum age of the cookie in seconds. If no maximum age is set, returns `null`. A zero or negative
     * number should expire the cookie immediately. Note that if both `Expires` and `Max-Age` are set, `Max-Age` will
     * have precedence.
     *
     * @return int|null
     */
    public function getMaxAge() {
        $maxAge = $this->get('Max-Age');

        if (is_null($maxAge)) return;
        if (!preg_match('/^([0-9-])([0-9]*)$/', $maxAge)) return null;
        if ($maxAge === '-') return null;

        return (int)$maxAge;
    }

    /**
     * Returns the datetime at which the cookie expires as a string, or `null` if the cookie did not specify one. This
     * will be a formatted date stamp parseable with `strtotime`. If an expiration date is unavailable, the cookie
     * should have the lifetime of a session cookie.
     *
     * @property bool $considerMaxAge Allows the `Max-Age` property to override the `Expires` property.
     * @return string|null
     */
    public function getExpirationDate($considerMaxAge = true) {
        $expires = $this->get('Expires');
        $maxAge = $this->getMaxAge();

        if ($considerMaxAge && !is_null($maxAge)) return gmdate('D, d M Y H:i:s T', time() + $maxAge);
        if (is_null($expires)) return;

        return $expires;
    }

    /**
     * Returns a timestamp representing the time at which this cookie expires, or `null` if it is a session cookie. If
     * the returned timestamp is equal to or less than the current time, the cookie must expire immediately.
     *
     * @property bool $considerMaxAge Allows the `Max-Age` property to override the `Expires` property.
     * @return int|null
     */
    public function getExpirationTime($considerMaxAge = true) {
        $expires = $this->get('Expires');
        $maxAge = $this->getMaxAge();

        if ($considerMaxAge && !is_null($maxAge)) return time() + $maxAge;
        if (is_null($expires)) return;

        return (($timestamp = @strtotime($expires)) !== false) ? $timestamp : null;
    }

    /**
     * Returns the domain this cookie wants to be sent to. Leading dots are ignored, and specifying a domain will
     * always include all subdomains. If no domain is set, returns `null`. In this case, the default behavior should be
     * to restrict the cookie to the host portion of the current document location (but not including subdomains).
     *
     * @return string|null
     */
    public function getDomain() {
        $domain = $this->get('Domain');
        if (is_null($domain)) return;
        return trim($domain, '.');
    }

    /**
     * Returns the URL path that must exist for the cookie to be sent. All subdirectories of the path will always be
     * matched. Defaults to `/` if not set.
     *
     * @return string|null
     */
    public function getPath() {
        $path = $this->get('Path');

        if (is_null($path)) return '/';
        return '/' . ltrim($path, '/');
    }

    /**
     * Returns the value of the specified setting key or `null` if it was not available.
     *
     * @param string $key
     * @return string|null
     */
    protected function get($key) {
        $key = strtolower($key);

        if (isset($this->settings[$key])) {
            return $this->settings[$key];
        }
    }

    /**
     * Returns the original cookie definition.
     *
     * @return string
     */
    public function getDefinition() {
        return $this->definition;
    }

    /**
     * Returns the cookie's name.
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Returns the cookie's value.
     *
     * @return string
     */
    public function getValue() {
        return $this->value;
    }

}
