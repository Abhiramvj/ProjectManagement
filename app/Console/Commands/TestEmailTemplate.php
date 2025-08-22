<?php

namespace App\Console\Commands;

use App\Mail\LeaveApplicationSubmitted;
use App\Models\LeaveApplication;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailTemplate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email-template {--send : Actually send the email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the email template rendering';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing email template...');

        // Get the first leave application
        $leaveApplication = LeaveApplication::with('user')->first();

        if (! $leaveApplication) {
            $this->error('No leave applications found in the database.');

            return 1;
        }

        $this->info("Found leave application ID: {$leaveApplication->id}");
        $this->info("Employee: {$leaveApplication->user->name}");

        try {
            // Create the mailable
            $mailable = new LeaveApplicationSubmitted($leaveApplication);

            // Render the email content
            $rendered = $mailable->render();

            $this->info('Email template rendered successfully!');
            $this->info('Subject: '.$mailable->subject);
            $this->info('Content length: '.strlen($rendered).' characters');

            // Show a preview of the rendered content
            $this->info('Preview of rendered email:');
            $this->line('---');
            $this->line(substr($rendered, 0, 500).'...');
            $this->line('---');

            // If --send flag is provided, actually send the email
            if ($this->option('send')) {
                $this->info('Sending email...');

                // Send to a test email (you can change this)
                $testEmail = 'test@example.com';

                try {
                    Mail::to($testEmail)->send($mailable);
                    $this->info("Email sent successfully to {$testEmail}");
                    $this->info('Check your mailer (Mailtrap, log, etc.) to see the email.');
                } catch (\Exception $e) {
                    $this->error('Failed to send email: '.$e->getMessage());
                    $this->error('This might be due to mailer configuration.');
                }
            } else {
                $this->info('Use --send flag to actually send the email for testing.');
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Error rendering email template: '.$e->getMessage());
            $this->error('Stack trace: '.$e->getTraceAsString());

            return 1;
        }
    }
}
