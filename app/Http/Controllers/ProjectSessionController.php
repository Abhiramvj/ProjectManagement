<?php

namespace App\Http\Controllers;

use App\Notifications\SessionApproved;
use App\Notifications\SessionCompleted;
use App\Notifications\SessionDateUpdated;
use App\Notifications\SessionRejected;
use App\Services\BadgeService;
use App\Services\MilestoneService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Models\ProjectSession;
use App\Models\Badge;
use App\Models\Milestone;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProjectSessionController extends Controller
{
 public function index()
{
    $user = auth()->user();

    // Get badges & milestones as before
    $badges = $user->badges()->latest('badge_user.unlocked_at')->get();
 $milestones = Milestone::with(['users' => function ($query) use ($user) {
        $query->where('users.id', $user->id);
    }])->get()->map(function($milestone) use ($user) {
        $userPivot = $milestone->users->first()
            ? $milestone->users->first()->pivot
            : null;
        return [
            'id' => $milestone->id,
            'name' => $milestone->name,
            'target' => $milestone->target,
            'description' => $milestone->description,
            'icon' => $milestone->icon, // icon file path/name, e.g. /milestones/star.png
            'progress' => $userPivot ? $userPivot->progress : 0,
            'unlocked_at' => $userPivot ? $userPivot->unlocked_at : null,
        ];
    });



    // Fetch upcoming sessions for user, excluding completed ones
    $upcomingSessions = $user->projectSessions()
        ->whereIn('status', ['pending', 'approved']) // include only active/pending/approved
        ->orderBy('date', 'asc')
        ->get()
        ->map(function ($session) {
            return [
                'id' => $session->id,
                'topic' => $session->topic,
                'description' => $session->description,
                'date' => $session->date,
                'status' => $session->status,
                'requester' => [
                    'id' => $session->requester->id,
                    'name' => $session->requester->name,
                ],
            ];
        });

      $leaderboard = User::withCount([
    'badges',
    'milestones as milestones_count' => function ($query) {
        $query->whereNotNull('milestone_user.unlocked_at')
              ->whereColumn('milestone_user.progress', '>=', 'milestones.target'); // completed only
    },
    'projectSessions as project_sessions_count' => function ($query) {
        $query->where('status', 'completed');
    },
])
->orderByDesc('project_sessions_count')
->paginate(10, ['id', 'name', 'designation']);


    return Inertia::render('Sessions/Index', [
        'user' => [
            'name' => $user->name,
            'designation' => $user->designation,
            'email' => $user->email,
        ],
        'badges' => $badges,
        'milestones' => $milestones,
        'sessionsCount' => $user->projectSessions()->count(),
        'upcomingSessions' => $upcomingSessions,
        'leaderboard' => $leaderboard
    ]);
}



    // Show form to create a session request
    public function create()
    {
        return Inertia::render('Sessions/SessionCreate');
    }

    // Store the session request
    public function store(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|max:255',
            'date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        ProjectSession::create([
            'topic' => $request->topic,
            'date' => $request->date,
            'description' => $request->description,
            'requester_id' => Auth::id(),
            'status' => 'pending',
        ]);

        return redirect()->route('sessions.index')->with('success', 'Session request submitted.');
    }

    // HR/Admin dashboard view
    public function hrAdminDashboard()
{
    $sessions = ProjectSession::with('requester')
        ->whereIn('status', ['pending', 'approved', 'rejected','completed'])
        ->latest()
        ->get();


    $pendingCount = $sessions->where('status', 'pending')->count();
    $approvedCount = $sessions->where('status', 'approved')->count();
    $rejectedCount = $sessions->where('status', 'rejected')->count();
    $completedCount = $sessions->where('status', 'completed')->count();

    return Inertia::render('Sessions/HrAdminDashboard', [
        'sessions' => $sessions,
        'pendingCount' => $pendingCount,
        'approvedCount' => $approvedCount,
        'rejectedCount' => $rejectedCount,
        'completedCount' => $completedCount,
    ]);
}


    // Approve session request (HR/Admin)
   public function approve($id)
    {
        try {
            $session = ProjectSession::findOrFail($id);
            $session->update(['status' => 'approved']);
            $session->requester->notify(new SessionApproved($session));

            return redirect()->back()->with('success', 'Session approved successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to approve session: ' . $e->getMessage());
        }
    }

 public function reject($id)
    {
        try {
            $session = ProjectSession::findOrFail($id);
            $session->update(['status' => 'rejected']);
            $session->requester->notify(new SessionRejected($session));

            return redirect()->back()->with('success', 'Session rejected successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to reject session: ' . $e->getMessage());
        }
    }

    public function updateDate(Request $request, $id)
    {
        try {
            $request->validate([
                'date' => 'required|date',
            ]);


            $session = ProjectSession::findOrFail($id);
            $session->update([
                'date' => $request->date,
            ]);
            $session->requester->notify(new SessionDateUpdated($session));

            return redirect()->back()->with('success', 'Session date updated successfully');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update session date: ' . $e->getMessage());
        }
    }


public function complete($id, BadgeService $badgeService, MilestoneService $milestoneService)
{
    $session = ProjectSession::findOrFail($id);
    $session->update([
        'status' => 'completed',
        'completed_at' => now(),
    ]);

    $session->requester->notify(new SessionCompleted($session));

    $badgeService->awardBadgesToUser($session->requester, $session->id);
    $milestoneService->updateMilestonesForUser($session->requester, $session->id);

    return redirect()->back()->with('success', 'Session marked as completed.');
}





}
