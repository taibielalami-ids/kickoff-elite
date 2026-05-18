<?php

class CommunityController extends Controller
{
    private TeamAd $teamAds;
    private BackupCall $backupCalls;
    private Pitch $pitches;

    public function __construct()
    {
        $this->teamAds = new TeamAd();
        $this->backupCalls = new BackupCall();
        $this->pitches = new Pitch();
    }

    public function teamAds(): void
    {
        $this->requireAuth();
        $viewerId = $this->userId();
        $city = trim($_GET['city'] ?? '');
        $ads = $this->teamAds->listActive($viewerId, $city);
        $adIds = array_map(static fn(array $ad): int => (int) ($ad['id'] ?? 0), $ads);
        $membersByAd = $this->teamAds->membersByAdIds($adIds);

        $this->view('community/team-ads', [
            'ads' => $ads,
            'membersByAd' => $membersByAd,
            'positionSlots' => $this->teamAds->positionSlots(),
            'cityOptions' => $this->teamAds->cityOptions(),
            'selectedCity' => $city,
            'pitches' => $this->pitches->search([]),
        ]);
    }

    public function createTeamAd(): void
    {
        $this->requireAuth();
        $this->ensureCsrf('/team-ads');

        $pitchId = (int) ($_POST['pitch_id'] ?? 0);
        $day = trim($_POST['day'] ?? '');
        $hour = trim($_POST['hour'] ?? '');
        $teamSizeTarget = (int) ($_POST['team_size_target'] ?? 10);
        $notes = trim($_POST['notes'] ?? '');
        $creatorRole = trim($_POST['role_name'] ?? '');
        $creatorSlot = trim($_POST['position_slot'] ?? '');

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) !== 1 || preg_match('/^\d{2}:\d{2}$/', $hour) !== 1) {
            $this->redirectWith('/team-ads', 'danger', 'Pick a valid day and hour.');
        }
        if (!in_array($creatorRole, ['goalkeeper', 'defender', 'midfielder', 'attacker'], true)) {
            $creatorRole = 'midfielder';
        }

        $result = $this->teamAds->createAd(
            $this->userId(),
            $pitchId,
            $day . ' ' . $hour . ':00',
            $teamSizeTarget,
            $notes,
            $creatorRole,
            $creatorSlot
        );
        if (!($result['ok'] ?? false)) {
            $this->redirectWith('/team-ads', 'danger', (string) ($result['error'] ?? 'Could not create team ad.'));
        }

        activity_log('team_ad_created', 'Team ad created', 'team_ad', (int) ($result['ad_id'] ?? 0), $this->userId());
        $this->redirectWith('/team-ads', 'success', 'Team ad created successfully.');
    }

    public function joinTeamAd(): void
    {
        $this->requireAuth();
        $this->ensureCsrf('/team-ads');

        $adId = (int) ($_POST['ad_id'] ?? 0);
        $roleName = trim($_POST['role_name'] ?? '');
        $slotKey = trim($_POST['position_slot'] ?? '');
        if (!in_array($roleName, ['goalkeeper', 'defender', 'midfielder', 'attacker'], true)) {
            $roleName = 'midfielder';
        }

        $result = $this->teamAds->joinAd($adId, $this->userId(), $roleName, $slotKey);
        if (!($result['ok'] ?? false)) {
            $this->redirectWith('/team-ads', 'danger', (string) ($result['error'] ?? 'Could not join team ad.'));
        }

        activity_log('team_ad_joined', 'Joined team ad', 'team_ad', $adId, $this->userId());
        $this->redirectWith('/team-ads', 'success', 'Joined team ad successfully.');
    }

    public function leaveTeamAd(): void
    {
        $this->requireAuth();
        $this->ensureCsrf('/team-ads');

        $adId = (int) ($_POST['ad_id'] ?? 0);
        $result = $this->teamAds->leaveAd($adId, $this->userId());
        if (!($result['ok'] ?? false)) {
            $this->redirectWith('/team-ads', 'danger', (string) ($result['error'] ?? 'Could not leave team ad.'));
        }

        activity_log('team_ad_left', 'Left team ad', 'team_ad', $adId, $this->userId());
        $this->redirectWith('/team-ads', 'success', 'Team ad updated.');
    }

    public function closeTeamAd(): void
    {
        $this->requireAuth();
        $this->ensureCsrf('/team-ads');

        $adId = (int) ($_POST['ad_id'] ?? 0);
        $this->teamAds->closeAd($adId, $this->userId());
        activity_log('team_ad_closed', 'Closed team ad', 'team_ad', $adId, $this->userId());
        $this->redirectWith('/team-ads', 'success', 'Team ad closed.');
    }

    public function backupCalls(): void
    {
        $this->requireAuth();
        $viewerId = $this->userId();
        $city = trim($_GET['city'] ?? '');

        $this->view('community/backup-calls', [
            'calls' => $this->backupCalls->listOpenForUser($viewerId, $city),
            'cityOptions' => $this->teamAds->cityOptions(),
            'selectedCity' => $city,
            'myBookings' => $this->backupCalls->listMyEligibleBookings($viewerId),
            'viewerId' => $viewerId,
        ]);
    }

    public function createBackupCall(): void
    {
        $this->requireAuth();
        $this->ensureCsrf('/backup-calls');

        $bookingId = (int) ($_POST['booking_id'] ?? 0);
        $neededRole = trim($_POST['needed_role'] ?? '');
        $isFree = isset($_POST['is_free']) ? 1 : 0;
        $rewardAmount = (float) ($_POST['reward_amount'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $expiresMinutes = (int) ($_POST['expires_minutes'] ?? 90);

        if (!in_array($neededRole, ['goalkeeper', 'defender', 'midfielder', 'attacker', 'any'], true)) {
            $neededRole = 'any';
        }

        $result = $this->backupCalls->createFromBooking(
            $this->userId(),
            $bookingId,
            $neededRole,
            $isFree,
            $rewardAmount,
            $message,
            $expiresMinutes
        );
        if (!($result['ok'] ?? false)) {
            $this->redirectWith('/backup-calls', 'danger', (string) ($result['error'] ?? 'Could not create backup call.'));
        }

        activity_log('backup_call_created', 'Backup call created', 'booking', $bookingId, $this->userId());
        $this->redirectWith('/backup-calls', 'success', 'Backup call created.');
    }

    public function respondBackupCall(): void
    {
        $this->requireAuth();
        $this->ensureCsrf('/backup-calls');

        $callId = (int) ($_POST['call_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $result = $this->backupCalls->respond($callId, $this->userId(), $message);
        if (!($result['ok'] ?? false)) {
            $this->redirectWith('/backup-calls', 'danger', (string) ($result['error'] ?? 'Could not send response.'));
        }

        activity_log('backup_call_responded', 'Responded to backup call', 'backup_call', $callId, $this->userId());
        $this->redirectWith('/backup-calls', 'success', 'Response sent.');
    }

    public function selectBackupResponder(): void
    {
        $this->requireAuth();
        $this->ensureCsrf('/backup-calls');

        $callId = (int) ($_POST['call_id'] ?? 0);
        $responseId = (int) ($_POST['response_id'] ?? 0);
        $result = $this->backupCalls->selectResponder($callId, $this->userId(), $responseId);
        if (!($result['ok'] ?? false)) {
            $this->redirectWith('/backup-calls', 'danger', (string) ($result['error'] ?? 'Could not select responder.'));
        }

        activity_log('backup_call_filled', 'Backup call filled', 'backup_call', $callId, $this->userId());
        $this->redirectWith('/backup-calls', 'success', 'Backup player selected.');
    }

    public function closeBackupCall(): void
    {
        $this->requireAuth();
        $this->ensureCsrf('/backup-calls');

        $callId = (int) ($_POST['call_id'] ?? 0);
        $this->backupCalls->closeByOwner($callId, $this->userId());
        activity_log('backup_call_closed', 'Backup call closed', 'backup_call', $callId, $this->userId());
        $this->redirectWith('/backup-calls', 'success', 'Backup call closed.');
    }
}
