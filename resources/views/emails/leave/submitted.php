@component('mail::message')
# New Leave Application Submitted

Hello **{{ $leaveApplication->user->name }}**,

A new leave application has been submitted and is awaiting your review.

...

@endcomponent
