<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckSentEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:sent-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check sent emails from the array driver';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking sent emails...');

        try {
            $emails = Mail::getSymfonyTransport()->messages();
            
            $this->info('Number of sent emails: ' . count($emails));
            
            if (count($emails) > 0) {
                $latestEmail = $emails[0];
                
                $this->info('Latest email details:');
                $this->line('Subject: ' . $latestEmail->getSubject());
                $this->line('To: ' . implode(', ', array_keys($latestEmail->getTo())));
                $this->line('From: ' . implode(', ', array_keys($latestEmail->getFrom())));
                
                $this->info('Email HTML content (first 500 chars):');
                $this->line('---');
                $this->line(substr($latestEmail->getHtmlBody(), 0, 500) . '...');
                $this->line('---');
                
                $this->info('Email text content (first 500 chars):');
                $this->line('---');
                $this->line(substr($latestEmail->getTextBody(), 0, 500) . '...');
                $this->line('---');
            } else {
                $this->info('No emails found in the array driver.');
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error checking emails: ' . $e->getMessage());
            return 1;
        }
    }
}

