<?php
/**
 * Created by PhpStorm.
 */

namespace WebAppId\Lazy\Traits;


use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @author: Dyan Galih<dyan.galih@gmail.com>
 * Date: 19/09/2020
 * Time: 09.01
 * Class RepositoryTrait
 * @package WebAppId\Lazy\Traits
 */
trait RepositoryTrait
{

    /**
     * @var array
     */
    protected array $joinTable = [];
    /**
     * @var array
     */
    private array $column = [];

    /**
     * @param Model $model
     * @return Model
     */
    protected function getJoin(Model $model): Model
    {
        $this->column[$model->getTable()] = $model->getColumns();

        $builder = $model;
        foreach ($this->joinTable as $key => $value) {
            try {
                $table = app()->make($value->class);
                $this->column[$key] = $table->getColumns();
                $builder = $builder->join(
                    $table->getTable() . ' as ' . $key,
                    (!str_contains($value->foreign, '.') ? $model->getTable() . '.' : '') . $value->foreign,
                    '=',
                    ($value->primary ?? $key . '.' . $table->getKeyName()),
                    $value->type ?? 'inner');
            } catch (BindingResolutionException $e) {
                report($e);
            }
        }

        return $builder;
    }

    /**
     * @param bool $isAssociative
     * @return array
     */
    protected function getColumn(bool $isAssociative = false): array
    {
        $resultColumn = [];

        foreach ($this->column as $table => $column) {
            foreach ($column as $key => $value) {
                if ($value instanceof Expression) {
                    if (Str::contains($value, 'as')) {
                        $values = explode(' as ', $value);
                        $value = $values[0];
                    } elseif (Str::contains($value, 'AS')) {
                        $values = explode(' AS ', $value);
                        $value = $values[0];
                    }
                    if (!isset($resultColumn[$key])) {
                        $resultColumn[$key] = DB::raw($value . ' AS ' . $key);
                    } else {
                        $resultColumn[$table . '_' . $key] = DB::raw($value . ' AS ' . Str::singular($table) . '_' . $key);
                    }
                } else {
                    if (!isset($resultColumn[$key])) {
                        $values = explode('.', $value);
                        $resultColumn[$key] = $table . '.' . $values[1];
                    } else {
                        $resultColumn[$table . '_' . $key] = $table . '.' . $key . ' AS ' . Str::singular($table) . '_' . $key;
                    }
                }
            }
        }

        ksort($resultColumn);

        if ($isAssociative) {
            return $resultColumn;
        } else {
            return array_values($resultColumn);
        }
    }
}
