@component('mail::message')
    # Leave Application Approved Hello {{ $leaveApplication->user->name }},
    Great news! Your leave application has been reviewed and approved. ##
    Approved Application Details - **Employee:**
    {{ $leaveApplication->user->name }} - **Leave Type:**
    {{ ucfirst($leaveApplication->leave_type) }} - **Start Date:**
    {{ \Carbon\Carbon::parse($leaveApplication->start_date)->format('M d, Y') }}
    - **End Date:**
    {{ \Carbon\Carbon::parse($leaveApplication->end_date)->format('M d, Y') }}
    - **Duration:** {{ $leaveApplication->leave_days }} day(s) - **Day Type:**
    {{ ucfirst(str_replace('_', ' ', $leaveApplication->day_type ?? 'full_day')) }}
    **Approved Leave Period:**
    {{ \Carbon\Carbon::parse($leaveApplication->start_date)->format('M d, Y') }}
    to
    {{ \Carbon\Carbon::parse($leaveApplication->end_date)->format('M d, Y') }}

    @if (! empty($leaveApplication->remarks))
        ## Approver's Remarks
        {{ $leaveApplication->remarks }}
    @endif

    We wish you a pleasant time off.

    @component('mail::button', ['url' => route('leave.index'), 'color' => 'green'])
        View My Leave Status
    @endcomponent

    Thanks,
    <br />
    {{ config('app.name') }}
@endcomponent
