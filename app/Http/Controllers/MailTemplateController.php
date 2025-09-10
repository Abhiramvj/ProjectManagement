<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MailTemplate;

class MailTemplateController extends Controller
{
    public function index()
    {
        // Return list of templates with minimal fields for selection
        $templates = MailTemplate::select('id', 'name','event_type')->get();
        return response()->json($templates);
    }

    public function show($id)
    {
        // Return full details of a single template
        $template = MailTemplate::findOrFail($id);
        return response()->json($template);
    }
}
