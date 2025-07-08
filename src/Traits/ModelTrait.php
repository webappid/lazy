<?php
/**
 * Created by PhpStorm.
 */

namespace WebAppId\Lazy\Traits;

use Illuminate\Support\Facades\Cache;

/**
 * @author: Dyan Galih<dyan.galih@gmail.com>
 * Date: 18/09/2020
 * Time: 09.09
 * Class BaseModel
 * @package ${NAMESPACE}
 */
trait ModelTrait
{
    public function getAllColumn(bool $isFresh = false)
    {
        if ($isFresh) {
            Cache::forget($this->getTable());
        }

        if (!Cache::has($this->getTable())) {
            {
                $columns = $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
                $newColumns = [];
                foreach ($columns as $column) {
                    $newColumns[$column] = $this->getTable() . '.' . $column;
                }
                Cache::forever($this->getTable(), $newColumns);
            }
        }
        return Cache::get($this->getTable());
    }
}
