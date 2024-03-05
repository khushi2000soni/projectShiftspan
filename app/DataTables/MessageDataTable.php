<?php

namespace App\DataTables;

use App\Models\Message;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\Gate;

class MessageDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('checkbox', function($record){
                return '<label class="custom-checkbox">
                    <input type="checkbox" class="dt_cb message_cb" data-id="'.$record->id .'" /><span></span>
                </label>';
            })
            ->addColumn('message', function ($record) {
                return '<div class="inner-msg noti-before position-relative">' .
                    '<h3>' . $record->subject . '</h3>' .
                    '<p>' .'Sent: '. $record->message . '</p>' .
                '</div>';
            })
            ->setRowId('id')
            ->filterColumn('message', function ($query, $keyword) {
                $query->where('message', 'LIKE', "%$keyword%")
                      ->orWhere('subject', 'LIKE', "%$keyword%")
                      ->orWhere('section', 'LIKE', "%$keyword%");
            })            
            ->rawColumns(['checkbox', 'message']);
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(Notification $model): QueryBuilder
    { 
        return $model->where('notification_type', 'send_message')->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('message-centre-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->orderBy(0)
                    ->selectStyleSingle()
                    ->buttons([
                        Button::make('excel'),
                        Button::make('csv'),
                        Button::make('pdf'),
                        Button::make('print'),
                        Button::make('reset'),
                        Button::make('reload')
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        $columns = [];
        $columns[] = Column::make('created_at')->title('')->searchable(false)->visible(false);
        if (Gate::check('message_delete')) {
            $columns[] = Column::make('checkbox')->titleAttr('')->title('<label class="custom-checkbox"><input type="checkbox" id="dt_cb_all" ><span></span></label>')->orderable(false)->searchable(false)->addClass('pe-0 position-relative');
        } 
        $columns[] = Column::make('message')->title('<span>'.trans('cruds.message.title_singular').'</span>')->titleAttr(trans('cruds.message.title_singular'))->sortable(false);
        return $columns;

    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Message_' . date('YmdHis');
    }
}