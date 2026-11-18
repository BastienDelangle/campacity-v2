<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\ReportReason;
use App\Models\Publication;
use App\Models\PublicationReport;
use App\Models\User;
use App\Notifications\NewPublicationReport;
use App\Support\SpamGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class PublicationReportController extends Controller
{
    public function store(Request $request, Publication $publication): RedirectResponse
    {
        abort_unless($publication->isPubliclyVisible(), 404);

        SpamGuard::check($request, 'report');

        $data = $request->validateWithBag('report', [
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'comment' => ['nullable', 'string', 'max:2000', Rule::requiredIf($request->input('reason') === ReportReason::Other->value)],
            'reporter_email' => ['nullable', 'email', 'max:255'],
        ], attributes: ['reason' => 'motif', 'comment' => 'commentaire', 'reporter_email' => 'adresse e-mail']);

        $ipHash = SpamGuard::ipHash($request);

        // Un même visiteur ne signale pas deux fois la même publication.
        $alreadyReported = PublicationReport::where('publication_id', $publication->id)
            ->where(fn ($q) => $q->where('ip_hash', $ipHash)->when($request->user(), fn ($q, $user) => $q->orWhere('reporter_id', $user->id)))
            ->exists();

        if (! $alreadyReported) {
            $report = PublicationReport::create([
                'publication_id' => $publication->id,
                'reporter_id' => $request->user()?->id,
                'reporter_email' => $request->user()?->email ?? ($data['reporter_email'] ?? null),
                'reason' => $data['reason'],
                'comment' => $data['comment'] ?? null,
                'ip_hash' => $ipHash,
            ]);

            Notification::send(User::activeWithPermission(Permission::ManageReports)->get(), new NewPublicationReport($report));
        }

        return redirect()->to(route('publications.show', $publication->slug).'#signaler')
            ->with('report_status', 'Merci, votre signalement a été transmis à l\'équipe de modération.');
    }
}
