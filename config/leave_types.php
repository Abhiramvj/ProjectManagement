<?php

return [
    'annual' => [
        'title' => 'Annual Leave',
        'summary' => 'Planned time off with advance notice',
        'details' => [
            'For vacations, personal time, or other planned absences',
            'Should be requested at least 7 days in advance',
            'Requires manager approval',
            'Balance accrued based on tenure (typically 15-25 days/year)',
        ],
        'icon' => '🌴',
    ],
    'personal' => [
        'title' => 'Personal Leave',
        'summary' => 'Leave for personal matters that require time off',
        'details' => [
            'Used for personal errands, family commitments, or important events',
            'Typically requires 3 days advance notice',
            'Needs manager approval',
            'May be limited in total number of days per year',
        ],
        'icon' => '📝',
    ],
    'sick' => [
        'title' => 'Sick Leave',
        'summary' => 'Leave granted for health-related issues',
        'details' => [
            'For medical appointments, illness, or recovery',
            'Usually requires a medical certificate for extended absences',
            'Accrued based on company policy and local law',
            'May be paid or unpaid as per policy',
        ],
        'icon' => '🤒',
    ],
    'emergency' => [
        'title' => 'Emergency Leave',
        'summary' => 'Leave for urgent, unforeseen personal emergencies',
        'details' => [
            'For unexpected situations like medical emergencies, accidents, or urgent family matters',
            'Typically granted on short notice with flexible approval process',
            'Usually paid leave, separate from annual or personal leave balances',
            'Helps employees manage critical situations without penalty',
        ],
        'icon' => '🚨',
    ],
    'maternity' => [
        'title' => 'Maternity Leave',
        'summary' => 'Leave for childbirth and related recovery',
        'details' => [
            'Granted to employees during pregnancy and after birth',
            'Duration varies by jurisdiction (typically 12-26 weeks)',
            'May require medical documentation',
            'Protected under employment law',
        ],
        'icon' => '🤰',
    ],
    'paternity' => [
        'title' => 'Paternity Leave',
        'summary' => 'Leave for fathers around the time of childbirth',
        'details' => [
            'Allows bonding with the newborn and support for the family',
            'Usually shorter duration than maternity leave (e.g., 1-2 weeks)',
            'Requires notice as per company policy',
            'Protected under employment law',
        ],
        'icon' => '👨‍🍼',
    ],
    'wfh' => [
        'title' => 'Work From Home',
        'summary' => 'Remote work arrangements without reducing leave balance',
        'details' => [
            'Allows employees to work remotely for full or partial days',
            'Usually does not deduct from leave balance',
            'Requires manager’s approval',
            'Supports flexible work-life balance',
        ],
        'icon' => '💻',
    ],
    'compensatory' => [
        'title' => 'Compensatory Leave',
        'summary' => 'Leave earned by working extra hours or on holidays',
        'details' => [
            'Credited when working during official holidays',
            'Used as paid time off in lieu of extra hours worked',
            'Requires manager approval to grant and to use',
            'Usually tracked separately to enforce usage policies',
        ],
        'icon' => '⏰',
    ],
];
