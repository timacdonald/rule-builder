<?php

namespace Tests;

class EloquentDummy
{
    const TABLE_NAME = 'table_name';

    const KEY_NAME = 'key_name';

    public function getTable()
    {
        return static::TABLE_NAME;
    }

    public function getKeyName()
    {
        return static::KEY_NAME;
    }
}
