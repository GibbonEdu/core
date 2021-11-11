<?php

namespace Gibbon\Http;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

/**
 * An implementation of PSR-7 URI for Gibbon URLs.
 *
 * For forward-compatibility, the path information specific to Gibbon (e.g. script name,
 * parameter "q") are stored internally with $routeHandler, $module and $routePath. Once
 * an instance is created by Url::fromRoute, Url::fromModuleRoute, Url::fromHandlerRoute,
 * or fromHandlerModuleRoute, these internal variables are not changable.
 *
 * Renders as normal URL string if cast as string type. (Implemented magic method
 * `__toString()`)
 *
 * The internal variables will be used to generate the supposed URL. Other method calls
 * (e.g. Url::withQueryParam or Url::withQueryParams) do not overwrite the effect of the
 * internal variables set. So a Url initiated from Url::fromModuleRoute will still have a
 * "q" parameter even if withQueryParam('q', '') or withQueryParams([]) is called.
 *
 * Example:
 * ```php
 * $url = Url::fromModuleRoute('User Admin', 'user_manage_edit')->withQueryParams([
 *          'gibbonPersonID' => 1,
 *          'search' => 'foobar',
 * ]);
 * echo "<a href='{$url}'>Edit User</a>";
 * ```
 *
 * Will generate a link to "/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=1&search=foobar".
 *
 * @author Koala Yeung
 */
class Url extends Uri implements UriInterface
{

    /**
     * The base absolute URL to use for all rendered URLs.
     *
     * @var string
     */
    protected static $baseUrl;

    /**
     * The path portion of the baseUrl.
     *
     * @var string
     */
    protected static $basePath;

    /**
     * The handler of the route.
     *
     * @var string
     */
    protected $routeHandler = 'index.php';

    /**
     * The name of the module.
     *
     * @var string
     */
    protected $module;

    /**
     * Whether to apply an absolute URL to this route.
     *
     * @var bool
     */
    protected $isAbsolute = false;

    /**
     * Gibbon's internal route path. Would be non-null if created from methods
     * like fromRoute, fromModuleRoute. Affects how __toString works.
     *
     * @var string|null
     */
    protected $routePath = null;

    /**
     * Create Uri instance for the root-relative url of the given Gibbon routes.
     *
     * @param string $route_path
     *   The core route path (e.g. "preferences", "privacyPolicy"). If left empty,
     *   will use the base path (Home).
     *
     * @return static
     *   The URL object.
     */
    public static function fromRoute(string $route_path = ''): self
    {
        return (new static())
            ->withPath(static::$basePath)
            ->withRoutePath($route_path);
    }

    /**
     * Create Uri instance for the root-relative url of the given Gibbon module
     * routes.
     *
     * @param string $module
     *   The name of the module (e.g. "Reports").
     * @param string $route_path
     *   The module specific route path (e.g. "reporting_cycles_manage_add"). If left
     *   empty, the default will be empty string (module home page).
     *
     * @return static
     *   The URL object.
     */
    public static function fromModuleRoute(string $module, string $route_path = ''): self
    {
        return (new static())
            ->withPath(static::$basePath)
            ->withModule($module)
            ->withRoutePath($route_path);
    }

    /**
     * Create an URL instance of the given Gibbon handler script.
     *
     * @param string $handler
     *   Can be either "fullscreen.php", "index_tt_ajax.php"
     *   or other scripts at Gibbon's root folder.
     * @param string $route_path
     *   (Optional) The route path "q".
     *
     * @return self
     */
    public static function fromHandlerRoute(string $handler, string $route_path = ''): self
    {
        $new = (new static())
            ->withPath(static::$basePath)
            ->withRoutePath($route_path);
        $new->routeHandler = $handler;
        return $new;
    }

