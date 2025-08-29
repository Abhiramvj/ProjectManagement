<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <title>Log: {{ $mailLog->subject }}</title>
        <style>
            /* Add some very basic, self-contained CSS */
            body {
                font-family: sans-serif;
                padding: 20px;
                color: #333;
            }
            .container {
                border: 1px solid #ccc;
                padding: 15px;
                border-radius: 5px;
            }
            h1 {
                font-size: 24px;
            }
            p {
                margin: 10px 0;
            }
            strong {
                color: #000;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>{{ $mailLog->subject }}</h1>
            <p>
                <strong>Recipient:</strong>
                {{ $mailLog->recipient_email }}
            </p>
            <hr />
            <h3>Details from Mail</h3>
            <p>
                <strong>Leave Period:</strong>
                {{ $mailLog->leave_period ?? 'Not available' }}
            </p>
            <p>
                <strong>Reason:</strong>
                {{ $mailLog->reason ?? 'Not available' }}
            </p>
            <hr />
            <h3>Log Information</h3>
            <p>
                <strong>Status:</strong>
                {{ $mailLog->status }}
            </p>
            <p>
                <strong>Event Type:</strong>
                {{ $mailLog->event_type }}
            </p>
            <p>
                <strong>Sent At:</strong>
                {{ $mailLog->sent_at }}
            </p>
        </div>
    </body>
</html>
