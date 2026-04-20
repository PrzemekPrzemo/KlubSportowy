<?php

namespace App\Models;

class AssociationVoteModel extends ClubScopedModel
{
    protected string $table = 'association_votes';

    public static array $RESULTS = [
        'przyjęta'   => ['label' => 'Przyjęta',   'class' => 'success'],
        'odrzucona'  => ['label' => 'Odrzucona',  'class' => 'danger'],
        'nieważna'   => ['label' => 'Nieważna',   'class' => 'secondary'],
    ];

    public function listForMeeting(int $meetingId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM association_votes WHERE meeting_id = ? AND club_id = ? ORDER BY resolution_number"
        );
        $stmt->execute([$meetingId, $this->clubId()]);
        return $stmt->fetchAll();
    }

    public function addVote(array $data): int
    {
        return $this->insert($data);
    }

    public function resolutionBook(int $year): array
    {
        $stmt = $this->db->prepare(
            "SELECT v.*, m.meeting_date, m.meeting_type
             FROM association_votes v
             JOIN association_meetings m ON m.id = v.meeting_id
             WHERE v.club_id = ? AND YEAR(m.meeting_date) = ?
             ORDER BY v.resolution_number"
        );
        $stmt->execute([$this->clubId(), $year]);
        return $stmt->fetchAll();
    }

    public function nextResolutionNumber(int $meetingId, int $year): string
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM association_votes v
             JOIN association_meetings m ON m.id = v.meeting_id
             WHERE v.club_id = ? AND YEAR(m.meeting_date) = ?"
        );
        $stmt->execute([$this->clubId(), $year]);
        $count = (int)$stmt->fetchColumn() + 1;
        return sprintf('U/%d/%03d', $year, $count);
    }
}
