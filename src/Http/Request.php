<?php

namespace Dingo\Api\Http;

use Dingo\Api\Http\Parser\Accept;
use Illuminate\Http\Request as IlluminateRequest;
use Dingo\Api\Contract\Http\Request as RequestInterface;

class Request extends IlluminateRequest implements RequestInterface
{
    /**
     * Accept parser instance.
     *
     * @var Accept
     */
    protected static $acceptParser;

    /**
     * Parsed accept header for the request.
     *
     * @var array
     */
    protected $accept;

    /**
     * Create a new Dingo request instance from an Illuminate request instance.
     *
     * @param IlluminateRequest $old
     *
     * @return Request
     */
    public static function createFromIlluminate(IlluminateRequest $old) : Request
    {
        $new = new static(
            $old->query->all(), $old->request->all(), $old->attributes->all(),
            $old->cookies->all(), $old->files->all(), $old->server->all(), $old->content
        );

        if ($session = $old->getSession()) {
            $new->setLaravelSession($old->getSession());
        }

        $new->setRouteResolver($old->getRouteResolver());
        $new->setUserResolver($old->getUserResolver());

        return $new;
    }

    /**
     * Get the defined version.
     *
     * @return string
     */
    public function version() : string
    {
        $this->parseAcceptHeader();

        return $this->accept['version'];
    }

    /**
     * Get the defined subtype.
     *
     * @return string
     */
    public function subtype() : string
    {
        $this->parseAcceptHeader();

        return $this->accept['subtype'];
    }

    /**
     * Get the expected format type.
     *
     * @param string $default
     * @return string
     */
    public function format($default = 'html') : string
    {
        $this->parseAcceptHeader();

        return $this->accept['format'] ?: parent::format($default);
    }

    /**
     * Parse the accept header.
     *
     * @return void
     */
    protected function parseAcceptHeader() : void
    {
        if ($this->accept) {
            return;
        }

        $this->accept = static::$acceptParser->parse($this);
    }

    /**
     * Set the accept parser instance.
     *
     * @param Accept $acceptParser
     *
     * @return void
     */
    public static function setAcceptParser(Accept $acceptParser) : void
    {
        static::$acceptParser = $acceptParser;
    }

    /**
     * Get the accept parser instance.
     *
     * @return Accept
     */
    public static function getAcceptParser() : Accept
    {
        return static::$acceptParser;
    }
}
