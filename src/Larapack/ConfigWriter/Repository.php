<?php

namespace Larapack\ConfigWriter;

use Illuminate\Config\Repository as BaseRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;

class Repository extends BaseRepository
{
    protected $name;
    protected $disk;

    public function __construct($name)
    {
        $this->name = $name;

        parent::__construct(Config::get($name, []));
    }

    public function save($from = null, $to = null, $validate = true)
    {
        if ($from === null) {
            $from = $this->getFile();
        }
        if ($to === null) {
            $to = $this->getFile();
        }

        $content = $this->prepareContent($from, $validate);

        $this->ensurePathsExists($to);

        $this->disk()->put(
            $to,
            $content
        );
    }

    protected function disk()
    {
        if (!$this->disk) {
            $this->disk = new Filesystem();
        }

        return $this->disk;
    }

    protected function ensurePathsExists($location)
    {
        $parts = explode(DIRECTORY_SEPARATOR, $location);

        array_pop($parts);
        $path = implode(DIRECTORY_SEPARATOR, $parts);

        if ($this->disk()->isDirectory($path) == false) {
            $this->ensurePathsExists($path);
            $this->disk()->makeDirectory($path);
        }
    }

    protected function getCoreFile()
    {
        $response = '';

        $name = $this->name;

        $parts = explode('.', $name);

        foreach ($parts as $path) {
            if ($response != '') {
                $response .= DIRECTORY_SEPARATOR;
            }

            $response .= $path;
        }

        return $response;
    }

    protected function getFile()
    {
        $file = $this->getCoreFile($this->name);

        return base_path('config'.DIRECTORY_SEPARATOR.$file.'.php');
    }

    protected function prepareContent($from, $validate = true)
    {
        $contents = $this->disk()->get($from);

        $response = $this->toContent($contents, $this->prepareKeys($this->items), $validate);

        return $response;
    }

    protected function prepareKeys(array $config = [])
    {
        $response = [];

        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $this->prepareSubKeys($response, $key, $value);
            } else {
                $response[$key] = $value;
            }
        }

        return $response;
    }

    protected function prepareSubKeys(&$response, $headKey, $array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this->prepareSubKeys($response, $headKey.'.'.$key, $value);
            } else {
                $response[$headKey.'.'.$key] = $value;
            }
        }
    }

    protected function toContent($contents, $newValues, $useValidation = true)
    {
        $contents = $this->parseContent($contents, $newValues);

        if ($useValidation) {
            $result = eval('?>'.$contents);

            foreach ($newValues as $key => $expectedValue) {
                $parts = explode('.', $key);

                $array = $result;

                foreach ($parts as $part) {
                    if (!is_array($array) || !array_key_exists($part, $array)) {
                        throw new \Exception(sprintf('Unable to rewrite key "%s" in config, does it exist?', $key));
                    }

                    $array = $array[$part];
                }

                $actualValue = $array;

                if ($actualValue != $expectedValue) {
                    throw new \Exception(sprintf('Unable to rewrite key "%s" in config, rewrite failed', $key));
                }
            }
        }

        return $contents;
    }

    protected function parseContent($contents, $newValues)
    {
        $patterns = [];

        $replacements = [];

        foreach ($newValues as $path => $value) {
            $items = explode('.', $path);
            $key = array_pop($items);

            if (is_string($value) && strpos($value, "'") === false) {
                $replaceValue = "'".$value."'";
            } elseif (is_string($value) && strpos($value, '"') === false) {
                $replaceValue = '"'.$value.'"';
            } elseif (is_bool($value)) {
                $replaceValue = ($value ? 'true' : 'false');
            } elseif (is_null($value)) {
                $replaceValue = 'null';
            } else {
                $replaceValue = $value;
            }

            $patterns[] = $this->buildStringExpression($key, $items);
            $replacements[] = '${1}${2}'.$replaceValue;
            $patterns[] = $this->buildStringExpression($key, $items, '"');
            $replacements[] = '${1}${2}'.$replaceValue;
            $patterns[] = $this->buildConstantExpression($key, $items);
            $replacements[] = '${1}${2}'.$replaceValue;
        }

        return preg_replace($patterns, $replacements, $contents, 1);
    }

    protected function buildStringExpression($targetKey, $arrayItems = [], $quoteChar = "'")
    {
        $expression = [];

        // Opening expression for array items ($1)
        $expression[] = $this->buildArrayOpeningExpression($arrayItems);

        // The target key opening
        $expression[] = '([\'|"]'.$targetKey.'[\'|"]\s*=>\s*)['.$quoteChar.']';

        // The target value to be replaced ($2)
        $expression[] = '([^'.$quoteChar.']*)';

        // The target key closure
        $expression[] = '['.$quoteChar.']';

        return '/'.implode('', $expression).'/';
    }

    /**
     * Common constants only (true, false, null, integers).
     */
    protected function buildConstantExpression($targetKey, $arrayItems = [])
    {
        $expression = [];

        // Opening expression for array items ($1)
        $expression[] = $this->buildArrayOpeningExpression($arrayItems);

        // The target key opening ($2)
        $expression[] = '([\'|"]'.$targetKey.'[\'|"]\s*=>\s*)';

        // The target value to be replaced ($3)
        $expression[] = '([tT][rR][uU][eE]|[fF][aA][lL][sS][eE]|[nN][uU][lL]{2}|[\d]+)';

        return '/'.implode('', $expression).'/';
    }

    protected function buildArrayOpeningExpression($arrayItems)
    {
        if (count($arrayItems)) {
            $itemOpen = [];

            foreach ($arrayItems as $item) {
                // The left hand array assignment
                $itemOpen[] = '[\'|"]'.$item.'[\'|"]\s*=>\s*(?:[aA][rR]{2}[aA][yY]\(|[\[])';
            }

            // Capture all opening array (non greedy)
            $result = '('.implode('[\s\S]*', $itemOpen).'[\s\S]*?)';
        } else {
            // Gotta capture something for $1
            $result = '()';
        }

        return $result;
    }
}
