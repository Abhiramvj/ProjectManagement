@component('mail::message')
# Leave Application Approved

Hello **{{ $leaveApplication->user->name }}**,

Your leave application for **{{ $leaveApplication->leave_type }}** leave from **{{ $leaveApplication->start_date->format('d M, Y') }}** to **{{ $leaveApplication->end_date->format('d M, Y') }}** has been approved.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
