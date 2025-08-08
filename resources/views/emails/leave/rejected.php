@component('mail::message')
# Leave Application Rejected

Hello **{{ $leaveApplication->user->name }}**,

Unfortunately, your leave application for **{{ $leaveApplication->leave_type }}** leave from **{{ $leaveApplication->start_date->format('d M, Y') }}** to **{{ $leaveApplication->end_date->format('d M, Y') }}** has been rejected.

**Reason for Rejection:**
{{ $leaveApplication->rejection_reason ?? 'No reason provided.' }}

If you have questions, please contact your manager.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
