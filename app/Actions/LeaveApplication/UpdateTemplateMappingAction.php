<?php

namespace App\Actions\LeaveApplication;

use App\Models\TemplateMapping;

class UpdateTemplateMappingAction
{
    /**
     * Update or create a template mapping.
     */
    public function handle(string $eventType, string $mailTemplateId): TemplateMapping
    {
        return TemplateMapping::updateOrCreate(
            ['event_type' => $eventType],
            ['mail_template_id' => $mailTemplateId]
        );
    }
}
