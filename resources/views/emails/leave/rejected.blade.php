@component('mail::message')
# Leave Application Rejected

Hello {{ $leaveApplication->user->name }},

We have reviewed your recent leave application. Unfortunately, we are unable to approve your request at this time.

## Application Details

- **Employee:** {{ $leaveApplication->user->name }}
- **Leave Type:** {{ ucfirst($leaveApplication->leave_type) }}
- **Start Date:** {{ \Carbon\Carbon::parse($leaveApplication->start_date)->format('M d, Y') }}
- **End Date:** {{ \Carbon\Carbon::parse($leaveApplication->end_date)->format('M d, Y') }}
- **Duration:** {{ $leaveApplication->leave_days }} day(s)
- **Reason for Request:** {{ $leaveApplication->reason }}

## Reason for Rejection

{{--
  This is the key change. We now use the $rejectionReason variable passed
  directly from our Mailable class.
--}}
**{{ $rejection_reason ?? 'No specific reason was provided.' }}**

If you have any questions or would like to discuss this further, please contact your manager.

@component('mail::button', ['url' => route('leave.index'), 'color' => 'red'])
View Application Status
@endcomponent

Thanks,<br>
{{ config('app.name') }}

@endcomponent
