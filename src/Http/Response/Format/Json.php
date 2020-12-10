<?php

namespace Dingo\Api\Http\Response\Format;

use ErrorException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Contracts\Support\Arrayable;

class Json extends Format
{
    /*
     * JSON format (as well as JSONP) uses JsonOptionalFormatting trait, which
     * provides extra functionality for the process of encoding data to
     * its JSON representation.
     */
    use JsonOptionalFormatting;

    /**
     * Format an Eloquent model.
     *
     * @param Model $model
     *
     * @return string
     * @throws ErrorException
     */
    public function formatEloquentModel(Model $model) : string
    {
        $key = Str::singular($model->getTable());

        if (! $model::$snakeAttributes) {
            $key = Str::camel($key);
        }

        return $this->encode([$key => $model->toArray()]);
    }

    /**
     * Format an Eloquent collection.
     *
     * @param Collection $collection
     *
     * @return string
     * @throws ErrorException
     */
    public function formatEloquentCollection(Collection $collection) : string
    {
        if ($collection->isEmpty()) {
            return $this->encode([]);
        }

        $model = $collection->first();
        $key = Str::plural($model->getTable());

        if (! $model::$snakeAttributes) {
            $key = Str::camel($key);
        }

        return $this->encode([$key => $collection->toArray()]);
    }

    /**
     * Format an array or instance implementing Arrayable.
     *
     * @param array|Arrayable $content
     *
     * @return string
     * @throws ErrorException
     */
    public function formatArray($content) : string
    {
        $content = $this->morphToArray($content);

        array_walk_recursive($content, function (&$value) {
            $value = $this->morphToArray($value);
        });

        return $this->encode($content);
    }

    /**
     * Get the response content type.
     *
     * @return string
     */
    public function getContentType() : string
    {
        return 'application/json';
    }

    /**
     * Morph a value to an array.
     *
     * @param array|Arrayable $value
     *
     * @return array
     */
    protected function morphToArray($value) : array
    {
        return $value instanceof Arrayable ? $value->toArray() : $value;
    }

    /**
     * Encode the content to its JSON representation.
     *
     * @param mixed $content
     *
     * @return string
     * @throws ErrorException
     */
    protected function encode($content) : string
    {
        $jsonEncodeOptions = [];

        // Here is a place, where any available JSON encoding options, that
        // deal with users' requirements to JSON response formatting and
        // structure, can be conveniently applied to tweak the output.

        if ($this->isJsonPrettyPrintEnabled()) {
            $jsonEncodeOptions[] = JSON_PRETTY_PRINT;
            $jsonEncodeOptions[] = JSON_UNESCAPED_UNICODE;
        }

        $encodedString = $this->performJsonEncoding($content, $jsonEncodeOptions);

        if ($this->isCustomIndentStyleRequired()) {
            $encodedString = $this->indentPrettyPrintedJson(
                $encodedString,
                $this->options['indent_style']
            );
        }

        return $encodedString;
    }
}
