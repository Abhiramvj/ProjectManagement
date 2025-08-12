@component('mail::message')
# New Leave Application Submitted

Hello,

A new leave application has been submitted and is awaiting your review.

## Application Details

- **Employee:** {{ $leaveApplication->user->name }}
- **Leave Type:** {{ ucfirst($leaveApplication->leave_type) }}
- **Start Date:** {{ \Carbon\Carbon::parse($leaveApplication->start_date)->format('M d, Y') }}
- **End Date:** {{ \Carbon\Carbon::parse($leaveApplication->end_date)->format('M d, Y') }}
- **Duration:** {{ $leaveApplication->leave_days }} day(s)
- **Day Type:** {{ ucfirst(str_replace('_', ' ', $leaveApplication->day_type ?? 'full_day')) }}
- **Reason:** {{ $leaveApplication->reason }}

**Leave Period:** {{ \Carbon\Carbon::parse($leaveApplication->start_date)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($leaveApplication->end_date)->format('M d, Y') }}

Please review this application and take appropriate action.

@component('mail::button', ['url' => route('leave.index')])
Review Applications
@endcomponent

Thanks,<br>
{{ config('app.name') }}

@endcomponent
