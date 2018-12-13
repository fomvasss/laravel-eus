<?php
/**
 * Created by PhpStorm.
 * User: fomvasss
 * Date: 23.10.18
 * Time: 21:25
 */

namespace Fomvasss\LaravelEUS;

use Illuminate\Database\Eloquent\Model;

class EUSGenerator
{
    const CONFIG_FILE_NAME = 'eus';

    protected $app;
    
    protected $config = [];

    protected $entity;

    protected $rawStr = '';

    /**
     * EUSGenerator constructor.
     *
     * @param $app
     */
    public function __construct($app = null)
    {
        if (! $app) {
            $app = app();   //Fallback when $app is not given
        }
        $this->app = $app;

        $this->config = $this->app['config']->get(self::CONFIG_FILE_NAME);
    }

    /**
     * Generate and save unique string value in specified field.
     *
     * @return mixed
     * @throws \Exception
     */
    public function save(Model $entity = null, $rawStr = '')
    {
        $this->entity = $entity ?: $this->entity;
        $this->rawStr = $rawStr ?: $this->rawStr;

        $str = $this->get();
        try {
            $this->entity->{$this->config['field_name_for_unique_str']} = $str;

            return $this->entity->save();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Generate unique string value.
     *
     * @return string
     * @throws \Exception
     */
    public function get()
    {
        $nonUniqueStr = $this->makeNonUniqueStr($this->rawStr);

        return $this->makeUniqueStr($nonUniqueStr);
    }

    /**
     * @param string|null $rawStr
     * @return \Fomvasss\LaravelEUS\EUSGenerator
     */
    public function setRawStr(string $rawStr = null): self
    {
        $this->rawStr = $rawStr;
        
        return $this;
    }

    /**
     * @param Model $entity
     * @return EUSGenerator
     */
    public function setEntity(Model $entity): self
    {
        $this->entity = $entity;
        
        return $this;
    }

    /**
     * @param string $fieldNameForUniqueStr
     * @return EUSGenerator
     */
    public function setFieldName(string $fieldNameForUniqueStr): self
    {
        $this->config['field_name_for_unique_str'] = $fieldNameForUniqueStr;

        return $this;
    }

    /**
     * @param string $modelPrimaryKey
     * @return \Fomvasss\LaravelEUS\EUSGenerator
     */
    public function setModelPrimaryKey(string $modelPrimaryKey): self
    {
        $this->config['model_primary_key'] = $modelPrimaryKey;

        return $this;
    }

    /**
     * @param string $separator
     * @return \Fomvasss\LaravelEUS\EUSGenerator
     */
    public function setSlugSeparator(string $separator): self
    {
        $this->config['str_slug_separator'] = $separator;

        return $this;
    }

    /**
     * @param string $separator
     * @return \Fomvasss\LaravelMetaTags\EUSGenerator
     */
    public function setSegmentsSeparator(string $separator): self
    {
        $this->config['str_segments_separator'] = $separator;

        return $this;
    }

    /**
     * @param string $rawStr
     * @return string
     */
    protected function makeNonUniqueStr(string $rawStr): string
    {
        if ($str_segments_separator = $this->config['str_segments_separator']) {
            $res = array_map(function ($str) {

                return str_slug($this->getClippedSlugWithPrefixSuffix($str), $this->config['str_slug_separator']);

            }, explode($str_segments_separator, $rawStr));

            return implode($str_segments_separator, $res);
        }

        return str_slug($this->getClippedSlugWithPrefixSuffix($rawStr), $this->config['str_slug_separator']);
    }

    /**
     * @param string $str
     * @return string
     */
    protected function getClippedSlugWithPrefixSuffix(string $str): string
    {
        $prefix = $this->config['prefix'];
        $suffix = $this->config['suffix'];
        $maximumLength= $this->config['max_length'];
        
        if ($strLen = strlen($prefix) + strlen($suffix)) {
            $limitWithoutPrefixSuffix = $maximumLength - ($strLen + 2);

            if ($limitWithoutPrefixSuffix < 1) {
                return str_limit($prefix . ' ' . $suffix, $maximumLength);
            }
            
            return $prefix.' '.str_limit($str, $limitWithoutPrefixSuffix, '').' '.$suffix;
        }
        
        return str_limit($str, $maximumLength, '');
    }

    /**
     * @param string $str
     * @return string
     * @throws \Exception
     */
    protected function makeUniqueStr(string $str): string
    {
        $notUniqueStr = $str;
        $i = 1;
        
        while ($this->isOtherRecordExists($str) || $str === '') {
            $str = $notUniqueStr . $this->config['str_slug_separator'] . $i++;
        }
        
        return $str;
    }

    /**
     * @param string $str
     * @return bool
     * @throws \Exception
     */
    protected function isOtherRecordExists(string $str): bool
    {
        try {
            $modelClass = $this->app->make(get_class($this->entity));
            $primaryKey = $this->config['model_primary_key'];
            $fieldNameForUnique = $this->config['field_name_for_unique_str'];
            
            return (bool) $modelClass::withoutGlobalScopes()
                ->where($primaryKey, '<>', optional($this->entity)->{$primaryKey}) // except check self entity
                ->where($fieldNameForUnique, $str)
                ->first();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}