<?php

namespace App\DataTables;

use App\Models\User;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;

use function Laravel\Prompts\select;

class StaffDataTable extends DataTable
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
                return '<label class="custom-checkbox"><input type="checkbox" class="dt_cb staff_cb" data-id="'.$record->uuid.'" /><span></span></label>';
            })

            ->editColumn('created_at', function($record) {
                return $record->created_at->format('d-m-Y');
            })
            
            ->editColumn('staff_image', function($record){
                return '<div class="staff-img"><img src="'.($record->profile_image_url ? $record->profile_image_url : asset(config('constant.default.staff-image'))).'" alt=""></div>';
            })

            ->editColumn('name', function($record){
                return $record->name ? ucwords($record->name) : '';
            })

            ->editColumn('email', function($record){
                return $record->email ?? '';
            })

            ->editColumn('is_active', function($record){
                $statusHtml = '<select class="select changeStaffStatus" data-id="'.$record->uuid.'" data-old_value="'.$record->is_active.'">';
                    foreach(config('constant.user_status') as $key => $value){
                        $statusHtml .= '<option value="'.$key.'" '.($record->is_active == $key ? 'selected' : '').'>'.$value.'</option>';
                    }
                $statusHtml .= '</select>';
                return $statusHtml;
            })
            
            ->addColumn('action', function($record){
                $actionHtml = '';
                if (Gate::check('staff_edit')) {
                    if($this->authUser->is_super_admin){
                        $actionHtml .= '<button class="dash-btn yellow-bg small-btn icon-btn viewStaffBtn"  data-href="'.route('staffs.show', $record->uuid).'">
                            <span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="'.__('global.view').'">
                                '.(getSvgIcon('view')).'
                            </span>
                        </button>';
                    }
                }
                if (Gate::check('staff_edit')) {
                    if($this->authUser->is_super_admin){
                        $actionHtml .= '<button class="dash-btn sky-bg small-btn icon-btn editStaffBtn"  data-href="'.route('staffs.edit', $record->uuid).'">
                            <span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="'.__('global.edit').'">
                                '.(getSvgIcon('edit')).'
                            </span>
                        </button>';
                    }
                }
                if (Gate::check('staff_delete')) {
				    $actionHtml .= '<button class="dash-btn red-bg small-btn icon-btn deleteStaffBtn" data-href="'.route('staffs.destroy', $record->uuid).'">
                        <span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="'.__('global.delete').'">
                            '.(getSvgIcon('delete')).'
                        </span>
                    </button>';
                }
                return $actionHtml;
            })
            ->setRowId('id')

            ->filterColumn('created_at', function ($query, $keyword) {
                $query->whereRaw("DATE_FORMAT(created_at,'%d-%m-%Y') like ?", ["%$keyword%"]); //date_format when searching using date
            })
            ->rawColumns(['action', 'checkbox', 'staff_image', 'is_active']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(User $model): QueryBuilder
    {
        return $model->whereHas('roles',function($query){
            $query->where('id',config('constant.roles.staff'));
        })->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        $orderByColumn = 3;
        if (Gate::check('staff_delete')) {
            $orderByColumn = 4;
        }
        return $this->builder()
                    ->setTableId('staff-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    //->dom('Bfrtip')
                    ->orderBy($orderByColumn)                    
                    ->selectStyleSingle();
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        $columns = [];
        if (Gate::check('staff_delete')) {
            $columns[] = Column::make('checkbox')->titleAttr('')->title('<label class="custom-checkbox"><input type="checkbox" id="dt_cb_all" ><span></span></label>')->orderable(false)->searchable(false)->addClass('pe-0 position-relative');
        }

        $columns[] = Column::make('staff_image')->title(trans('cruds.staff.fields.staff_image'))->titleAttr(trans('cruds.staff.fields.staff_image'))->searchable(false)->orderable(false);
        $columns[] = Column::make('name')->title('<span>'.trans('cruds.staff.fields.name').'</span>')->titleAttr(trans('cruds.staff.fields.name'));
        $columns[] = Column::make('email')->title('<span>'.trans('cruds.staff.fields.email').'</span>')->titleAttr(trans('cruds.staff.fields.email'));
        $columns[] = Column::make('created_at')->title('<span>'.trans('cruds.staff.fields.created_at').'</span>')->titleAttr(trans('cruds.staff.fields.created_at'));
        $columns[] = Column::make('is_active')->title('<span>'.trans('cruds.staff.fields.status').'</span>')->titleAttr(trans('cruds.staff.fields.status'));

        $columns[] = Column::computed('action')->exportable(false)->printable(false)->width(60)->addClass('text-center');

        return $columns;
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Staff_' . date('YmdHis');
    }
}