<?php

namespace App\DataTables;

use App\Models\Location;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;

class LocationDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */

    private $authUser;

    public function __construct()
    {
        $this->authUser = auth()->user();
    }


    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            // ->addIndexColumn()
            ->addColumn('checkbox', function($record){
                return '<label class="custom-checkbox"><input type="checkbox" class="dt_cb location_cb" data-id="'.$record->uuid.'" /><span></span></label>';
            })
            ->editColumn('name', function($record){
                return $record->name ?? '';
            })
            
            /* ->editColumn('createdBy.name', function($record){
                return ($record->createdBy) ? $record->createdBy->name : '';
            }) */
            ->addColumn('action', function($record){
                $actionHtml = '';
                if (Gate::check('location_edit')) {
                    if($this->authUser->is_super_admin){
                        $actionHtml .= '<button class="dash-btn sky-bg small-btn icon-btn editLocationBtn"  data-href="'.route('locations.edit', $record->uuid).'" title="Edit">
                            <span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="'.__('global.edit').'">
                                '.(getSvgIcon('edit')).'
                            </span>
                        </button>';
                    }
                }
                if (Gate::check('location_delete')) {
				    $actionHtml .= '<button class="dash-btn red-bg small-btn icon-btn deleteLocationBtn" data-href="'.route('locations.destroy', $record->uuid).'" title="Delete">
                        <span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="'.__('global.delete').'">
                            '.(getSvgIcon('delete')).'
                        </span>
                    </button>';
                }
                return $actionHtml;
            })
            ->setRowId('id')
            ->rawColumns(['action', 'checkbox']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Location $model): QueryBuilder
    {
        $user = $this->authUser;
        if($user->is_super_admin){
            return $model->newQuery();
        } else {
            return Location::join('location_user', function ($join) use ($user) {
                $join->on('location_user.location_id', '=', 'locations.id')
                     ->where('location_user.user_id', '=', $user->id);
            })->select('locations.*');
        }
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        $orderByColumn = 1;
        if (Gate::check('location_delete')) {
            $orderByColumn = 2;
        }
        return $this->builder()
                    ->setTableId('location-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    //->dom('Bfrtip')
                    ->orderBy($orderByColumn)                    
                    ->selectStyleSingle()
                    ->lengthMenu([
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, 'All']
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        $columns = [];
        if (Gate::check('location_delete')) {
            $columns[] = Column::make('checkbox')->titleAttr('')->title('<label class="custom-checkbox"><input type="checkbox" id="dt_cb_all" ><span></span></label>')->orderable(false)->searchable(false)->addClass('pe-0 position-relative');
        } 
        $columns[] = Column::make('name')->title('<span>'.trans('cruds.location.fields.name').'</span>')->titleAttr(trans('cruds.location.fields.name'));
        $columns[] = Column::make('created_at')->title(trans('cruds.location.fields.created_at'))->searchable(false)->visible(false);
        $columns[] = Column::computed('action')->exportable(false)->printable(false)->width(60)->addClass('text-center');

        return $columns;
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Location_' . date('YmdHis');
    }
}