    /**
     * Create an URL instance of the given Gibbon handler script with module route path.
     *
     * @param string $handler
     *   Can be either "fullscreen.php", "index_tt_ajax.php"
     *   or other scripts at Gibbon's root folder.
     * @param string $module
     *   The name of the module (e.g. "Reports").
     * @param string $route_path
     *   The module specific route path (e.g. "reporting_cycles_manage_add").
     *
     * @return self
     */
    public static function fromHandlerModuleRoute(
        string $handler,
        string $module,
        string $route_path
    ): self
    {
        $new = (new static())
            ->withPath(static::$basePath)
            ->withModule($module)
            ->withRoutePath($route_path);
        $new->routeHandler = $handler;
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        // Apply an absolute URL to this route before rendering.
        if ($this->isAbsolute) {
            $parsed = parse_url(self::$baseUrl);

            $new = $this
                ->withScheme($parsed['scheme'] ?? '')
                ->withHost($parsed['host'] ?? '')
                ->withPort($parsed['port'] ?? null);
            $new->isAbsolute = false;
            return $new->__toString();
        }

        // Only override rendering if a route path is set.
        // Supposed to only happen if created by the
        // fromRoute() or fromModuleRoute() methods.
        if (isset($this->routePath)) {
            $query = $this->getQueryParams();
            $handler_path = $this->getPath() . '/' . $this->routeHandler;
            $route_target = !empty($this->routePath) ? $this->routePath . '.php' : '';
            $route_target = !empty($this->module)
                ? '/modules/' . $this->module . '/' . $route_target
                : $route_target;

            if (!empty($route_target)) {
                // overwrite "q" in query with module / core route path
                $query = ['q' => $route_target] + $query;
            }

            $new = $this
                ->withPath($handler_path)
                ->withQueryParams($query);
            $new->routePath = null; // reset routePath to prevent infinite recursion
            return $new->__toString();
        }
        return parent::__toString();
    }

    /**
     * Retrieve query string arguments.
     *
     * Retrieves the deserialized query string arguments, if any.
     *
     * @return array An assoc-array of query param key-values.
     */
    public function getQueryParams(): array
    {
        parse_str($this->getQuery(), $params);
        return $params;
    }

    /**
     * Return an instance with the specified query string arguments.
     *
     * The method retains immutability of the original Url instances.
     * Changes are only made to the returned instance.
     *
     * @param array $params
     *
     * @return static
     */
    public function withQueryParams(array $params): self
    {
        return $this->withQuery(http_build_query($params));
    }

    /**
     * Return an instance with one query parameter changed. If no
     * value or null is provided for the value, the query string
     * will have only the key without value.
     *
     * @param array $params
     *
     * @return static
     */
    public function withQueryParam(string $key, $value=null): self
    {
        return Uri::withQueryValue($this, $key, $value);
    }

    /**
     * Create Uri with "return" query paremeter.
     *
     * Create Uri instance for the root-relative url with the specific return
     * message (success, warning or error). Simply a short-hand for
     * `Url::withQueryParam('return', $return_type)`.
     *
     * @param string $return_type The internal reference string for the return.
     *
     * @return static
     */
    public function withReturn(string $return_type): self
    {
        return $this->withQueryParam('return', $return_type);
    }

    /**
     * Ensure the rendered url contains the scheme, host and (optional) port.
     *
     * @param bool $isAbsolute
     * @return self
     */
    public function withAbsoluteUrl(bool $isAbsolute = true): self
    {
        if ($this->isAbsolute === $isAbsolute) {
            return $this;
        }
        $new = clone $this;
        $new->isAbsolute = $isAbsolute;
        return $new;
    }

    /**
     * Setup the class to use the baseUrl and basePath for all
     * rendered URL. Should be the "absoluteURL" in session variables.
     *
     * Will be used by the fromRoute and fromModuleRoute methods.
     *
     * Note: The setting is binded to the class in the
     * environment, so you should only do this once per request.
     *
     * @param string $baseUrl
     *
     * @return void
     */
    public static function setBaseUrl(string $baseUrl)
    {
        // make sure the base url do not have trailing slash
        self::$baseUrl = rtrim($baseUrl, '/');
        $parsed = parse_url(self::$baseUrl);
        self::$basePath = $parsed['path'] ?? '';
    }

    /**
     * Setting the internal module property. Should only be
     * used internally by fromRoute and fromModuleRoute
     * method (or other similar public method) to keep the
     * class useful for non-Gibbon URLs.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified module.
     *
     * @param string $module
     *
     * @return static
     *   A new instance with the specified module.
     */
    private function withModule(string $module): self
    {
        if ($this->module === $module) {
            return $this;
        }
        $new = clone $this;
        $new->module = $module;
        return $new;
    }

    /**
     * Setting the internal routePath property. Should only be
     * used internally by fromRoute and fromModuleRoute
     * method (or other similar public method) to keep the
     * class useful for non-Gibbon URLs.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified module.
     *
     * @param string $module
     *
     * @return static
     *   A new instance with the specified route path.
     */
    private function withRoutePath(string $route_path): self
    {
        $route_path = preg_replace('/\.php$/i', '', $route_path);

        if ($this->routePath === $route_path) {
            return $this;
        }
        $new = clone $this;
        $new->routePath = $route_path;
        return $new;
    }
}
