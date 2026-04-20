<?php

namespace App\Models;

class AssociationMeetingModel extends ClubScopedModel
{
    protected string $table = 'association_meetings';

    public static array $MEETING_TYPES = [
        'walne'             => 'Walne Zebranie',
        'zarząd'            => 'Posiedzenie Zarządu',
        'komisja_rewizyjna' => 'Komisja Rewizyjna',
        'nadzwyczajne'      => 'Nadzwyczajne WZ',
    ];

    public function listForClub(?string $type = null, ?int $year = null, int $page = 1, int $perPage = 20): array
    {
        $where  = ['m.club_id = ?'];
        $params = [$this->clubId()];

        if ($type !== null) { $where[] = 'm.meeting_type = ?'; $params[] = $type; }
        if ($year !== null) { $where[] = 'YEAR(m.meeting_date) = ?'; $params[] = $year; }

        $sql = "SELECT m.*, COUNT(v.id) AS vote_count
                FROM association_meetings m
                LEFT JOIN association_votes v ON v.meeting_id = m.id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY m.id
                ORDER BY m.meeting_date DESC";

        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function findWithVotes(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM association_meetings WHERE id = ? AND club_id = ?"
        );
        $stmt->execute([$id, $this->clubId()]);
        $meeting = $stmt->fetch();
        return $meeting ?: null;
    }

    public function createMeeting(array $data): int
    {
        return $this->insert($data);
    }

    public function addProtocol(int $id, string $path): void
    {
        $this->update($id, ['protocol_path' => $path]);
    }
}
