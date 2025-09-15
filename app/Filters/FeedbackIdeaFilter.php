<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FeedbackIdeaFilter
{
    protected Builder $query;

    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply(Builder $query): Builder
    {
        $this->query = $query;

        $this->filterByUser();
        $this->filterByDate();

        return $this->query->orderBy('created_at', 'desc');
    }

    protected function filterByUser()
    {
        if ($this->request->employee_id) {
            $this->query->where('user_id', $this->request->employee_id);
        } elseif ($this->request->type && auth()->user()->hasRole('employee')) {
            $this->query->where('user_id', auth()->id());
        }
    }

    protected function filterByDate()
    {
        $start = $this->request->start_date;
        $end = $this->request->end_date;
        $filter = $this->request->date_filter;

        if ($start && $end) {
            $this->query->whereBetween('created_at', [$start, $end]);
        } elseif ($filter === 'today') {
            $this->query->whereDate('created_at', now()->toDateString());
        } elseif ($filter === 'week') {
            $this->query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($filter === 'month') {
            $this->query->whereMonth('created_at', now()->month);
        }
    }
}
